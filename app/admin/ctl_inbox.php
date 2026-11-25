<?php
declare(strict_types=1);

/** Admin controllers: inquiries inbox, SEO, navigation. */

/* ============================================================== inquiries */
function ctl_inquiries(): void
{
    if (is_post()) {
        $id = (int) post('id');
        switch (post('action')) {
            case 'status':
                if (isset(status_labels()[post('status')])) {
                    q('UPDATE inquiries SET status=?, is_read=1 WHERE id=?', [post('status'), $id]);
                    flash('Status geändert: ' . status_labels()[post('status')] . '.');
                }
                break;
            case 'read':
                q('UPDATE inquiries SET is_read=1 WHERE id=?', [$id]);
                break;
            case 'unread':
                q('UPDATE inquiries SET is_read=0, status=CASE WHEN status=\'new\' THEN \'new\' ELSE status END WHERE id=?', [$id]);
                flash('Als ungelesen markiert.');
                break;
            case 'delete':
                q('DELETE FROM inquiries WHERE id=?', [$id]);
                flash('Anfrage gelöscht.');
                redirect('/admin/inquiries');
        }
        redirect('/admin/inquiries?id=' . $id);
    }

    $id = (int) get('id');
    if ($id) {
        $r = q_one('SELECT * FROM inquiries WHERE id=?', [$id]);
        if (!$r) {
            flash('Anfrage nicht gefunden.', 'err');
            redirect('/admin/inquiries');
        }
        q('UPDATE inquiries SET is_read=1 WHERE id=?', [$id]);
        admin_page('Anfrage #' . $id, 'inquiries', function () use ($r) {
            $full = $r['first_name'] . ' ' . $r['last_name'];
            $wa = 'https://wa.me/' . preg_replace('/\D+/', '', preg_replace('/^0/', '49', preg_replace('/[^\d+]/', '', $r['phone']) ?? '') ?? '');
            ?>
<p><a href="/admin/inquiries">← Zurück zur Übersicht</a></p>
<div class="grid-2 grid-2-wide">
  <article class="card">
    <header class="card-head"><h2><?= e($r['subject']) ?></h2><span class="pill pill-<?= e($r['status']) ?>"><?= e(status_labels()[$r['status']] ?? $r['status']) ?></span></header>
    <div class="message"><?= nl2br(e($r['message'])) ?></div>
    <p class="muted">Eingegangen am <?= e(format_date($r['created_at'])) ?> · E-Mail-Zustellung:
      <strong><?= ['sent' => 'zugestellt', 'failed' => 'fehlgeschlagen', 'pending' => 'ausstehend'][$r['mail_status']] ?? e($r['mail_status']) ?></strong></p>
  </article>
  <aside class="card">
    <header class="card-head"><h2>Absender</h2></header>
    <dl class="dl">
      <dt>Name</dt><dd><?= e($full) ?></dd>
      <dt>E-Mail</dt><dd><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a></dd>
      <dt>Telefon</dt><dd><?= $r['phone'] !== '' ? '<a href="tel:' . e(preg_replace('/[^\d+]/', '', $r['phone'])) . '">' . e($r['phone']) . '</a>' : '–' ?></dd>
    </dl>
    <div class="quick">
      <a class="btn btn-primary" href="mailto:<?= e($r['email']) ?>?subject=<?= e(rawurlencode('Ihre Anfrage: ' . $r['subject'])) ?>"><?= icon('mail', '', 18) ?><span>Per E-Mail antworten</span></a>
      <?php if ($r['phone'] !== ''): ?>
        <a class="btn btn-ghost" href="tel:<?= e(preg_replace('/[^\d+]/', '', $r['phone'])) ?>"><?= icon('phone', '', 18) ?><span>Anrufen</span></a>
        <a class="btn btn-ghost" href="<?= e($wa) ?>" target="_blank" rel="noopener noreferrer"><?= icon('whatsapp', '', 18) ?><span>WhatsApp</span></a>
      <?php endif; ?>
    </div>
    <form method="post" class="form-stack"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="action" value="status">
      <div class="field"><label for="st">Status ändern</label>
        <select id="st" name="status"><?php foreach (status_labels() as $k => $l): ?><option value="<?= e($k) ?>"<?= $r['status'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
      <button class="btn btn-primary" type="submit">Status speichern</button>
    </form>
    <form method="post" class="form-stack"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="action" value="unread"><button class="btn btn-quiet" type="submit">Als ungelesen markieren</button></form>
    <form method="post" class="form-stack"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="action" value="delete"><?= confirm_button('Anfrage löschen', 'Diese Anfrage endgültig löschen?') ?></form>
  </aside>
</div>
<?php
        });
        return;
    }

    $status = get('status');
    $qs = get('q');
    $page = max(1, (int) get('page', '1'));
    $per = 15;
    $where = [];
    $params = [];
    if (isset(status_labels()[$status])) {
        $where[] = 'status=?';
        $params[] = $status;
    }
    if ($qs !== '') {
        $where[] = '(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ? OR subject LIKE ? OR message LIKE ?)';
        for ($i = 0; $i < 6; $i++) {
            $params[] = '%' . $qs . '%';
        }
    }
    $w = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $total = (int) q_val("SELECT COUNT(*) FROM inquiries$w", $params);
    $rows = q_all("SELECT * FROM inquiries$w ORDER BY id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $params);
    $counts = [];
    foreach (q_all('SELECT status, COUNT(*) n FROM inquiries GROUP BY status') as $c) {
        $counts[$c['status']] = (int) $c['n'];
    }
    admin_page('Anfragen', 'inquiries', function () use ($rows, $total, $page, $per, $status, $qs, $counts) {
        $base = '/admin/inquiries?' . ($qs !== '' ? 'q=' . rawurlencode($qs) . '&' : '');
        ?>
<ul class="tabs" aria-label="Status filtern">
  <li><a href="<?= e($base) ?>" <?= $status === '' ? 'aria-current="true"' : '' ?>>Alle (<?= array_sum($counts) ?>)</a></li>
  <?php foreach (status_labels() as $k => $l): ?><li><a href="<?= e($base . 'status=' . $k) ?>" <?= $status === $k ? 'aria-current="true"' : '' ?>><?= e($l) ?> (<?= (int) ($counts[$k] ?? 0) ?>)</a></li><?php endforeach; ?>
</ul>
<form method="get" class="toolbar"><?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
  <label class="sr" for="iq">Suchen</label><input id="iq" type="search" name="q" value="<?= e($qs) ?>" placeholder="Name, E-Mail, Telefon oder Text suchen …">
  <button class="btn btn-ghost" type="submit"><?= icon('search', '', 18) ?><span>Suchen</span></button></form>
<?php if (!$rows): ?>
  <div class="empty"><?= icon('inbox', '', 48) ?><p><?= $total === 0 && $qs === '' && $status === '' ? 'Noch keine Anfragen. Sobald jemand das Kontaktformular absendet, erscheint die Nachricht hier.' : 'Keine Anfragen für diese Auswahl.' ?></p></div>
<?php else: ?>
  <div class="table-wrap">
  <table class="table">
    <thead><tr><th>Datum</th><th>Name</th><th>Betreff</th><th>Kontakt</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr class="<?= $r['is_read'] ? '' : 'is-unread' ?>">
        <td data-l="Datum"><?= e(format_date($r['created_at'])) ?></td>
        <td data-l="Name"><a href="/admin/inquiries?id=<?= (int) $r['id'] ?>"><strong><?= e($r['first_name'] . ' ' . $r['last_name']) ?></strong></a></td>
        <td data-l="Betreff"><?= e($r['subject']) ?></td>
        <td data-l="Kontakt"><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a><?= $r['phone'] !== '' ? '<br><a href="tel:' . e(preg_replace('/[^\d+]/', '', $r['phone'])) . '">' . e($r['phone']) . '</a>' : '' ?></td>
        <td data-l="Status"><span class="pill pill-<?= e($r['status']) ?>"><?= e(status_labels()[$r['status']] ?? $r['status']) ?></span></td>
        <td><a class="btn btn-ghost btn-sm" href="/admin/inquiries?id=<?= (int) $r['id'] ?>">Öffnen</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php $pages = (int) ceil($total / $per); if ($pages > 1): ?>
    <nav class="pager" aria-label="Seiten"><?php for ($p = 1; $p <= $pages; $p++): ?>
      <a href="<?= e($base . ($status !== '' ? 'status=' . $status . '&' : '') . 'page=' . $p) ?>"<?= $p === $page ? ' aria-current="page"' : '' ?>><?= $p ?></a><?php endfor; ?></nav>
  <?php endif; ?>
<?php endif;
    });
}

/* =================================================================== seo */
function ctl_seo(): void
{
    $pages = [
        'home' => ['Startseite', '/'], 'leistungen' => ['Leistungen', '/leistungen'], 'galerie' => ['Galerie', '/galerie'],
        'kontakt' => ['Kontakt', '/kontakt'], 'impressum' => ['Impressum', '/impressum'], 'datenschutz' => ['Datenschutz', '/datenschutz'],
    ];
    $key = get('page', 'home');
    if (!isset($pages[$key])) {
        redirect('/admin/seo');
    }
    $errors = [];
    if (is_post()) {
        $title = mb_substr(post('title'), 0, 120);
        $desc = mb_substr(post('description'), 0, 320);
        $canon = post('canonical');
        $og = ctype_digit(post('og_media_id')) && post('og_media_id') !== '' ? (int) post('og_media_id') : null;
        if ($canon !== '' && !preg_match('#^(https?://[^\s]+|/[^\s]*)$#', $canon)) {
            $errors[] = 'Die kanonische URL muss mit https:// oder / beginnen.';
        }
        if ($og && !media_row($og)) {
            $errors[] = 'Das gewählte Social-Media-Bild existiert nicht.';
        }
        if (!$errors) {
            q('INSERT INTO page_seo (page_key,title,description,keywords,canonical,og_title,og_description,og_media_id,noindex) VALUES (?,?,?,?,?,?,?,?,?)
               ON CONFLICT(page_key) DO UPDATE SET title=excluded.title, description=excluded.description, keywords=excluded.keywords, canonical=excluded.canonical,
               og_title=excluded.og_title, og_description=excluded.og_description, og_media_id=excluded.og_media_id, noindex=excluded.noindex',
                [$key, $title, $desc, mb_substr(post('keywords'), 0, 300), $canon, mb_substr(post('og_title'), 0, 120), mb_substr(post('og_description'), 0, 320), $og, post('noindex') === '1' ? 1 : 0]);
            flash('SEO-Einstellungen für „' . $pages[$key][0] . '“ gespeichert.');
            redirect('/admin/seo?page=' . $key);
        }
    }
    $row = page_seo_row($key);
    admin_page('SEO', 'seo', function () use ($pages, $key, $row, $errors) {
        ?>
<ul class="tabs" aria-label="Seite wählen">
  <?php foreach ($pages as $k => [$label]): ?><li><a href="/admin/seo?page=<?= e($k) ?>" <?= $k === $key ? 'aria-current="true"' : '' ?>><?= e($label) ?></a></li><?php endforeach; ?>
</ul>
<?php if ($errors): ?><div class="flash flash-err" role="alert"><ul><?php foreach ($errors as $e1): ?><li><?= e($e1) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="card"><?= csrf_field() ?>
  <header class="card-head"><h2><?= e($pages[$key][0]) ?> – Suchmaschinen &amp; soziale Netzwerke</h2><a href="<?= e($pages[$key][1]) ?>" target="_blank" rel="noopener">Seite ansehen</a></header>
  <div class="form-grid">
    <?= field_input('title', 'text', 'SEO-Titel', $row['title'], ['max' => 120, 'wide' => true, 'help' => 'Ideal: bis ca. 60 Zeichen. Er erscheint als Überschrift in Google und im Browser-Tab.']) ?>
    <?= field_input('description', 'textarea', 'Meta-Beschreibung', $row['description'], ['rows' => 3, 'max' => 320, 'wide' => true, 'help' => 'Ideal: 120–160 Zeichen. Beschreibt die Seite in den Suchergebnissen.']) ?>
    <?= field_input('keywords', 'text', 'Schlüsselwörter (optional, kommagetrennt)', $row['keywords'], ['wide' => true, 'help' => 'Google wertet diese kaum noch aus. Nur Begriffe nennen, die wirklich zu Ihren Leistungen passen.']) ?>
    <?= field_input('canonical', 'text', 'Kanonische URL (optional)', $row['canonical'], ['help' => 'Leer = automatisch die Adresse dieser Seite. Nur ändern, wenn Sie wissen warum.']) ?>
    <div class="field"><label class="check"><input type="checkbox" name="noindex" value="1"<?= $row['noindex'] ? ' checked' : '' ?>><span>Diese Seite nicht in Suchmaschinen anzeigen (noindex)</span></label></div>
    <?= field_input('og_title', 'text', 'Titel für soziale Netzwerke (Open Graph)', $row['og_title'], ['max' => 120, 'help' => 'Leer = SEO-Titel.']) ?>
    <?= field_input('og_description', 'textarea', 'Beschreibung für soziale Netzwerke', $row['og_description'], ['rows' => 2, 'max' => 320, 'help' => 'Leer = Meta-Beschreibung.']) ?>
    <?= field_input('og_media_id', 'image', 'Vorschaubild für soziale Netzwerke', (string) ($row['og_media_id'] ?? ''), ['wide' => true, 'help' => 'Empfohlen: 1200×630 Pixel. Alt-Texte pflegen Sie in der Mediathek.']) ?>
  </div>
  <?= form_actions('SEO speichern') ?>
</form>

<div class="grid-2">
  <section class="card">
    <header class="card-head"><h2>Sitemap &amp; robots.txt</h2></header>
    <p>Beide Dateien werden automatisch aus den aktuellen Inhalten erzeugt – neue Leistungen erscheinen sofort in der Sitemap.</p>
    <ul class="links"><li><a href="/sitemap.xml" target="_blank" rel="noopener">/sitemap.xml</a></li><li><a href="/robots.txt" target="_blank" rel="noopener">/robots.txt</a></li></ul>
    <p class="muted">Zusätzliche robots.txt-Zeilen und den Google-Search-Console-Code finden Sie unter „Allgemein“.</p>
  </section>
  <section class="card">
    <header class="card-head"><h2>Strukturierte Daten (JSON-LD)</h2></header>
    <p>Auf jeder Seite werden <strong>Organization</strong>, <strong>LocalBusiness</strong> und <strong>WebSite</strong> ausgegeben, auf Unterseiten zusätzlich <strong>BreadcrumbList</strong> und <strong>Service</strong>. Die Daten stammen aus „Kontakt &amp; Firma“. Es werden keine erfundenen Bewertungen oder Koordinaten ausgegeben.</p>
    <details><summary>Vorschau ansehen</summary><pre class="code"><?= e(json_encode(org_schema(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)) ?></pre></details>
  </section>
</div>
<?php
    });
}

/* ============================================================ navigation */
function ctl_navigation(): void
{
    $areas = ['header' => 'Hauptmenü (Kopfzeile)', 'footer' => 'Fußbereich: Seiten', 'legal' => 'Fußbereich: Rechtliches'];
    $area = get('area', 'header');
    if (!isset($areas[$area])) {
        redirect('/admin/navigation');
    }
    if (is_post()) {
        $id = (int) post('id');
        switch (post('action')) {
            case 'add':
                $label = mb_substr(post('label'), 0, 60);
                $url = safe_nav_url(post('url'));
                if ($label === '' || !$url) {
                    flash('Bitte geben Sie eine Bezeichnung und ein gültiges Ziel an (z. B. /kontakt oder https://…).', 'err');
                    break;
                }
                q('INSERT INTO nav_items (area,label,url,sort,visible) VALUES (?,?,?,?,1)', [$area, $label, $url, 1 + (int) q_val('SELECT COALESCE(MAX(sort),0) FROM nav_items')]);
                flash('Menüpunkt hinzugefügt.');
                break;
            case 'update':
                $label = mb_substr(post('label'), 0, 60);
                $url = safe_nav_url(post('url'));
                if ($label === '' || !$url) {
                    flash('Ungültige Bezeichnung oder ungültiges Ziel.', 'err');
                    break;
                }
                q('UPDATE nav_items SET label=?, url=?, visible=? WHERE id=? AND area=?', [$label, $url, post('visible') === '1' ? 1 : 0, $id, $area]);
                flash('Menüpunkt gespeichert.');
                break;
            case 'delete':
                q('DELETE FROM nav_items WHERE id=? AND area=?', [$id, $area]);
                flash('Menüpunkt gelöscht.');
                break;
        }
        redirect('/admin/navigation?area=' . $area);
    }
    $items = q_all('SELECT * FROM nav_items WHERE area=? ORDER BY sort,id', [$area]);
    admin_page('Navigation', 'navigation', function () use ($areas, $area, $items) {
        ?>
<ul class="tabs" aria-label="Menübereich"><?php foreach ($areas as $k => $l): ?><li><a href="/admin/navigation?area=<?= e($k) ?>" <?= $k === $area ? 'aria-current="true"' : '' ?>><?= e($l) ?></a></li><?php endforeach; ?></ul>
<p class="lead">Ziehen Sie Einträge oder nutzen Sie die Pfeile, um die Reihenfolge zu ändern. Ziele: interne Seiten wie <code>/kontakt</code> oder <code>/leistungen/entruempelung</code>, externe Adressen mit <code>https://</code>.</p>
<ul class="rows" data-sortable="nav_items">
  <?php foreach ($items as $n): ?>
    <li class="row row-block<?= $n['visible'] ? '' : ' is-off' ?>" data-id="<?= (int) $n['id'] ?>" draggable="true">
      <form method="post" class="nav-edit"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
        <span class="grip" aria-hidden="true">⋮⋮</span>
        <div class="field"><label>Bezeichnung<input type="text" name="label" value="<?= e($n['label']) ?>" maxlength="60" required></label></div>
        <div class="field"><label>Ziel<input type="text" name="url" value="<?= e($n['url']) ?>" maxlength="300" required></label></div>
        <label class="check"><input type="checkbox" name="visible" value="1"<?= $n['visible'] ? ' checked' : '' ?>><span>Sichtbar</span></label>
        <div class="gal-actions">
          <button class="btn btn-primary btn-sm" type="submit" name="action" value="update">Speichern</button>
          <button type="button" class="btn btn-quiet btn-sm" data-move="-1" aria-label="Nach oben"><?= icon('up', '', 16) ?></button>
          <button type="button" class="btn btn-quiet btn-sm" data-move="1" aria-label="Nach unten"><?= icon('down', '', 16) ?></button>
          <button class="btn btn-danger btn-sm" type="submit" name="action" value="delete" data-confirm="Diesen Menüpunkt löschen?"><?= icon('trash', '', 16) ?></button>
        </div>
      </form>
    </li>
  <?php endforeach; ?>
</ul>
<section class="card">
  <header class="card-head"><h2>Menüpunkt hinzufügen</h2></header>
  <form method="post" class="form-inline"><?= csrf_field() ?><input type="hidden" name="action" value="add">
    <div class="field"><label for="nl">Bezeichnung</label><input id="nl" name="label" type="text" maxlength="60" required></div>
    <div class="field"><label for="nu">Ziel</label><input id="nu" name="url" type="text" maxlength="300" placeholder="/kontakt" required></div>
    <button class="btn btn-primary" type="submit"><?= icon('plus', '', 18) ?><span>Hinzufügen</span></button>
  </form>
</section>
<?php
    });
}
