<?php
/** @var array $services @var array $gallery @var array $testimonials */
$trust = [
    ['clipboard', 'trust1'], ['euro', 'trust2'], ['shield', 'trust3'], ['clock', 'trust4'],
];
$steps = ['step1', 'step2', 'step3', 'step4'];
$heroImg = (int) setting('hero_image');
?>
<section class="hero">
  <div class="wrap hero-grid">
    <div class="hero-copy">
      <h1 class="hero-title"><?= e(setting('hero_title')) ?></h1>
      <p class="hero-text"><?= e(setting('hero_text')) ?></p>
      <div class="hero-actions">
        <?= btn(setting('hero_cta_primary'), '/kontakt#formular', 'primary', 'clipboard') ?>
        <?= btn(setting('hero_cta_secondary') . ' · ' . setting('phone_display'), tel_url(), 'ghost', 'phone') ?>
      </div>
      <?php if (setting('hero_badge') !== ''): ?>
        <p class="hero-badge"><?= icon('check', '', 20) ?><span><?= e(setting('hero_badge')) ?></span></p>
      <?php endif; ?>
    </div>
    <div class="hero-visual" aria-hidden="<?= $heroImg ? 'false' : 'true' ?>">
      <?php if ($heroImg && media_row($heroImg)): ?>
        <div class="hero-photo"><?= media_img($heroImg, null, '', '(min-width: 900px) 46vw, 100vw', false, true) ?></div>
      <?php else: ?>
        <div class="hero-mark">
          <img src="/assets/img/mark.svg" alt="" width="360" height="373">
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="trust" aria-label="Unsere Vorteile">
  <div class="wrap">
    <ul class="trust-grid">
      <?php foreach ($trust as [$ic, $k]): ?>
        <li>
          <?= icon($ic, 'trust-ic', 34) ?>
          <div><strong><?= e(setting($k . '_title')) ?></strong><span><?= e(setting($k . '_text')) ?></span></div>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="section services" id="leistungen">
  <div class="wrap">
    <header class="section-head">
      <h2><?= e(setting('services_title')) ?></h2>
      <p><?= e(setting('services_text')) ?></p>
    </header>
    <div class="svc-grid">
      <?php foreach ($services as $i => $s): ?>
        <a class="svc <?= $i === 0 ? 'svc-feature' : '' ?>" href="/leistungen/<?= e($s['slug']) ?>">
          <span class="svc-icon"><?= icon($s['icon'], '', $i === 0 ? 44 : 32) ?></span>
          <h3><?= e($s['title']) ?></h3>
          <p><?= e($s['short']) ?></p>
          <span class="svc-more">Mehr erfahren <?= icon('arrow', '', 18) ?></span>
        </a>
      <?php endforeach; ?>
      <div class="svc svc-offer">
        <h3>Kostenlose Besichtigung &amp; individuelles Angebot</h3>
        <p>Wir besichtigen Ihr Objekt vor Ort und erstellen Ihnen ein faires und unverbindliches Angebot.</p>
        <?= btn('Besichtigung anfragen', '/kontakt#formular', 'dark') ?>
      </div>
    </div>
  </div>
</section>

<section class="section process" id="ablauf">
  <div class="wrap">
    <header class="section-head section-head-light">
      <h2><?= e(setting('process_title')) ?></h2>
    </header>
    <ol class="steps">
      <?php foreach ($steps as $i => $k): ?>
        <li class="step">
          <span class="step-no" aria-hidden="true"><?= $i + 1 ?></span>
          <h3><?= e(setting($k . '_title')) ?></h3>
          <p><?= e(setting($k . '_text')) ?></p>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<section class="section why">
  <div class="wrap why-grid">
    <div>
      <h2><?= e(setting('why_title')) ?></h2>
      <p class="lead"><?= e(setting('why_text')) ?></p>
      <ul class="checks">
        <?php foreach (lines(setting('why_list')) as $line): ?>
          <li><?= icon('check', 'check-ic', 22) ?><span><?= e($line) ?></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <aside class="why-card">
      <h3><?= e(setting('about_title')) ?></h3>
      <?= paragraphs(setting('about_text')) ?>
      <p class="owner"><strong><?= e(setting('owner_name')) ?></strong><span>Inhaber, <?= e(setting('company_name')) ?></span></p>
    </aside>
  </div>
</section>

<?php if ($gallery): ?>
<section class="section gallery-preview">
  <div class="wrap">
    <header class="section-head">
      <h2><?= e(setting('gallery_home_title')) ?></h2>
    </header>
    <ul class="gal-grid gal-grid-sm">
      <?php foreach ($gallery as $g): ?>
        <li><a href="/galerie" aria-label="<?= e($g['title'] ?: 'Zur Galerie') ?>"><?= media_img((int) $g['media_id'], $g['title'] !== '' ? $g['title'] : null, '', '(min-width: 900px) 30vw, 50vw') ?></a></li>
      <?php endforeach; ?>
    </ul>
    <p class="section-more"><?= btn('Zur Galerie', '/galerie', 'ghost-dark', 'arrow') ?></p>
  </div>
</section>
<?php endif; ?>

<?php if ($testimonials): ?>
<section class="section testimonials">
  <div class="wrap">
    <header class="section-head"><h2><?= e(setting('testimonials_title')) ?></h2></header>
    <div class="quote-grid">
      <?php foreach ($testimonials as $t): ?>
        <figure class="quote">
          <blockquote><p><?= nl2br(e($t['text'])) ?></p></blockquote>
          <figcaption><strong><?= e($t['name']) ?></strong><?= $t['place'] !== '' ? ' · ' . e($t['place']) : '' ?></figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section area">
  <div class="wrap area-grid">
    <div>
      <h2><?= e(setting('area_title')) ?></h2>
      <p class="lead"><?= e(setting('area_text')) ?></p>
      <p><?= btn('Zur Karte & Anfahrt', '/kontakt#karte', 'ghost-dark', 'map') ?></p>
    </div>
    <ul class="chips" aria-label="Einsatzorte">
      <?php foreach (lines(setting('area_list')) as $place): ?><li><?= icon('pin', '', 18) ?><?= e($place) ?></li><?php endforeach; ?>
      <li class="chip-more">und Umgebung</li>
    </ul>
  </div>
</section>

<section class="cta-band">
  <div class="wrap cta-in">
    <div>
      <h2><?= e(setting('cta_title')) ?></h2>
      <p><?= e(setting('cta_text')) ?></p>
    </div>
    <div class="cta-actions">
      <?= btn('Jetzt anrufen', tel_url(), 'dark', 'phone') ?>
      <?= btn('WhatsApp schreiben', whatsapp_url(), 'light', 'whatsapp', ' target="_blank" rel="noopener noreferrer"') ?>
    </div>
  </div>
</section>
