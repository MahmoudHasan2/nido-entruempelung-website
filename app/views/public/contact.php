<?php
/** @var array $form @var array $errors @var array $subjects @var bool $sent */
$hours = lines(setting('opening_hours'));
$addr = setting('street') . ', ' . setting('zip') . ' ' . setting('city') . ', ' . setting('country');
$mapQuery = rawurlencode($addr);
$err = fn(string $f) => isset($errors[$f]) ? '<p class="field-err" id="err-' . e($f) . '" role="alert">' . e($errors[$f]) . '</p>' : '';
$val = fn(string $f) => e($form[$f] ?? '');
$inv = fn(string $f) => isset($errors[$f]) ? ' aria-invalid="true" aria-describedby="err-' . e($f) . '"' : '';
?>
<section class="page-head">
  <div class="wrap">
    <nav class="crumbs" aria-label="Brotkrumen"><a href="/">Start</a><span aria-hidden="true">/</span><span aria-current="page">Kontakt</span></nav>
    <h1><?= e(setting('contact_h1')) ?></h1>
    <p class="lead"><?= e(setting('contact_intro')) ?></p>
  </div>
</section>

<section class="section contact">
  <div class="wrap contact-grid">
    <div class="contact-info">
      <h2><?= e(setting('contact_lead')) ?></h2>
      <ul class="info-list">
        <li>
          <?= icon('phone', 'info-ic', 26) ?>
          <div><span class="info-label">Telefon</span><a href="<?= e(tel_url()) ?>"><?= e(setting('phone_display')) ?></a></div>
        </li>
        <li>
          <?= icon('mail', 'info-ic', 26) ?>
          <div><span class="info-label">E-Mail</span><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></div>
        </li>
        <li>
          <?= icon('pin', 'info-ic', 26) ?>
          <div><span class="info-label">Standort</span><address><?= e($addr) ?></address></div>
        </li>
        <li>
          <?= icon('user', 'info-ic', 26) ?>
          <div><span class="info-label">Inhaber</span><span><?= e(setting('owner_name')) ?></span></div>
        </li>
        <?php if ($hours): ?>
        <li>
          <?= icon('clock', 'info-ic', 26) ?>
          <div><span class="info-label">Erreichbarkeit</span><?php foreach ($hours as $h): ?><span class="hours"><?= e($h) ?></span><?php endforeach; ?></div>
        </li>
        <?php endif; ?>
      </ul>
      <div class="quick-actions">
        <?= btn('WhatsApp-Nachricht senden', whatsapp_url(), 'whatsapp', 'whatsapp', ' target="_blank" rel="noopener noreferrer"') ?>
        <?= btn('Jetzt anrufen', tel_url(), 'primary', 'phone') ?>
        <?= btn('Nachricht senden', '#formular', 'ghost-dark', 'mail') ?>
      </div>
    </div>

    <div class="form-card" id="formular">
      <h2><?= e(setting('form_title')) ?></h2>
      <?php if ($sent): ?>
        <div class="notice notice-ok" role="status" tabindex="-1" data-focus><?= icon('check', '', 24) ?><p><?= e(setting('form_success')) ?></p></div>
      <?php else: ?>
        <?php if ($errors): ?>
          <div class="notice notice-err" role="alert" tabindex="-1" data-focus>
            <?= icon('x-circle', '', 24) ?>
            <p><?= isset($errors['_form']) ? e($errors['_form']) : 'Bitte prüfen Sie die markierten Felder.' ?></p>
          </div>
        <?php endif; ?>
        <form method="post" action="/kontakt#formular" novalidate data-contact-form>
          <?= csrf_field() ?>
          <div class="hp" aria-hidden="true"><label>Bitte leer lassen <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
          <div class="form-grid">
            <div class="field">
              <label for="first_name">Vorname <span class="req" aria-hidden="true">*</span></label>
              <input id="first_name" name="first_name" type="text" autocomplete="given-name" required maxlength="80" value="<?= $val('first_name') ?>"<?= $inv('first_name') ?>>
              <?= $err('first_name') ?>
            </div>
            <div class="field">
              <label for="last_name">Nachname <span class="req" aria-hidden="true">*</span></label>
              <input id="last_name" name="last_name" type="text" autocomplete="family-name" required maxlength="80" value="<?= $val('last_name') ?>"<?= $inv('last_name') ?>>
              <?= $err('last_name') ?>
            </div>
            <div class="field">
              <label for="email">E-Mail-Adresse <span class="req" aria-hidden="true">*</span></label>
              <input id="email" name="email" type="email" autocomplete="email" required maxlength="160" value="<?= $val('email') ?>"<?= $inv('email') ?>>
              <?= $err('email') ?>
            </div>
            <div class="field">
              <label for="phone">Telefonnummer</label>
              <input id="phone" name="phone" type="tel" autocomplete="tel" maxlength="40" value="<?= $val('phone') ?>"<?= $inv('phone') ?>>
              <?= $err('phone') ?>
            </div>
            <div class="field field-wide">
              <label for="subject">Betreff / Leistung <span class="req" aria-hidden="true">*</span></label>
              <select id="subject" name="subject" required<?= $inv('subject') ?>>
                <option value="">Bitte wählen …</option>
                <?php foreach ($subjects as $s): ?>
                  <option value="<?= e($s) ?>"<?= ($form['subject'] ?? '') === $s ? ' selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
              </select>
              <?= $err('subject') ?>
            </div>
            <div class="field field-wide">
              <label for="message">Ihre Nachricht <span class="req" aria-hidden="true">*</span></label>
              <textarea id="message" name="message" rows="6" required maxlength="5000"<?= $inv('message') ?>><?= $val('message') ?></textarea>
              <?= $err('message') ?>
            </div>
            <div class="field field-wide field-check">
              <label class="check">
                <input type="checkbox" name="privacy" value="1" required<?= !empty($form['privacy']) ? ' checked' : '' ?><?= $inv('privacy') ?>>
                <span>Ich habe die <a href="/datenschutz" target="_blank" rel="noopener">Datenschutzerklärung</a> gelesen und bin damit einverstanden, dass meine Angaben zur Bearbeitung meiner Anfrage verwendet werden. <span class="req" aria-hidden="true">*</span></span>
              </label>
              <?= $err('privacy') ?>
            </div>
          </div>
          <p class="form-note">Felder mit * sind Pflichtfelder.</p>
          <button class="btn btn-primary btn-lg" type="submit"><?= icon('mail', 'btn-ic', 20) ?><span>Nachricht senden</span></button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section map-section" id="karte">
  <div class="wrap">
    <header class="section-head"><h2><?= e(setting('map_title')) ?></h2></header>
    <div class="map-grid">
      <div class="map-box" data-map data-src="https://www.google.com/maps?q=<?= $mapQuery ?>&amp;output=embed&amp;hl=de">
        <div class="map-consent">
          <?= icon('map', '', 44) ?>
          <p><strong><?= e($addr) ?></strong></p>
          <p class="small">Zum Laden der Karte wird eine Verbindung zu Google Maps (Google Ireland Limited) hergestellt. Dabei können Daten, z. B. Ihre IP-Adresse, an Google übertragen werden. Mehr dazu in der <a href="/datenschutz#karte">Datenschutzerklärung</a>.</p>
          <button type="button" class="btn btn-primary" data-map-load><?= icon('map', 'btn-ic', 20) ?><span>Karte laden</span></button>
        </div>
      </div>
      <div class="map-side">
        <h3>Adresse</h3>
        <address><?= e(setting('company_name')) ?><br><?= e(setting('street')) ?><br><?= e(setting('zip') . ' ' . setting('city')) ?><br><?= e(setting('country')) ?></address>
        <p><?= btn('Route planen', 'https://www.google.com/maps/dir/?api=1&destination=' . $mapQuery, 'primary', 'external', ' target="_blank" rel="noopener noreferrer"') ?></p>
        <p class="small">Die Route öffnet sich in einem neuen Tab bei Google Maps.</p>
        <p><strong>Einsatzgebiet:</strong> <?= e(setting('service_area')) ?></p>
      </div>
    </div>
  </div>
</section>
