<?php
declare(strict_types=1);

/**
 * Front controller. Apache/LiteSpeed (.htaccess) and the PHP dev server (router script) send every
 * request that is not an existing public file to this file.
 */

// PHP built-in server: serve only public static folders, never app/ or storage/.
if (PHP_SAPI === 'cli-server') {
    $p = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (preg_match('#^/(assets|uploads)/#', $p) || $p === '/favicon.ico') {
        $file = __DIR__ . $p;
        if (is_file($file) && !str_contains($p, '..')) {
            return false; // let the built-in server deliver the static file
        }
    }
}

require __DIR__ . '/app/bootstrap.php';
require APP . '/lib/view.php';
require APP . '/lib/seo.php';
require APP . '/lib/mail.php';
require APP . '/lib/auth.php';

$path = current_path();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($path === '/admin' || str_starts_with($path, '/admin/')) {
    require APP . '/admin/router.php';
    exit;
}

if (!in_array($method, ['GET', 'HEAD', 'POST'], true)) {
    http_response_code(405);
    header('Allow: GET, HEAD, POST');
    exit;
}

/* ---------------------------------------------------------------- routes */
switch (true) {
    case $path === '/' || $path === '/index.php':
        $services = q_all('SELECT * FROM services WHERE active=1 ORDER BY sort,id');
        $gallery = q_all('SELECT * FROM gallery_images WHERE visible=1 ORDER BY sort,id LIMIT 6');
        $testimonials = q_all('SELECT * FROM testimonials WHERE visible=1 ORDER BY sort,id');
        render_public('home', [
            'key' => 'home', 'path' => '/',
            'title' => setting('site_title'), 'description' => setting('site_description'),
        ], compact('services', 'gallery', 'testimonials'));
        break;

    case $path === '/leistungen':
        $services = q_all('SELECT * FROM services WHERE active=1 ORDER BY sort,id');
        $list = [];
        foreach ($services as $i => $s) {
            $list[] = ['@type' => 'ListItem', 'position' => $i + 1, 'url' => site_url('/leistungen/' . $s['slug']), 'name' => $s['title']];
        }
        render_public('services', [
            'key' => 'leistungen', 'path' => '/leistungen',
            'breadcrumbs' => [['Start', '/'], ['Leistungen', '/leistungen']],
            'schema' => [['@type' => 'ItemList', 'name' => 'Leistungen', 'itemListElement' => $list]],
        ], compact('services'));
        break;

    case (bool) preg_match('#^/leistungen/([a-z0-9\-]+)$#', $path, $m):
        $service = q_one('SELECT * FROM services WHERE slug=? AND active=1', [$m[1]]);
        if (!$service) {
            not_found();
        }
        $others = q_all('SELECT slug,title FROM services WHERE active=1 AND id<>? ORDER BY sort,id', [$service['id']]);
        $schema = [[
            '@type' => 'Service', 'name' => $service['title'], 'description' => $service['short'],
            'provider' => ['@id' => site_url('/#localbusiness')],
            'areaServed' => array_map(fn($c) => ['@type' => 'City', 'name' => $c], lines(setting('area_list'))),
            'url' => site_url('/leistungen/' . $service['slug']),
        ]];
        $meta = [
            'key' => 'service:' . $service['slug'], 'path' => '/leistungen/' . $service['slug'],
            'title' => $service['seo_title'] !== '' ? $service['seo_title'] : $service['title'] . ' in Gifhorn | ' . setting('company_name'),
            'description' => $service['seo_description'] !== '' ? $service['seo_description'] : $service['short'] . ' Kostenlose Besichtigung und unverbindliches Angebot von ' . setting('company_name') . ' in Gifhorn.',
            'og_image_media' => $service['media_id'],
            'breadcrumbs' => [['Start', '/'], ['Leistungen', '/leistungen'], [$service['title'], '/leistungen/' . $service['slug']]],
            'schema' => $schema,
        ];
        render_public('service', $meta, compact('service', 'others'));
        break;

    case $path === '/galerie':
        $categories = q_all('SELECT c.* FROM gallery_categories c WHERE EXISTS (SELECT 1 FROM gallery_images g WHERE g.category_id=c.id AND g.visible=1) ORDER BY c.sort,c.id');
        $active = get('kategorie');
        $sql = 'SELECT g.* FROM gallery_images g WHERE g.visible=1';
        $params = [];
        if ($active !== '') {
            $cat = q_one('SELECT id FROM gallery_categories WHERE slug=?', [$active]);
            if ($cat) {
                $sql .= ' AND g.category_id=?';
                $params[] = $cat['id'];
            } else {
                $active = '';
            }
        }
        $images = q_all($sql . ' ORDER BY g.sort, g.id', $params);
        render_public('gallery', [
            'key' => 'galerie', 'path' => '/galerie',
            'breadcrumbs' => [['Start', '/'], ['Galerie', '/galerie']],
        ], compact('images', 'categories', 'active'));
        break;

    case $path === '/kontakt':
        handle_contact();
        break;

    case $path === '/impressum':
        render_public('impressum', ['key' => 'impressum', 'path' => '/impressum', 'breadcrumbs' => [['Start', '/'], ['Impressum', '/impressum']]]);
        break;

    case $path === '/datenschutz':
        render_public('datenschutz', ['key' => 'datenschutz', 'path' => '/datenschutz', 'breadcrumbs' => [['Start', '/'], ['Datenschutz', '/datenschutz']]]);
        break;

    case $path === '/sitemap.xml':
        header('Content-Type: application/xml; charset=UTF-8');
        header('Cache-Control: public, max-age=3600');
        echo sitemap_xml();
        break;

    case $path === '/robots.txt':
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: public, max-age=3600');
        echo robots_txt();
        break;

    default:
        not_found();
}

function not_found(): never
{
    render_public('notfound', ['key' => '404', 'path' => current_path(), 'status' => 404, 'title' => 'Seite nicht gefunden | ' . setting('company_name'), 'description' => 'Diese Seite wurde nicht gefunden.']);
    exit;
}

/* --------------------------------------------------------- contact form */
function inquiry_subjects(): array
{
    $s = array_column(q_all('SELECT title FROM services WHERE active=1 ORDER BY sort,id'), 'title');
    $s[] = 'Allgemeine Anfrage';
    return $s;
}

function handle_contact(): void
{
    session_boot();
    $subjects = inquiry_subjects();
    $form = [];
    $errors = [];
    $sent = get('gesendet') === '1';

    if (is_post()) {
        $form = [
            'first_name' => post('first_name'), 'last_name' => post('last_name'), 'email' => post('email'),
            'phone' => post('phone'), 'subject' => post('subject'), 'message' => post('message'),
            'privacy' => post('privacy'),
        ];
        $startedAt = (int) ($_SESSION['form_t'] ?? 0);
        $ip = client_ip();
        $clean = fn(string $s) => trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s) ?? '');

        if (!csrf_check()) {
            $errors['_form'] = 'Die Sitzung ist abgelaufen. Bitte senden Sie das Formular erneut ab.';
            $_SESSION['form_t'] = time();
        } elseif (post('website') !== '') {
            // honeypot filled by a bot: pretend success, store nothing
            redirect('/kontakt?gesendet=1#formular');
        } else {
            q('DELETE FROM inquiry_attempts WHERE created_at < ?', [time() - 7200]);
            if ((int) q_val('SELECT COUNT(*) FROM inquiry_attempts WHERE ip=? AND created_at>?', [$ip, time() - 3600]) >= 5) {
                $errors['_form'] = 'Von Ihrer Adresse wurden zu viele Nachrichten gesendet. Bitte versuchen Sie es später erneut oder rufen Sie uns an.';
            }
            if ($startedAt === 0 || time() - $startedAt < 3) {
                $errors['_form'] = 'Das Formular wurde zu schnell abgesendet. Bitte versuchen Sie es erneut.';
                $_SESSION['form_t'] = time() - 10;
            }
            $f = array_map($clean, $form);
            if ($f['first_name'] === '' || mb_strlen($f['first_name']) > 80) $errors['first_name'] = 'Bitte geben Sie Ihren Vornamen an.';
            if ($f['last_name'] === '' || mb_strlen($f['last_name']) > 80) $errors['last_name'] = 'Bitte geben Sie Ihren Nachnamen an.';
            if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($f['email']) > 160) $errors['email'] = 'Bitte geben Sie eine gültige E-Mail-Adresse an.';
            if ($f['phone'] !== '' && !preg_match('/^[0-9+()\/.\-\s]{5,40}$/', $f['phone'])) $errors['phone'] = 'Bitte geben Sie eine gültige Telefonnummer an oder lassen Sie das Feld leer.';
            if (!in_array($f['subject'], $subjects, true)) $errors['subject'] = 'Bitte wählen Sie einen Betreff aus.';
            if (mb_strlen($f['message']) < 10) $errors['message'] = 'Bitte beschreiben Sie Ihr Anliegen (mindestens 10 Zeichen).';
            elseif (mb_strlen($f['message']) > 5000) $errors['message'] = 'Die Nachricht ist zu lang (maximal 5000 Zeichen).';
            elseif (preg_match_all('#https?://#i', $f['message']) > 2) $errors['message'] = 'Bitte entfernen Sie die Links aus Ihrer Nachricht.';
            if ($f['privacy'] !== '1') $errors['privacy'] = 'Bitte stimmen Sie der Datenschutzerklärung zu.';

            if (!$errors) {
                q('INSERT INTO inquiry_attempts (ip, created_at) VALUES (?,?)', [$ip, time()]);
                q('INSERT INTO inquiries (first_name,last_name,email,phone,subject,message,status,is_read,mail_status,ip,created_at)
                   VALUES (?,?,?,?,?,?,?,?,?,?,?)', [$f['first_name'], $f['last_name'], $f['email'], $f['phone'], $f['subject'], $f['message'], 'new', 0, 'pending', $ip, now()]);
                $id = (int) db()->lastInsertId();
                $body = "Neue Anfrage über die Website\n\n"
                    . "Name:     {$f['first_name']} {$f['last_name']}\n"
                    . "E-Mail:   {$f['email']}\n"
                    . 'Telefon:  ' . ($f['phone'] !== '' ? $f['phone'] : '-') . "\n"
                    . "Betreff:  {$f['subject']}\n\n"
                    . "Nachricht:\n{$f['message']}\n\n"
                    . '-- Anfrage #' . $id . ' vom ' . date('d.m.Y H:i') . " Uhr\n"
                    . 'Im Dashboard ansehen: ' . site_url('/admin/inquiries?id=' . $id) . "\n";
                $to = (string) env('MAIL_TO', setting('email'));
                $res = send_mail($to, 'Neue Anfrage: ' . $f['subject'] . ' – ' . $f['first_name'] . ' ' . $f['last_name'], $body, $f['email'], $f['first_name'] . ' ' . $f['last_name']);
                q('UPDATE inquiries SET mail_status=? WHERE id=?', [$res['ok'] ? 'sent' : 'failed', $id]);
                if (!$res['ok']) {
                    error_log('[contact] mail failed for inquiry #' . $id . ': ' . ($res['error'] ?? ''));
                }
                unset($_SESSION['form_t']);
                redirect('/kontakt?gesendet=1#formular');
            }
        }
    } else {
        $_SESSION['form_t'] = time();
        $pre = get('leistung');
        if ($pre !== '') {
            $svc = q_one('SELECT title FROM services WHERE slug=? AND active=1', [$pre]);
            if ($svc) {
                $form['subject'] = $svc['title'];
            }
        }
    }

    render_public('contact', [
        'key' => 'kontakt', 'path' => '/kontakt',
        'breadcrumbs' => [['Start', '/'], ['Kontakt', '/kontakt']],
        'status' => 200,
    ], compact('form', 'errors', 'subjects', 'sent'));
}
