<?php /** @var array $service @var array $others */ ?>
<section class="page-head">
  <div class="wrap">
    <nav class="crumbs" aria-label="Brotkrumen"><a href="/">Start</a><span aria-hidden="true">/</span><a href="/leistungen">Leistungen</a><span aria-hidden="true">/</span><span aria-current="page"><?= e($service['title']) ?></span></nav>
    <h1><?= e($service['title']) ?></h1>
    <p class="lead"><?= e($service['short']) ?></p>
  </div>
</section>

<section class="section">
  <div class="wrap detail-grid">
    <div class="detail-main">
      <?php if ($service['media_id'] && media_row((int) $service['media_id'])): ?>
        <div class="detail-media"><?= media_img((int) $service['media_id'], null, '', '(min-width: 900px) 60vw, 100vw', false, true) ?></div>
      <?php endif; ?>
      <?= paragraphs($service['description']) ?>
      <?php $ben = lines($service['benefits']); if ($ben): ?>
        <h2 class="h3">Ihre Vorteile</h2>
        <ul class="checks">
          <?php foreach ($ben as $b): ?><li><?= icon('check', 'check-ic', 22) ?><span><?= e($b) ?></span></li><?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
    <aside class="detail-side">
      <div class="side-card">
        <h2 class="h3">Kostenlose Besichtigung</h2>
        <p>Wir sehen uns Ihr Objekt vor Ort an und erstellen ein faires, unverbindliches Angebot.</p>
        <?= btn('Jetzt anfragen', '/kontakt?leistung=' . rawurlencode($service['slug']) . '#formular', 'primary', 'mail') ?>
        <?= btn(setting('phone_display'), tel_url(), 'ghost-dark', 'phone') ?>
        <?= btn('WhatsApp', whatsapp_url('Hallo, ich interessiere mich für: ' . $service['title']), 'ghost-dark', 'whatsapp', ' target="_blank" rel="noopener noreferrer"') ?>
      </div>
      <?php if ($others): ?>
        <nav class="side-links" aria-label="Weitere Leistungen">
          <h2 class="h4">Weitere Leistungen</h2>
          <ul><?php foreach ($others as $o): ?><li><a href="/leistungen/<?= e($o['slug']) ?>"><?= e($o['title']) ?></a></li><?php endforeach; ?></ul>
        </nav>
      <?php endif; ?>
    </aside>
  </div>
</section>
