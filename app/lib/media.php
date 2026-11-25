<?php
declare(strict_types=1);

/**
 * Media library: secure image upload (re-encoded with GD, EXIF stripped, random names),
 * responsive variants and rendering helpers.
 */

const MEDIA_MAX_BYTES = 12 * 1024 * 1024;
const MEDIA_MAX_EDGE = 2400;
const MEDIA_VARIANT_WIDTHS = [480, 960, 1600];

function gd_supports_webp(): bool
{
    return function_exists('imagewebp');
}

/** @return array{ok:bool,id?:int,error?:string} */
function media_store_upload(array $f, string $alt = ''): array
{
    if (!extension_loaded('gd')) {
        return ['ok' => false, 'error' => 'Die PHP-Erweiterung GD fehlt auf dem Server.'];
    }
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $msg = match ($f['error'] ?? 0) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Die Datei ist zu groß.',
            UPLOAD_ERR_NO_FILE => 'Keine Datei ausgewählt.',
            default => 'Der Upload ist fehlgeschlagen.',
        };
        return ['ok' => false, 'error' => $msg];
    }
    $tmp = (string) $f['tmp_name'];
    if (!is_uploaded_file($tmp) && !(env('APP_ENV') === 'testing' && is_file($tmp))) {
        return ['ok' => false, 'error' => 'Ungültiger Upload.'];
    }
    if (filesize($tmp) > MEDIA_MAX_BYTES) {
        return ['ok' => false, 'error' => 'Die Datei ist größer als 12 MB.'];
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
    $info = @getimagesize($tmp);
    if (!$info || !in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        return ['ok' => false, 'error' => 'Erlaubt sind nur JPG-, PNG- und WebP-Bilder.'];
    }
    [$w, $h] = $info;
    if ($w < 1 || $h < 1 || $w > 12000 || $h > 12000 || ($w * $h) > 60_000_000) {
        return ['ok' => false, 'error' => 'Die Bildabmessungen sind nicht zulässig.'];
    }
    $img = @imagecreatefromstring((string) file_get_contents($tmp));
    if (!$img) {
        return ['ok' => false, 'error' => 'Das Bild konnte nicht gelesen werden.'];
    }

    // honour EXIF orientation (photos from phones), then drop all metadata by re-encoding
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($tmp);
        $o = (int) ($exif['Orientation'] ?? 1);
        $rot = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
        if ($rot !== 0 && ($r = imagerotate($img, $rot, 0))) {
            $img = $r;
        }
    }
    $w = imagesx($img);
    $h = imagesy($img);
    $hasAlpha = $mime !== 'image/jpeg';
    $ext = $mime === 'image/jpeg' ? 'jpg' : ($mime === 'image/png' ? 'png' : 'webp');

    if (max($w, $h) > MEDIA_MAX_EDGE) {
        $s = MEDIA_MAX_EDGE / max($w, $h);
        $img = media_resize($img, (int) round($w * $s), (int) round($h * $s), $hasAlpha);
        $w = imagesx($img);
        $h = imagesy($img);
    }

    if (!is_dir(UPLOADS)) {
        @mkdir(UPLOADS, 0755, true);
    }
    $base = bin2hex(random_bytes(10));
    if (!media_write($img, UPLOADS . "/$base.$ext", $ext)) {
        return ['ok' => false, 'error' => 'Das Bild konnte nicht gespeichert werden.'];
    }

    $vext = gd_supports_webp() ? 'webp' : ($ext === 'png' ? 'png' : 'jpg');
    $made = [];
    foreach (MEDIA_VARIANT_WIDTHS as $vw) {
        if ($vw >= $w) {
            continue;
        }
        $vh = (int) round($h * $vw / $w);
        $v = media_resize($img, $vw, $vh, $hasAlpha);
        if (media_write($v, UPLOADS . "/$base-$vw.$vext", $vext)) {
            $made[] = $vw;
        }
        imagedestroy($v);
    }
    imagedestroy($img);

    $name = preg_replace('/[^\p{L}\p{N}._ -]/u', '', (string) ($f['name'] ?? '')) ?: 'bild';
    q('INSERT INTO media (file,ext,orig_name,alt,width,height,bytes,variants,created_at) VALUES (?,?,?,?,?,?,?,?,?)', [
        $base, $ext, mb_substr($name, 0, 120), mb_substr($alt, 0, 300), $w, $h,
        (int) filesize(UPLOADS . "/$base.$ext"), $vext . ':' . implode(',', $made), now(),
    ]);
    return ['ok' => true, 'id' => (int) db()->lastInsertId()];
}

function media_resize(GdImage $src, int $w, int $h, bool $alpha): GdImage
{
    $dst = imagecreatetruecolor($w, $h);
    if ($alpha) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));
    return $dst;
}

function media_write(GdImage $img, string $path, string $ext): bool
{
    if ($ext === 'png' || $ext === 'webp') {
        imagealphablending($img, false);
        imagesavealpha($img, true);
    }
    return match ($ext) {
        'jpg' => imagejpeg($img, $path, 84),
        'png' => imagepng($img, $path, 7),
        'webp' => imagewebp($img, $path, 82),
        default => false,
    };
}

function media_row(?int $id): ?array
{
    static $cache = [];
    if (!$id) {
        return null;
    }
    return $cache[$id] ??= q_one('SELECT * FROM media WHERE id=?', [$id]);
}

function media_variants(array $m): array
{
    [$vext, $list] = array_pad(explode(':', (string) $m['variants'], 2), 2, '');
    $ws = array_values(array_filter(array_map('intval', explode(',', $list))));
    return [$vext, $ws];
}

function media_url(?int $id, ?int $w = null): string
{
    $m = media_row($id);
    if (!$m) {
        return '';
    }
    if ($w) {
        [$vext, $ws] = media_variants($m);
        foreach ($ws as $vw) {
            if ($vw >= $w) {
                return "/uploads/{$m['file']}-$vw.$vext";
            }
        }
    }
    return "/uploads/{$m['file']}.{$m['ext']}";
}

/** Responsive <img>. $alt === null falls back to the alt text stored in the media library. */
function media_img(?int $id, ?string $alt = null, string $class = '', string $sizes = '100vw', bool $lazy = true, bool $priority = false): string
{
    $m = media_row($id);
    if (!$m) {
        return '';
    }
    [$vext, $ws] = media_variants($m);
    $srcset = [];
    foreach ($ws as $vw) {
        $srcset[] = "/uploads/{$m['file']}-$vw.$vext {$vw}w";
    }
    $srcset[] = "/uploads/{$m['file']}.{$m['ext']} {$m['width']}w";
    $src = media_url($id, 960);
    $attrs = ' src="' . e($src) . '" srcset="' . e(implode(', ', $srcset)) . '" sizes="' . e($sizes) . '"'
        . ' width="' . (int) $m['width'] . '" height="' . (int) $m['height'] . '"'
        . ' alt="' . e($alt ?? $m['alt']) . '" decoding="async"'
        . ($priority ? ' fetchpriority="high"' : ($lazy ? ' loading="lazy"' : ''))
        . ($class !== '' ? ' class="' . e($class) . '"' : '');
    return '<img' . $attrs . '>';
}

/** Where is a media item used? Returns human-readable usage strings. */
function media_usage(int $id): array
{
    $use = [];
    foreach (q_all("SELECT key FROM settings WHERE value = ?", [(string) $id]) as $r) {
        $def = field_def($r['key']);
        if ($def && $def[0] === 'image') {
            $use[] = 'Einstellung: ' . $def[1];
        }
    }
    foreach (q_all('SELECT title FROM services WHERE media_id=?', [$id]) as $r) {
        $use[] = 'Leistung: ' . $r['title'];
    }
    $n = (int) q_val('SELECT COUNT(*) FROM gallery_images WHERE media_id=?', [$id]);
    if ($n > 0) {
        $use[] = 'Galerie (' . $n . ' Bild' . ($n > 1 ? 'er' : '') . ')';
    }
    foreach (q_all('SELECT page_key FROM page_seo WHERE og_media_id=?', [$id]) as $r) {
        $use[] = 'SEO-Bild: ' . $r['page_key'];
    }
    return $use;
}

function media_delete(int $id): bool
{
    $m = media_row($id);
    if (!$m || media_usage($id)) {
        return false;
    }
    [$vext, $ws] = media_variants($m);
    @unlink(UPLOADS . "/{$m['file']}.{$m['ext']}");
    foreach ($ws as $vw) {
        @unlink(UPLOADS . "/{$m['file']}-$vw.$vext");
    }
    q('DELETE FROM media WHERE id=?', [$id]);
    return true;
}

/** Image for brand slots (logo / favicon) with fallback to the shipped asset. */
function brand_asset(string $key, string $fallback): string
{
    $id = (int) setting($key);
    return $id && media_row($id) ? media_url($id) : $fallback;
}
