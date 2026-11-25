<?php
declare(strict_types=1);

/** Public rendering helpers. */

function current_path(): string
{
    $p = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $p = '/' . trim(rawurldecode($p), '/');
    return $p === '' ? '/' : $p;
}

function asset(string $path): string
{
    $f = ROOT . '/' . ltrim($path, '/');
    return '/' . ltrim($path, '/') . (is_file($f) ? '?v=' . filemtime($f) : '');
}

/** Brand logo <img>. $variant: 'dark' (for light backgrounds) | 'light' (for dark backgrounds) */
function logo_img(string $variant = 'dark', string $class = 'logo', int $height = 56): string
{
    $key = $variant === 'light' ? 'logo_light' : 'logo';
    $id = (int) setting($key);
    if ($variant === 'light' && !$id) {
        $id = 0;
    }
    $alt = setting('company_name') . ' – Logo';
    if ($id && ($m = media_row($id))) {
        $w = (int) round($height * $m['width'] / max(1, $m['height']));
        return '<img src="' . e(media_url($id, 960)) . '" alt="' . e($alt) . '" class="' . e($class) . '" width="' . $w . '" height="' . $height . '">';
    }
    $src = $variant === 'light' ? '/assets/img/logo-light.svg' : '/assets/img/logo.svg';
    $w = (int) round($height * 276 / 99.5);
    return '<img src="' . e($src) . '" alt="' . e($alt) . '" class="' . e($class) . '" width="' . $w . '" height="' . $height . '">';
}

function is_bot(): bool
{
    return (bool) preg_match('/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|headless|lighthouse|curl|wget|python|monitor/i', $_SERVER['HTTP_USER_AGENT'] ?? 'bot');
}

/** Privacy-friendly page counter: only daily totals per path, no IP, no cookie, no user agent stored. */
function track_view(string $path): void
{
    if (is_bot() || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        return;
    }
    try {
        q('INSERT INTO page_views (day,path,views) VALUES (?,?,1) ON CONFLICT(day,path) DO UPDATE SET views=views+1', [date('Y-m-d'), mb_substr($path, 0, 120)]);
    } catch (Throwable $e) {
        // statistics must never break a page
    }
}

function render_public(string $tpl, array $meta, array $data = []): void
{
    send_security_headers();
    header('Content-Type: text/html; charset=UTF-8');
    if (($meta['status'] ?? 200) !== 200) {
        http_response_code((int) $meta['status']);
    } else {
        track_view($meta['path'] ?? current_path());
    }
    extract($data, EXTR_SKIP);
    ob_start();
    require APP . "/views/public/$tpl.php";
    $content = ob_get_clean();
    $seo = resolve_meta($meta);
    if (!empty($meta['status']) && $meta['status'] !== 200) {
        $seo['noindex'] = true;
    }
    $graph = org_schema();
    if (!empty($meta['breadcrumbs'])) {
        $graph[] = breadcrumb_schema($meta['breadcrumbs']);
    }
    foreach ($meta['schema'] ?? [] as $s) {
        $graph[] = $s;
    }
    $pageKey = $meta['key'] ?? '';
    require APP . '/views/public/layout.php';
}

/** Splits text into <p> paragraphs (escaped). */
function paragraphs(string $text, string $class = ''): string
{
    $out = '';
    foreach (preg_split('/\R{2,}/', trim($text)) ?: [] as $p) {
        if (trim($p) !== '') {
            $out .= '<p' . ($class ? ' class="' . e($class) . '"' : '') . '>' . nl2br(e(trim($p)), false) . '</p>';
        }
    }
    return $out;
}

function btn(string $label, string $href, string $variant = 'primary', ?string $icon = null, string $extra = ''): string
{
    return '<a class="btn btn-' . e($variant) . '" href="' . e($href) . '"' . $extra . '>'
        . ($icon ? icon($icon, 'btn-ic', 20) : '') . '<span>' . e($label) . '</span></a>';
}
