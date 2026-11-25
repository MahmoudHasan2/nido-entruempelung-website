<?php
declare(strict_types=1);

/** Admin layout and reusable form widgets. */

function admin_menu(): array
{
    $new = (int) q_val("SELECT COUNT(*) FROM inquiries WHERE status='new'");
    return [
        ['', 'Übersicht', 'grid', ''],
        ['inquiries', 'Anfragen', 'inbox', $new > 0 ? (string) $new : ''],
        ['settings?g=home', 'Startseite', 'home', ''],
        ['settings?g=pages', 'Seitentexte', 'edit', ''],
        ['services', 'Leistungen', 'box', ''],
        ['gallery', 'Galerie', 'image', ''],
        ['testimonials', 'Kundenstimmen', 'quote', ''],
        ['settings?g=brand', 'Logo & Design', 'sliders', ''],
        ['settings?g=company', 'Kontakt & Firma', 'phone', ''],
        ['navigation', 'Navigation', 'link', ''],
        ['settings?g=footer', 'Fußbereich', 'folder', ''],
        ['seo', 'SEO', 'search', ''],
        ['settings?g=seo', 'Allgemein', 'wrench', ''],
        ['media', 'Medien', 'upload', ''],
        ['password', 'Passwort', 'lock', ''],
    ];
}

function admin_page(string $title, string $activeKey, callable $body): void
{
    header('Content-Type: text/html; charset=UTF-8');
    $nonce = csp_nonce();
    $flash = flash();
    $u = admin_user();
    $cur = current_path() . (isset($_SERVER['QUERY_STRING']) && str_contains((string) $_SERVER['QUERY_STRING'], 'g=') ? '?g=' . get('g') : '');
    ?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> – Dashboard · <?= e(setting('company_name')) ?></title>
<link rel="icon" href="/favicon.ico">
<link rel="stylesheet" href="<?= e(asset('/assets/css/admin.css')) ?>">
</head>
<body>
<a class="skip" href="#main">Zum Inhalt springen</a>
<div class="shell">
  <aside class="side" id="side">
    <a class="side-brand" href="/admin"><?= logo_img('light', 'logo', 40) ?><span>Dashboard</span></a>
    <nav aria-label="Dashboard-Navigation">
      <ul>
        <?php foreach (admin_menu() as [$href, $label, $ic, $badge]):
            $url = '/admin' . ($href !== '' ? '/' . $href : '');
            $on = $url === $cur || ($href !== '' && !str_contains($href, '?') && str_starts_with(current_path(), '/admin/' . $href)); ?>
          <li><a href="<?= e($url) ?>"<?= $on ? ' aria-current="page"' : '' ?>><?= icon($ic, '', 20) ?><span><?= e($label) ?></span><?php if ($badge): ?><b class="badge"><?= e($badge) ?></b><?php endif; ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <div class="side-foot">
      <a href="/" target="_blank" rel="noopener"><?= icon('external', '', 18) ?><span>Website ansehen</span></a>
      <form method="post" action="/admin/logout"><?= csrf_field() ?><button type="submit"><?= icon('logout', '', 18) ?><span>Abmelden (<?= e($u['username'] ?? '') ?>)</span></button></form>
    </div>
  </aside>
  <div class="main-col">
    <header class="topbar">
      <button type="button" class="side-toggle" data-side aria-controls="side" aria-expanded="false"><?= icon('menu', '', 24) ?><span class="sr">Menü</span></button>
      <h1><?= e($title) ?></h1>
    </header>
    <main id="main" class="content">
      <?php if ($flash): ?><div class="flash flash-<?= e($flash['type']) ?>" role="<?= $flash['type'] === 'err' ? 'alert' : 'status' ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
      <?php $body(); ?>
    </main>
  </div>
</div>

<dialog class="picker" id="picker" aria-label="Bild auswählen">
  <div class="picker-head">
    <h2>Bild auswählen</h2>
    <label class="btn btn-primary btn-sm picker-up"><?= icon('upload', '', 18) ?><span>Neues Bild hochladen</span><input type="file" accept="image/jpeg,image/png,image/webp" data-picker-upload hidden></label>
    <button type="button" class="btn btn-ghost btn-sm" data-picker-close>Schließen</button>
  </div>
  <p class="picker-msg" data-picker-msg role="status"></p>
  <ul class="picker-grid" data-picker-grid></ul>
</dialog>
<meta name="csrf" content="<?= e(csrf_token()) ?>">
<script src="<?= e(asset('/assets/js/admin.js')) ?>" defer></script>
</body>
</html>
<?php
}

/* ------------------------------------------------------------ widgets */
function field_input(string $name, string $type, string $label, string $value, array $opt = []): string
{
    $id = 'f_' . preg_replace('/[^a-z0-9_]/i', '_', $name);
    $help = !empty($opt['help']) ? '<p class="help" id="' . $id . '_h">' . e($opt['help']) . '</p>' : '';
    $desc = $help ? ' aria-describedby="' . $id . '_h"' : '';
    $req = !empty($opt['required']) ? ' required' : '';
    $max = !empty($opt['max']) ? ' maxlength="' . (int) $opt['max'] . '"' : '';
    $out = '<div class="field' . (!empty($opt['wide']) ? ' field-wide' : '') . '">';
    switch ($type) {
        case 'textarea':
            $rows = $opt['rows'] ?? 4;
            $out .= '<label for="' . $id . '">' . e($label) . '</label><textarea id="' . $id . '" name="' . e($name) . '" rows="' . $rows . '"' . $desc . $req . $max . '>' . e($value) . '</textarea>';
            break;
        case 'color':
            $out .= '<label for="' . $id . '">' . e($label) . '</label><div class="color"><input type="color" value="' . e($value) . '" data-color-sync="' . $id . '" aria-label="' . e($label) . ' – Farbwähler">'
                . '<input type="text" id="' . $id . '" name="' . e($name) . '" value="' . e($value) . '" pattern="#[0-9A-Fa-f]{6}" maxlength="7"' . $desc . '></div>';
            break;
        case 'select':
            $out .= '<label for="' . $id . '">' . e($label) . '</label><select id="' . $id . '" name="' . e($name) . '"' . $desc . '>';
            foreach ($opt['options'] as $k => $v) {
                $out .= '<option value="' . e((string) $k) . '"' . ((string) $k === $value ? ' selected' : '') . '>' . e($v) . '</option>';
            }
            $out .= '</select>';
            break;
        case 'bool':
            $out .= '<label class="check"><input type="checkbox" name="' . e($name) . '" value="1"' . ($value === '1' ? ' checked' : '') . '><span>' . e($label) . '</span></label>';
            break;
        case 'image':
            $out .= image_picker($name, $value === '' ? null : (int) $value, $label, $help);
            return $out . '</div>';
        default:
            $t = in_array($type, ['email', 'url', 'number', 'password'], true) ? $type : 'text';
            $out .= '<label for="' . $id . '">' . e($label) . '</label><input type="' . $t . '" id="' . $id . '" name="' . e($name) . '" value="' . e($value) . '"' . $desc . $req . $max . (!empty($opt['autocomplete']) ? ' autocomplete="' . e($opt['autocomplete']) . '"' : '') . '>';
    }
    return $out . $help . '</div>';
}

function image_picker(string $name, ?int $mediaId, string $label, string $helpHtml = ''): string
{
    $m = $mediaId ? media_row($mediaId) : null;
    $thumb = $m ? media_url($mediaId, 480) : '';
    return '<div class="imgpick" data-imgpick>'
        . '<span class="lbl">' . e($label) . '</span>'
        . '<input type="hidden" name="' . e($name) . '" value="' . ($m ? (int) $mediaId : '') . '" data-pick-value>'
        . '<div class="imgpick-prev' . ($m ? '' : ' is-empty') . '" data-pick-prev>' . ($m ? '<img src="' . e($thumb) . '" alt="">' : '<span>Kein Bild gewählt</span>') . '</div>'
        . '<div class="imgpick-actions"><button type="button" class="btn btn-ghost btn-sm" data-pick>' . icon('image', '', 18) . '<span>Bild wählen</span></button>'
        . '<button type="button" class="btn btn-quiet btn-sm" data-pick-clear' . ($m ? '' : ' hidden') . '>Entfernen</button></div>'
        . $helpHtml . '</div>';
}

function form_actions(string $label = 'Speichern', string $extra = ''): string
{
    return '<div class="form-actions"><button class="btn btn-primary" type="submit">' . icon('check', '', 18) . '<span>' . e($label) . '</span></button>' . $extra . '</div>';
}

function confirm_button(string $label, string $message, string $class = 'btn btn-danger btn-sm', string $icon = 'trash'): string
{
    return '<button type="submit" class="' . e($class) . '" data-confirm="' . e($message) . '">' . icon($icon, '', 16) . '<span>' . e($label) . '</span></button>';
}

function format_date(string $dt): string
{
    $t = strtotime($dt);
    return $t ? date('d.m.Y, H:i', $t) . ' Uhr' : $dt;
}

function status_labels(): array
{
    return ['new' => 'Neu', 'progress' => 'In Bearbeitung', 'done' => 'Erledigt', 'archived' => 'Archiviert'];
}

/** Normalises $_FILES[$key] (single or multiple) into a list of single-file arrays. */
function uploaded_files(string $key): array
{
    if (empty($_FILES[$key])) {
        return [];
    }
    $f = $_FILES[$key];
    if (!is_array($f['name'])) {
        return $f['error'] === UPLOAD_ERR_NO_FILE ? [] : [$f];
    }
    $out = [];
    foreach ($f['name'] as $i => $n) {
        if (($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $out[] = ['name' => $n, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    }
    return $out;
}

function safe_nav_url(string $u): ?string
{
    $u = trim($u);
    if ($u === '' || mb_strlen($u) > 300) {
        return null;
    }
    if (preg_match('#^(/[^\s]*|\#[\w\-]+|https?://[^\s]+|mailto:[^\s]+|tel:[+0-9 ()\-]+)$#i', $u)) {
        return $u;
    }
    return null;
}
