<?php
declare(strict_types=1);

/** Admin controllers: login, dashboard, password, generic settings forms. */

function ctl_login(): void
{
    $u = admin_user();
    if ($u && (int) $u['must_change'] === 0) {
        redirect('/admin');
    }
    $error = '';
    if (is_post()) {
        if (!csrf_check()) {
            $error = 'Die Sitzung ist abgelaufen. Bitte versuchen Sie es erneut.';
        } else {
            $r = admin_login(post('username'), (string) ($_POST['password'] ?? ''));
            if ($r['ok']) {
                redirect('/admin');
            }
            $error = $r['error'];
        }
    }
    header('Content-Type: text/html; charset=UTF-8');
    http_response_code($error !== '' && str_contains($error, 'Zu viele') ? 429 : 200);
    ?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Anmelden – Dashboard · <?= e(setting('company_name')) ?></title>
<link rel="icon" href="/favicon.ico">
<link rel="stylesheet" href="<?= e(asset('/assets/css/admin.css')) ?>">
</head>
<body class="login-body">
<main class="login">
  <div class="login-card">
    <div class="login-logo"><?= logo_img('light', 'logo', 56) ?></div>
    <h1>Dashboard</h1>
    <p class="muted">Bitte melden Sie sich an, um die Website zu verwalten.</p>
    <?php if ($error): ?><div class="flash flash-err" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="/admin/login" autocomplete="on">
      <?= csrf_field() ?>
      <div class="field"><label for="username">Benutzername</label><input id="username" name="username" type="text" autocomplete="username" required autofocus value="<?= e(post('username')) ?>"></div>
      <div class="field"><label for="password">Passwort</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
      <button class="btn btn-primary btn-block" type="submit"><?= icon('lock', '', 18) ?><span>Anmelden</span></button>
    </form>
    <p class="login-back"><a href="/">← Zur Website</a></p>
  </div>
</main>
</body>
</html>
<?php
}

function ctl_dashboard(): void
{
    $total = (int) q_val('SELECT COUNT(*) FROM inquiries');
    $new = (int) q_val("SELECT COUNT(*) FROM inquiries WHERE status='new'");
    $week = (int) q_val('SELECT COUNT(*) FROM inquiries WHERE created_at >= ?', [date('Y-m-d H:i:s', strtotime('-7 days'))]);
    $recent = q_all('SELECT * FROM inquiries ORDER BY id DESC LIMIT 5');
    $images = (int) q_val('SELECT COUNT(*) FROM gallery_images');
    $svcActive = (int) q_val('SELECT COUNT(*) FROM services WHERE active=1');
    $svcAll = (int) q_val('SELECT COUNT(*) FROM services');
    $mediaN = (int) q_val('SELECT COUNT(*) FROM media');
    $views30 = (int) q_val('SELECT COALESCE(SUM(views),0) FROM page_views WHERE day >= ?', [date('Y-m-d', strtotime('-30 days'))]);
    $views7 = (int) q_val('SELECT COALESCE(SUM(views),0) FROM page_views WHERE day >= ?', [date('Y-m-d', strtotime('-7 days'))]);
    $top = q_all('SELECT path, SUM(views) v FROM page_views WHERE day >= ? GROUP BY path ORDER BY v DESC LIMIT 5', [date('Y-m-d', strtotime('-30 days'))]);
    $failedMail = (int) q_val("SELECT COUNT(*) FROM inquiries WHERE mail_status='failed'");

    $status = [
        ['Website erreichbar', true, site_url('/')],
        ['PHP-Erweiterung GD (Bildverarbeitung)', extension_loaded('gd'), extension_loaded('gd') ? 'aktiv' : 'fehlt – Bild-Uploads funktionieren nicht'],
        ['Upload-Ordner beschreibbar', is_writable(UPLOADS), is_writable(UPLOADS) ? 'ok' : 'nicht beschreibbar'],
        ['Datenbank beschreibbar', is_writable(STORAGE), is_writable(STORAGE) ? 'ok' : 'nicht beschreibbar'],
        ['E-Mail-Versand', true, env('SMTP_HOST', '') !== '' ? 'SMTP (' . env('SMTP_HOST') . ')' : 'PHP mail() des Servers'],
        ['HTTPS aktiv', is_https(), is_https() ? 'ja' : 'nein (lokale Entwicklung?)'],
    ];

    admin_page('Übersicht', '', function () use ($total, $new, $week, $recent, $images, $svcActive, $svcAll, $mediaN, $views30, $views7, $top, $status, $failedMail) {
        ?>
<section class="stats" aria-label="Kennzahlen">
  <a class="stat stat-hot" href="/admin/inquiries?status=new"><span class="stat-n"><?= $new ?></span><span class="stat-l">Neue Anfragen</span></a>
  <a class="stat" href="/admin/inquiries"><span class="stat-n"><?= $total ?></span><span class="stat-l">Anfragen gesamt<small><?= $week ?> in den letzten 7 Tagen</small></span></a>
  <a class="stat" href="/admin/services"><span class="stat-n"><?= $svcActive ?></span><span class="stat-l">Leistungen aktiv<small>von <?= $svcAll ?></small></span></a>
  <a class="stat" href="/admin/gallery"><span class="stat-n"><?= $images ?></span><span class="stat-l">Galerie-Bilder</span></a>
  <div class="stat"><span class="stat-n"><?= $views30 ?></span><span class="stat-l">Seitenaufrufe (30 Tage)<small><?= $views7 ?> in 7 Tagen</small></span></div>
  <a class="stat" href="/admin/media"><span class="stat-n"><?= $mediaN ?></span><span class="stat-l">Dateien in der Mediathek</span></a>
</section>

<?php if ($failedMail > 0): ?>
  <div class="flash flash-err" role="alert"><?= $failedMail ?> Anfrage<?= $failedMail > 1 ? 'n konnten' : ' konnte' ?> nicht per E-Mail zugestellt werden. Sie sind trotzdem unter „Anfragen“ gespeichert. Bitte prüfen Sie die E-Mail-Konfiguration (siehe Anleitung in AI_SITE_GUIDE.md).</div>
<?php endif; ?>

<div class="grid-2">
  <section class="card">
    <header class="card-head"><h2>Letzte Anfragen</h2><a href="/admin/inquiries">Alle anzeigen</a></header>
    <?php if (!$recent): ?>
      <p class="muted">Noch keine Anfragen. Sobald jemand das Kontaktformular nutzt, erscheint die Nachricht hier.</p>
    <?php else: ?>
      <ul class="list">
        <?php foreach ($recent as $r): ?>
          <li>
            <a href="/admin/inquiries?id=<?= (int) $r['id'] ?>">
              <strong><?= e($r['first_name'] . ' ' . $r['last_name']) ?></strong>
              <span><?= e($r['subject']) ?></span>
              <small><?= e(format_date($r['created_at'])) ?></small>
            </a>
            <span class="pill pill-<?= e($r['status']) ?>"><?= e(status_labels()[$r['status']] ?? $r['status']) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <section class="card">
    <header class="card-head"><h2>Schnellzugriff</h2></header>
    <div class="quick">
      <a class="btn btn-primary" href="/admin/settings?g=home"><?= icon('edit', '', 18) ?><span>Startseite bearbeiten</span></a>
      <a class="btn btn-ghost" href="/admin/services/edit"><?= icon('plus', '', 18) ?><span>Leistung hinzufügen</span></a>
      <a class="btn btn-ghost" href="/admin/gallery"><?= icon('upload', '', 18) ?><span>Bilder hochladen</span></a>
      <a class="btn btn-ghost" href="/admin/settings?g=brand"><?= icon('sliders', '', 18) ?><span>Farben &amp; Logo</span></a>
      <a class="btn btn-ghost" href="/admin/seo"><?= icon('search', '', 18) ?><span>SEO-Einstellungen</span></a>
      <a class="btn btn-ghost" href="/" target="_blank" rel="noopener"><?= icon('external', '', 18) ?><span>Website ansehen</span></a>
    </div>
  </section>

  <section class="card">
    <header class="card-head"><h2>Beliebteste Seiten (30 Tage)</h2></header>
    <?php if (!$top): ?><p class="muted">Noch keine Aufrufe gezählt. Die Zählung ist anonym (ohne Cookies und ohne IP-Adressen).</p>
    <?php else: ?>
      <ul class="bars">
        <?php $max = max(array_column($top, 'v')); foreach ($top as $t): ?>
          <li><span class="bar-l"><?= e($t['path']) ?></span><span class="bar"><i data-w="<?= (int) round($t['v'] / $max * 100) ?>"></i></span><span class="bar-v"><?= (int) $t['v'] ?></span></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <section class="card">
    <header class="card-head"><h2>Website-Status</h2></header>
    <ul class="status">
      <?php foreach ($status as [$label, $ok, $info]): ?>
        <li class="<?= $ok ? 'ok' : 'bad' ?>"><?= icon($ok ? 'check' : 'x-circle', '', 20) ?><span><?= e($label) ?><small><?= e($info) ?></small></span></li>
      <?php endforeach; ?>
    </ul>
  </section>
</div>
<?php
    });
}

function ctl_password(array $user): void
{
    $forced = (int) $user['must_change'] === 1;
    $error = '';
    if (is_post()) {
        $r = admin_change_password((int) $user['id'], (string) ($_POST['current'] ?? ''), (string) ($_POST['new'] ?? ''), (string) ($_POST['confirm'] ?? ''));
        if ($r['ok']) {
            flash('Ihr Passwort wurde geändert.');
            redirect('/admin');
        }
        $error = $r['error'];
    }
    admin_page($forced ? 'Neues Passwort festlegen' : 'Passwort ändern', 'password', function () use ($forced, $error) {
        if ($forced): ?>
          <div class="flash flash-warn" role="status"><strong>Erste Anmeldung:</strong> Bitte legen Sie jetzt ein eigenes, sicheres Passwort fest. Erst danach können Sie das Dashboard nutzen.</div>
        <?php endif;
        if ($error): ?><div class="flash flash-err" role="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="card form-narrow" autocomplete="off">
          <?= csrf_field() ?>
          <?= field_input('current', 'password', 'Aktuelles Passwort', '', ['required' => true, 'autocomplete' => 'current-password']) ?>
          <?= field_input('new', 'password', 'Neues Passwort', '', ['required' => true, 'autocomplete' => 'new-password', 'help' => 'Mindestens 10 Zeichen. Eine Passphrase aus mehreren Wörtern ist sicher und gut zu merken.']) ?>
          <?= field_input('confirm', 'password', 'Neues Passwort wiederholen', '', ['required' => true, 'autocomplete' => 'new-password']) ?>
          <?= form_actions('Passwort speichern') ?>
        </form>
        <?php
    });
}

function ctl_settings(): void
{
    $reg = registry();
    $g = get('g', 'home');
    if (!isset($reg[$g])) {
        redirect('/admin/settings?g=home');
    }
    $group = $reg[$g];
    $errors = [];
    if (is_post()) {
        $saved = 0;
        foreach ($group['fields'] as $key => $def) {
            if (!array_key_exists($key, $_POST)) {
                continue;
            }
            $raw = is_string($_POST[$key]) ? $_POST[$key] : '';
            [$val, $err] = clean_field($key, $raw);
            if ($err) {
                $errors[] = $err;
                continue;
            }
            save_setting($key, (string) $val);
            $saved++;
        }
        if (!$errors) {
            flash('Gespeichert. Die Änderungen sind sofort auf der Website sichtbar.');
            redirect('/admin/settings?g=' . $g);
        }
    }
    $title = $group['label'];
    admin_page($title, 'settings:' . $g, function () use ($group, $g, $errors) {
        if ($errors): ?>
          <div class="flash flash-err" role="alert"><strong>Nicht gespeichert:</strong><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <p class="lead"><?= e($group['help']) ?></p>
        <form method="post" class="card" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="form-grid">
            <?php foreach ($group['fields'] as $key => $d):
                [$type, $label, , $help] = array_pad($d, 4, '');
                $current = $_POST[$key] ?? setting($key);
                $wide = in_array($type, ['textarea', 'image'], true) || mb_strlen($label) > 46;
                echo field_input($key, $type, $label, (string) $current, ['help' => $help, 'options' => $d[4] ?? [], 'wide' => $wide, 'rows' => 4]);
            endforeach; ?>
          </div>
          <?= form_actions('Änderungen speichern', '<a class="btn btn-ghost" href="/" target="_blank" rel="noopener">' . icon('external', '', 18) . '<span>Website ansehen</span></a>') ?>
        </form>
        <?php if ($g === 'company'): ?>
          <p class="muted">WhatsApp- und Telefon-Links werden automatisch aus diesen Angaben erzeugt. Die Öffnungszeiten erscheinen erst, wenn Sie etwas eintragen.</p>
        <?php endif;
    });
}
