<section class="page-head">
  <div class="wrap">
    <nav class="crumbs" aria-label="Brotkrumen"><a href="/">Start</a><span aria-hidden="true">/</span><span aria-current="page">Datenschutz</span></nav>
    <h1>Datenschutzerklärung</h1>
  </div>
</section>
<section class="section legal">
  <div class="wrap prose">
    <h2>1. Verantwortlicher</h2>
    <p><?= e(setting('company_name')) ?>, Inhaber: <?= e(setting('owner_name')) ?><br>
    <?= e(setting('street')) ?>, <?= e(setting('zip') . ' ' . setting('city')) ?>, <?= e(setting('country')) ?><br>
    Telefon: <?= e(setting('phone_display')) ?> · E-Mail: <a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></p>

    <h2>2. Hosting und Server-Logfiles</h2>
    <p>Diese Website wird bei einem externen Hosting-Anbieter (Hostinger) betrieben. Beim Aufruf der Seiten verarbeitet der Webserver technisch notwendige Daten, insbesondere die IP-Adresse, Datum und Uhrzeit, die aufgerufene Seite, die übertragene Datenmenge sowie Browser- und Betriebssysteminformationen. Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an einem sicheren und stabilen Betrieb der Website).</p>

    <h2>3. Kontaktaufnahme</h2>
    <p>Wenn Sie uns über das Kontaktformular, per E-Mail, Telefon oder WhatsApp kontaktieren, verarbeiten wir die von Ihnen mitgeteilten Daten (z. B. Name, E-Mail-Adresse, Telefonnummer, Nachricht), um Ihre Anfrage zu bearbeiten und mit Ihnen in Kontakt zu treten. Rechtsgrundlage ist Art. 6 Abs. 1 lit. b DSGVO (vorvertragliche Maßnahmen) bzw. Art. 6 Abs. 1 lit. f DSGVO (Bearbeitung von Anfragen). Die Daten aus dem Kontaktformular werden in unserer Datenbank gespeichert und per E-Mail an uns übermittelt. Wir speichern sie nur so lange, wie es zur Bearbeitung Ihrer Anfrage erforderlich ist oder gesetzliche Aufbewahrungsfristen bestehen.</p>
    <p>Beim Anklicken des WhatsApp-Links öffnet sich WhatsApp (Anbieter: WhatsApp Ireland Limited). Erst dann werden Daten an den Anbieter übertragen. Bitte beachten Sie die Datenschutzhinweise von WhatsApp.</p>

    <h2 id="karte">4. Karte (Google Maps)</h2>
    <p>Auf der Kontaktseite können Sie eine Karte von Google Maps anzeigen lassen. Die Karte wird erst nach Ihrem Klick auf „Karte laden“ eingebunden. Dabei wird eine Verbindung zu Servern von Google (Google Ireland Limited, Gordon House, Barrow Street, Dublin 4, Irland) hergestellt, und es können Daten wie Ihre IP-Adresse übertragen werden. Rechtsgrundlage ist Ihre Einwilligung nach Art. 6 Abs. 1 lit. a DSGVO, die Sie durch den Klick erteilen. Der Link „Route planen“ öffnet Google Maps in einem neuen Tab; erst dann findet eine Übertragung statt.</p>

    <h2>5. Cookies</h2>
    <p>Diese Website setzt keine Tracking- oder Werbe-Cookies. Beim Aufruf des Kontaktformulars wird ein technisch notwendiges Sitzungs-Cookie gesetzt, das dem Schutz des Formulars vor Missbrauch dient und beim Schließen des Browsers gelöscht wird. Rechtsgrundlage ist § 25 Abs. 2 Nr. 2 TTDSG sowie Art. 6 Abs. 1 lit. f DSGVO.</p>

    <h2>6. Reichweitenmessung</h2>
    <p>Wir zählen die Seitenaufrufe in anonymisierter Form (Datum und aufgerufene Seite). Es werden keine IP-Adressen, keine Cookies und keine Nutzerprofile gespeichert.</p>

    <h2>7. Ihre Rechte</h2>
    <p>Sie haben das Recht auf Auskunft (Art. 15 DSGVO), Berichtigung (Art. 16), Löschung (Art. 17), Einschränkung der Verarbeitung (Art. 18), Datenübertragbarkeit (Art. 20) sowie Widerspruch (Art. 21). Erteilte Einwilligungen können Sie jederzeit mit Wirkung für die Zukunft widerrufen. Zur Ausübung Ihrer Rechte genügt eine E-Mail an <a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a>.</p>

    <h2>8. Beschwerderecht</h2>
    <p>Sie haben das Recht, sich bei einer Datenschutz-Aufsichtsbehörde zu beschweren. Für Unternehmen in Niedersachsen ist dies die Landesbeauftragte für den Datenschutz Niedersachsen.</p>

    <h2>9. Datensicherheit</h2>
    <p>Die Übertragung zwischen Ihrem Browser und dieser Website erfolgt verschlüsselt (TLS/HTTPS).</p>

    <?php if (trim(setting('datenschutz_extra')) !== ''): ?>
      <h2>10. Weitere Hinweise</h2>
      <?= paragraphs(setting('datenschutz_extra')) ?>
    <?php endif; ?>
  </div>
</section>
