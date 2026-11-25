<?php /** @var array $images @var array $categories @var string $active */ ?>
<section class="page-head">
  <div class="wrap">
    <nav class="crumbs" aria-label="Brotkrumen"><a href="/">Start</a><span aria-hidden="true">/</span><span aria-current="page">Galerie</span></nav>
    <h1><?= e(setting('gallery_h1')) ?></h1>
    <p class="lead"><?= e(setting('gallery_intro')) ?></p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <?php if (!$images): ?>
      <div class="empty-state">
        <?= icon('image', '', 48) ?>
        <p><?= e(setting('gallery_empty')) ?></p>
        <?= btn('Anfrage senden', '/kontakt#formular', 'primary', 'mail') ?>
      </div>
    <?php else: ?>
      <?php if (count($categories) > 0): ?>
        <ul class="filters" aria-label="Kategorien">
          <li><a href="/galerie"<?= $active === '' ? ' aria-current="true"' : '' ?>>Alle</a></li>
          <?php foreach ($categories as $c): ?>
            <li><a href="/galerie?kategorie=<?= e($c['slug']) ?>"<?= $active === $c['slug'] ? ' aria-current="true"' : '' ?>><?= e($c['name']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <ul class="gal-grid" data-lightbox>
        <?php foreach ($images as $i => $g): $m = media_row((int) $g['media_id']); if (!$m) continue; ?>
          <li>
            <a href="<?= e(media_url((int) $g['media_id'], 1600)) ?>" data-title="<?= e($g['title']) ?>" data-desc="<?= e($g['description']) ?>" aria-label="Bild vergrößern<?= $g['title'] !== '' ? ': ' . e($g['title']) : '' ?>">
              <?= media_img((int) $g['media_id'], $g['title'] !== '' ? $g['title'] : null, '', '(min-width: 1100px) 30vw, (min-width: 640px) 45vw, 100vw', $i > 5) ?>
              <?php if ($g['title'] !== ''): ?><span class="gal-cap"><?= e($g['title']) ?></span><?php endif; ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>

<dialog class="lightbox" id="lightbox" aria-label="Bildansicht">
  <button type="button" class="lb-close" data-lb-close aria-label="Schließen"><?= icon('close', '', 28) ?></button>
  <button type="button" class="lb-nav lb-prev" data-lb-prev aria-label="Vorheriges Bild"><?= icon('chevron-left', '', 32) ?></button>
  <figure>
    <img src="" alt="" data-lb-img>
    <figcaption data-lb-cap></figcaption>
  </figure>
  <button type="button" class="lb-nav lb-next" data-lb-next aria-label="Nächstes Bild"><?= icon('chevron-right', '', 32) ?></button>
</dialog>
