<?php /** @var array $services */ ?>
<section class="page-head">
  <div class="wrap">
    <nav class="crumbs" aria-label="Brotkrumen"><a href="/">Start</a><span aria-hidden="true">/</span><span aria-current="page">Leistungen</span></nav>
    <h1><?= e(setting('services_h1')) ?></h1>
    <p class="lead"><?= e(setting('services_intro')) ?></p>
  </div>
</section>

<section class="section svc-list">
  <div class="wrap">
    <?php foreach ($services as $i => $s): ?>
      <article class="svc-row <?= $i % 2 ? 'is-flip' : '' ?>" id="<?= e($s['slug']) ?>">
        <div class="svc-media">
          <?php if ($s['media_id'] && media_row((int) $s['media_id'])): ?>
            <?= media_img((int) $s['media_id'], null, '', '(min-width: 900px) 40vw, 100vw') ?>
          <?php else: ?>
            <div class="svc-art"><?= icon($s['icon'], '', 120) ?></div>
          <?php endif; ?>
        </div>
        <div class="svc-body">
          <h2><a href="/leistungen/<?= e($s['slug']) ?>"><?= e($s['title']) ?></a></h2>
          <p class="lead"><?= e($s['short']) ?></p>
          <?= paragraphs($s['description']) ?>
          <?php $ben = lines($s['benefits']); if ($ben): ?>
            <ul class="checks checks-sm">
              <?php foreach ($ben as $b): ?><li><?= icon('check', 'check-ic', 20) ?><span><?= e($b) ?></span></li><?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <div class="row-actions">
            <?= btn('Anfrage zu dieser Leistung', '/kontakt?leistung=' . rawurlencode($s['slug']) . '#formular', 'primary', 'mail') ?>
            <?= btn('Details', '/leistungen/' . $s['slug'], 'ghost-dark', 'arrow') ?>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="cta-band">
  <div class="wrap cta-in">
    <div><h2><?= e(setting('cta_title')) ?></h2><p><?= e(setting('cta_text')) ?></p></div>
    <div class="cta-actions">
      <?= btn('Jetzt anrufen', tel_url(), 'dark', 'phone') ?>
      <?= btn('Zum Kontaktformular', '/kontakt#formular', 'light', 'mail') ?>
    </div>
  </div>
</section>
