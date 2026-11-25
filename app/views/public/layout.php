<?php
/** @var string $content @var array $seo @var array $graph @var string $pageKey */
$path = current_path();
$lang = setting('default_lang') ?: 'de';
$favId = (int) setting('favicon');
$favMain = $favId && media_row($favId) ? media_url($favId, 192) : '/favicon.ico';
$favPng48 = $favId && media_row($favId) ? media_url($favId, 480) : asset('/assets/img/favicon-48.png');
$favPng192 = $favId && media_row($favId) ? media_url($favId, 480) : asset('/assets/img/favicon-192.png');
$nonce = csp_nonce();
?>
<!doctype html>
<html lang="<?= e($lang) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($seo['title']) ?></title>
<meta name="description" content="<?= e($seo['description']) ?>">
<?php if ($seo['keywords'] !== ''): ?><meta name="keywords" content="<?= e($seo['keywords']) ?>">
<?php endif; ?>
<link rel="canonical" href="<?= e($seo['canonical']) ?>">
<meta name="robots" content="<?= $seo['noindex'] ? 'noindex, nofollow' : 'index, follow, max-image-preview:large' ?>">
<meta name="theme-color" content="<?= e(setting('color_secondary')) ?>">
<meta property="og:locale" content="de_DE">
<meta property="og:type" content="<?= e($seo['og_type']) ?>">
<meta property="og:site_name" content="<?= e(setting('company_name')) ?>">
<meta property="og:title" content="<?= e($seo['og_title']) ?>">
<meta property="og:description" content="<?= e($seo['og_description']) ?>">
<meta property="og:url" content="<?= e($seo['canonical']) ?>">
<meta property="og:image" content="<?= e($seo['og_image']) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($seo['og_title']) ?>">
<meta name="twitter:description" content="<?= e($seo['og_description']) ?>">
<meta name="twitter:image" content="<?= e($seo['og_image']) ?>">
<?php if (setting('head_note') !== ''): ?><meta name="google-site-verification" content="<?= e(setting('head_note')) ?>">
<?php endif; ?>
<link rel="icon" href="<?= e($favMain) ?>" sizes="48x48">
<link rel="icon" type="image/png" sizes="192x192" href="<?= e($favPng192) ?>">
<link rel="apple-touch-icon" href="<?= e($favId ? $favPng192 : asset('/assets/img/apple-touch-icon.png')) ?>">
<link rel="preload" href="/assets/fonts/barlow-condensed-latin-800-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/assets/fonts/barlow-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('/assets/css/site.css')) ?>">
<style nonce="<?= e($nonce) ?>"><?= brand_css() ?></style>
<?= jsonld($graph) ?>

</head>
<body class="page-<?= e($pageKey ?: 'x') ?>">
<a class="skip" href="#main">Zum Inhalt springen</a>

<header class="site-header" id="top">
  <div class="wrap header-in">
    <a class="brand" href="/" aria-label="<?= e(setting('company_name')) ?> – Startseite"><?= logo_img('light', 'logo', 46) ?></a>
    <nav class="nav" id="nav" aria-label="Hauptnavigation">
      <ul>
        <?php foreach (nav_items('header') as $n):
            $on = $n['url'] === $path || ($n['url'] !== '/' && str_starts_with($path, $n['url'] . '/')); ?>
          <li><a href="<?= e($n['url']) ?>"<?= $on ? ' aria-current="page"' : '' ?>><?= e($n['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="nav-cta">
        <a class="nav-phone" href="<?= e(tel_url()) ?>"><?= icon('phone', '', 20) ?><span><?= e(setting('phone_display')) ?></span></a>
      </div>
    </nav>
    <button class="menu-btn" type="button" aria-expanded="false" aria-controls="nav" data-menu>
      <span class="sr"><?= 'Menü' ?></span><?= icon('menu', 'ic-open', 28) ?><?= icon('close', 'ic-close', 28) ?>
    </button>
  </div>
</header>

<main id="main"><?= $content ?></main>

<footer class="site-footer">
  <div class="wrap footer-grid">
    <div class="footer-brand">
      <?php
        $fl = (int) setting('footer_logo');
        echo $fl && media_row($fl)
            ? '<img src="' . e(media_url($fl, 480)) . '" alt="' . e(setting('company_name')) . ' – Logo" class="logo" width="' . (int) round(56 * media_row($fl)['width'] / max(1, media_row($fl)['height'])) . '" height="56">'
            : logo_img('light', 'logo', 56);
      ?>
      <p><?= nl2br(e(setting('footer_text'))) ?></p>
      <?php
        $socials = ['social_facebook' => 'Facebook', 'social_instagram' => 'Instagram', 'social_tiktok' => 'TikTok', 'social_youtube' => 'YouTube'];
        $any = array_filter(array_map('setting', array_keys($socials)));
        if ($any): ?>
        <ul class="social">
          <?php foreach ($socials as $k => $label): if (setting($k) !== ''): ?>
            <li><a href="<?= e(setting($k)) ?>" rel="noopener noreferrer me" target="_blank"><?= e($label) ?></a></li>
          <?php endif; endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
    <nav class="footer-col" aria-label="Seiten">
      <h2>Seiten</h2>
      <ul><?php foreach (nav_items('footer') as $n): ?><li><a href="<?= e($n['url']) ?>"><?= e($n['label']) ?></a></li><?php endforeach; ?></ul>
    </nav>
    <div class="footer-col">
      <h2>Kontakt</h2>
      <ul class="contact-list">
        <li><?= icon('phone', '', 18) ?><a href="<?= e(tel_url()) ?>"><?= e(setting('phone_display')) ?></a></li>
        <li><?= icon('whatsapp', '', 18) ?><a href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a></li>
        <li><?= icon('mail', '', 18) ?><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></li>
        <li><?= icon('pin', '', 18) ?><span><?= e(setting('street')) ?><br><?= e(setting('zip') . ' ' . setting('city')) ?></span></li>
      </ul>
    </div>
  </div>
  <div class="wrap footer-bar">
    <span><?= e(str_replace('{year}', date('Y'), setting('footer_copyright'))) ?></span>
    <nav aria-label="Rechtliches"><ul><?php foreach (nav_items('legal') as $n): ?><li><a href="<?= e($n['url']) ?>"><?= e($n['label']) ?></a></li><?php endforeach; ?></ul></nav>
  </div>
</footer>

<div class="mobile-bar" role="region" aria-label="Schnellkontakt">
  <a href="<?= e(tel_url()) ?>"><?= icon('phone', '', 20) ?><span>Anrufen</span></a>
  <a href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener noreferrer"><?= icon('whatsapp', '', 20) ?><span>WhatsApp</span></a>
  <a href="/kontakt#formular"><?= icon('mail', '', 20) ?><span>Anfrage</span></a>
</div>

<script src="<?= e(asset('/assets/js/site.js')) ?>" defer></script>
</body>
</html>
