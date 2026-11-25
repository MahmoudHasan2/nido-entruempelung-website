<?php $addr1 = setting('street'); $addr2 = setting('zip') . ' ' . setting('city'); ?>
<section class="page-head">
  <div class="wrap">
    <nav class="crumbs" aria-label="Brotkrumen"><a href="/">Start</a><span aria-hidden="true">/</span><span aria-current="page">Impressum</span></nav>
    <h1>Impressum</h1>
  </div>
</section>
<section class="section legal">
  <div class="wrap prose">
    <h2>Angaben gemäß § 5 DDG</h2>
    <p><strong><?= e(setting('company_name')) ?></strong><br>
    Inhaber: <?= e(setting('owner_name')) ?><br>
    <?= e($addr1) ?><br><?= e($addr2) ?><br><?= e(setting('country')) ?></p>

    <h2>Kontakt</h2>
    <p>Telefon: <a href="<?= e(tel_url()) ?>"><?= e(setting('phone_display')) ?></a><br>
    E-Mail: <a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a><br>
    Website: <a href="<?= e(site_url('/')) ?>"><?= e(preg_replace('#^https?://#', '', rtrim(site_url('/'), '/'))) ?></a></p>

    <h2>Verantwortlich für den Inhalt nach § 55 Abs. 2 RStV</h2>
    <p><?= e(setting('owner_name')) ?><br><?= e($addr1) ?><br><?= e($addr2) ?></p>

    <?php if (trim(setting('impressum_extra')) !== ''): ?>
      <h2>Weitere Angaben</h2>
      <?= paragraphs(setting('impressum_extra')) ?>
    <?php endif; ?>

    <h2>Haftung für Inhalte</h2>
    <p>Als Diensteanbieter sind wir für eigene Inhalte auf diesen Seiten nach den allgemeinen Gesetzen verantwortlich. Wir sind jedoch nicht verpflichtet, übermittelte oder gespeicherte fremde Informationen zu überwachen. Verpflichtungen zur Entfernung oder Sperrung der Nutzung von Informationen nach den allgemeinen Gesetzen bleiben unberührt.</p>

    <h2>Haftung für Links</h2>
    <p>Unser Angebot enthält Links zu externen Websites Dritter, auf deren Inhalte wir keinen Einfluss haben. Für die Inhalte der verlinkten Seiten ist stets der jeweilige Anbieter verantwortlich.</p>
  </div>
</section>
