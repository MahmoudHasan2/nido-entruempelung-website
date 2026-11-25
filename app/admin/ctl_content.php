<?php
declare(strict_types=1);

/** Admin controllers: services, gallery, testimonials, media library, reordering. */

/* =============================================================== services */
function ctl_services(): void
{
    if (is_post()) {
        $id = (int) post('id');
        switch (post('action')) {
            case 'toggle':
                q('UPDATE services SET active = 1 - active, updated_at=? WHERE id=?', [now(), $id]);
                flash('Sichtbarkeit geändert.');
                break;
            case 'delete':
                q('DELETE FROM services WHERE id=?', [$id]);
                flash('Leistung gelöscht.');
                break;
        }
        redirect('/admin/services');
    }
    $rows = q_all('SELECT * FROM services ORDER BY sort,id');
    admin_page('Leistungen', 'services', function () use ($rows) {
        ?>
<p class="lead">Alle Leistungen der Website. Ziehen Sie Einträge per Maus oder nutzen Sie die Pfeile, um die Reihenfolge zu ändern. Deaktivierte Leistungen bleiben gespeichert, werden aber nicht angezeigt.</p>
<div class="toolbar"><a class="btn btn-primary" href="/admin/services/edit"><?= icon('plus', '', 18) ?><span>Leistung hinzufügen</span></a></div>
<ul class="rows" data-sortable="services">
  <?php foreach ($rows as $s): ?>
    <li class="row<?= $s['active'] ? '' : ' is-off' ?>" data-id="<?= (int) $s['id'] ?>" draggable="true">
      <span class="grip" aria-hidden="true">⋮⋮</span>
      <span class="row-ic"><?= icon($s['icon'], '', 24) ?></span>
      <div class="row-main"><strong><?= e($s['title']) ?></strong><small>/leistungen/<?= e($s['slug']) ?><?= $s['active'] ? '' : ' · ausgeblendet' ?></small></div>
      <div class="row-actions">
        <button type="button" class="btn btn-quiet btn-sm" data-move="-1" aria-label="Nach oben"><?= icon('up', '', 16) ?></button>
        <button type="button" class="btn btn-quiet btn-sm" data-move="1" aria-label="Nach unten"><?= icon('down', '', 16) ?></button>
        <a class="btn btn-ghost btn-sm" href="/admin/services/edit?id=<?= (int) $s['id'] ?>"><?= icon('edit', '', 16) ?><span>Bearbeiten</span></a>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="btn btn-quiet btn-sm" type="submit"><?= icon('eye', '', 16) ?><span><?= $s['active'] ? 'Ausblenden' : 'Einblenden' ?></span></button></form>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><input type="hidden" name="action" value="delete"><?= confirm_button('Löschen', 'Leistung „' . $s['title'] . '“ wirklich löschen?') ?></form>
      </div>
    </li>
  <?php endforeach; ?>
</ul>
<?php if (!$rows): ?><div class="empty"><p>Es gibt noch keine Leistungen.</p></div><?php endif;
    });
}

function ctl_service_edit(): void
{
    $id = (int) get('id');
    $s = $id ? q_one('SELECT * FROM services WHERE id=?', [$id]) : null;
    if ($id && !$s) {
        flash('Leistung nicht gefunden.', 'err');
        redirect('/admin/services');
    }
    $s ??= ['id' => 0, 'slug' => '', 'title' => '', 'short' => '', 'description' => '', 'benefits' => '', 'icon' => 'home', 'media_id' => null, 'seo_title' => '', 'seo_description' => '', 'active' => 1];
    $errors = [];
    if (is_post()) {
        $s = array_merge($s, [
            'title' => post('title'), 'short' => post('short'), 'description' => post('description'),
            'benefits' => post('benefits'), 'icon' => post('icon'), 'seo_title' => post('seo_title'),
            'seo_description' => post('seo_description'), 'active' => post('active') === '1' ? 1 : 0,
            'media_id' => ctype_digit(post('media_id')) && post('media_id') !== '' ? (int) post('media_id') : null,
        ]);
        $slugIn = slugify(post('slug') !== '' ? post('slug') : $s['title']);
        if ($s['title'] === '' || mb_strlen($s['title']) > 120) $errors[] = 'Bitte geben Sie einen Titel an (max. 120 Zeichen).';
        if (!isset(service_icon_choices()[$s['icon']])) $errors[] = 'Ungültiges Symbol.';
        if ($s['media_id'] && !media_row($s['media_id'])) $errors[] = 'Das gewählte Bild existiert nicht mehr.';
        if (mb_strlen($s['description']) > 6000 || mb_strlen($s['short']) > 400) $errors[] = 'Ein Text ist zu lang.';
        if (mb_strlen($s['seo_title']) > 120 || mb_strlen($s['seo_description']) > 320) $errors[] = 'SEO-Titel (max. 120) oder SEO-Beschreibung (max. 320) ist zu lang.';
        $dupe = q_one('SELECT id FROM services WHERE slug=? AND id<>?', [$slugIn, (int) $s['id']]);
        if ($dupe) $errors[] = 'Diese Adresse (' . $slugIn . ') wird bereits verwendet.';
        if (in_array($slugIn, ['admin', 'api'], true)) $errors[] = 'Diese Adresse ist reserviert.';
        if (!$errors) {
            $s['slug'] = $slugIn;
            if ($s['id']) {
                q('UPDATE services SET slug=?,title=?,short=?,description=?,benefits=?,icon=?,media_id=?,seo_title=?,seo_description=?,active=?,updated_at=? WHERE id=?',
                    [$s['slug'], $s['title'], $s['short'], $s['description'], $s['benefits'], $s['icon'], $s['media_id'], $s['seo_title'], $s['seo_description'], $s['active'], now(), $s['id']]);
            } else {
                $sort = 1 + (int) q_val('SELECT COALESCE(MAX(sort),0) FROM services');
                q('INSERT INTO services (slug,title,short,description,benefits,icon,media_id,seo_title,seo_description,sort,active,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    [$s['slug'], $s['title'], $s['short'], $s['description'], $s['benefits'], $s['icon'], $s['media_id'], $s['seo_title'], $s['seo_description'], $sort, $s['active'], now(), now()]);
            }
            flash('Leistung gespeichert.');
            redirect('/admin/services');
        }
    }
    admin_page($s['id'] ? 'Leistung bearbeiten' : 'Leistung hinzufügen', 'services', function () use ($s, $errors) {
        if ($errors): ?><div class="flash flash-err" role="alert"><strong>Nicht gespeichert:</strong><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form method="post" class="card">
          <?= csrf_field() ?>
          <div class="form-grid">
            <?= field_input('title', 'text', 'Titel', (string) $s['title'], ['required' => true, 'max' => 120]) ?>
            <?= field_input('slug', 'text', 'Adresse (URL)', (string) $s['slug'], ['help' => 'Wird zu /leistungen/<Adresse>. Leer lassen = automatisch aus dem Titel.']) ?>
            <?= field_input('short', 'textarea', 'Kurzbeschreibung (Karte & Suchergebnis)', (string) $s['short'], ['wide' => true, 'rows' => 2, 'max' => 400]) ?>
            <?= field_input('description', 'textarea', 'Ausführliche Beschreibung', (string) $s['description'], ['wide' => true, 'rows' => 8, 'help' => 'Leerzeile = neuer Absatz.']) ?>
            <?= field_input('benefits', 'textarea', 'Vorteile (ein Punkt pro Zeile)', (string) $s['benefits'], ['wide' => true, 'rows' => 5]) ?>
            <?= field_input('icon', 'select', 'Symbol', (string) $s['icon'], ['options' => service_icon_choices()]) ?>
            <div class="field"><label class="check"><input type="checkbox" name="active" value="1"<?= $s['active'] ? ' checked' : '' ?>><span>Auf der Website anzeigen</span></label></div>
            <?= field_input('media_id', 'image', 'Foto der Leistung (optional)', (string) ($s['media_id'] ?? ''), ['help' => 'Nur echte Fotos der Firma verwenden. Ohne Foto erscheint das Symbol.', 'wide' => true]) ?>
          </div>
          <fieldset class="fs"><legend>Suchmaschinen (optional)</legend>
            <div class="form-grid">
              <?= field_input('seo_title', 'text', 'SEO-Titel', (string) $s['seo_title'], ['max' => 120, 'help' => 'Leer = „<Titel> in Gifhorn | Nido Entrümpelung“.']) ?>
              <?= field_input('seo_description', 'textarea', 'SEO-Beschreibung', (string) $s['seo_description'], ['rows' => 3, 'max' => 320, 'help' => 'Ideal: 120–160 Zeichen.']) ?>
            </div>
          </fieldset>
          <?= form_actions('Leistung speichern', '<a class="btn btn-ghost" href="/admin/services">Abbrechen</a>') ?>
        </form>
        <?php
    });
}

/* ================================================================ gallery */
function ctl_gallery(): void
{
    if (is_post()) {
        $action = post('action');
        switch ($action) {
            case 'upload':
                $cat = ctype_digit(post('category_id')) && post('category_id') !== '' ? (int) post('category_id') : null;
                if ($cat && !q_val('SELECT id FROM gallery_categories WHERE id=?', [$cat])) {
                    $cat = null;
                }
                $files = uploaded_files('files');
                $ok = 0;
                $fail = [];
                $sort = (int) q_val('SELECT COALESCE(MAX(sort),0) FROM gallery_images');
                foreach ($files as $f) {
                    $r = media_store_upload($f, post('alt'));
                    if ($r['ok']) {
                        q('INSERT INTO gallery_images (category_id,media_id,title,description,sort,visible,created_at) VALUES (?,?,?,?,?,1,?)',
                            [$cat, $r['id'], '', '', ++$sort, now()]);
                        $ok++;
                    } else {
                        $fail[] = ($f['name'] ?? 'Datei') . ': ' . $r['error'];
                    }
                }
                if (!$files) {
                    flash('Bitte wählen Sie mindestens ein Bild aus.', 'err');
                } elseif ($fail) {
                    flash($ok . ' Bild(er) hochgeladen. Fehler – ' . implode(' | ', $fail), $ok ? 'warn' : 'err');
                } else {
                    flash($ok . ' Bild' . ($ok > 1 ? 'er' : '') . ' hochgeladen.');
                }
                break;
            case 'add_from_library':
                $mid = (int) post('media_id');
                if (media_row($mid)) {
                    $sort = 1 + (int) q_val('SELECT COALESCE(MAX(sort),0) FROM gallery_images');
                    q('INSERT INTO gallery_images (category_id,media_id,title,description,sort,visible,created_at) VALUES (NULL,?,?,?,?,1,?)', [$mid, '', '', $sort, now()]);
                    flash('Bild zur Galerie hinzugefügt.');
                }
                break;
            case 'update':
                $id = (int) post('id');
                $cat = ctype_digit(post('category_id')) && post('category_id') !== '' ? (int) post('category_id') : null;
                q('UPDATE gallery_images SET title=?, description=?, category_id=?, visible=? WHERE id=?',
                    [mb_substr(post('title'), 0, 160), mb_substr(post('description'), 0, 500), $cat, post('visible') === '1' ? 1 : 0, $id]);
                $mid = (int) q_val('SELECT media_id FROM gallery_images WHERE id=?', [$id]);
                if ($mid && post('alt') !== '') {
                    q('UPDATE media SET alt=? WHERE id=?', [mb_substr(post('alt'), 0, 300), $mid]);
                }
                flash('Bild gespeichert.');
                break;
            case 'delete':
                $id = (int) post('id');
                $mid = (int) q_val('SELECT media_id FROM gallery_images WHERE id=?', [$id]);
                q('DELETE FROM gallery_images WHERE id=?', [$id]);
                if ($mid) {
                    media_delete($mid); // only removes the file if nothing else uses it
                }
                flash('Bild gelöscht.');
                break;
            case 'cat_add':
                $n = mb_substr(post('name'), 0, 60);
                if ($n === '') {
                    flash('Bitte geben Sie einen Namen an.', 'err');
                    break;
                }
                $slug = slugify($n);
                $i = 2;
                $base = $slug;
                while (q_val('SELECT id FROM gallery_categories WHERE slug=?', [$slug])) {
                    $slug = $base . '-' . $i++;
                }
                q('INSERT INTO gallery_categories (name,slug,sort) VALUES (?,?,?)', [$n, $slug, 1 + (int) q_val('SELECT COALESCE(MAX(sort),0) FROM gallery_categories')]);
                flash('Kategorie angelegt.');
                break;
            case 'cat_rename':
                $n = mb_substr(post('name'), 0, 60);
                if ($n !== '') {
                    q('UPDATE gallery_categories SET name=? WHERE id=?', [$n, (int) post('id')]);
                    flash('Kategorie umbenannt.');
                }
                break;
            case 'cat_delete':
                q('DELETE FROM gallery_categories WHERE id=?', [(int) post('id')]);
                flash('Kategorie gelöscht. Die Bilder bleiben ohne Kategorie erhalten.');
                break;
        }
        redirect('/admin/gallery' . (get('kategorie') !== '' ? '?kategorie=' . rawurlencode(get('kategorie')) : ''));
    }

    $cats = q_all('SELECT c.*, (SELECT COUNT(*) FROM gallery_images g WHERE g.category_id=c.id) n FROM gallery_categories c ORDER BY sort,id');
    $filter = get('kategorie');
    $sql = 'SELECT * FROM gallery_images';
    $params = [];
    if ($filter === 'ohne') {
        $sql .= ' WHERE category_id IS NULL';
    } elseif (ctype_digit($filter) && $filter !== '') {
        $sql .= ' WHERE category_id=?';
        $params[] = (int) $filter;
    }
    $images = q_all($sql . ' ORDER BY sort,id', $params);

    admin_page('Galerie', 'gallery', function () use ($cats, $images, $filter) {
        ?>
<p class="lead">Laden Sie hier echte Fotos Ihrer Arbeit hoch. Sie erscheinen auf der Galerie-Seite mit Vergrößerungsansicht. Solange keine Bilder vorhanden sind, bleibt die Galerie leer.</p>

<section class="card">
  <header class="card-head"><h2>Bilder hochladen</h2></header>
  <form method="post" enctype="multipart/form-data" class="form-inline">
    <?= csrf_field() ?><input type="hidden" name="action" value="upload">
    <div class="field"><label for="files">Bilder (JPG, PNG, WebP – mehrere möglich)</label><input id="files" type="file" name="files[]" accept="image/jpeg,image/png,image/webp" multiple required></div>
    <div class="field"><label for="up_cat">Kategorie</label>
      <select id="up_cat" name="category_id"><option value="">Ohne Kategorie</option><?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="up_alt">Bildbeschreibung (Alt-Text, optional)</label><input id="up_alt" name="alt" type="text" maxlength="300"></div>
    <button class="btn btn-primary" type="submit"><?= icon('upload', '', 18) ?><span>Hochladen</span></button>
  </form>
  <form method="post" class="form-inline form-inline-sm">
    <?= csrf_field() ?><input type="hidden" name="action" value="add_from_library">
    <?= image_picker('media_id', null, 'Oder ein Bild aus der Mediathek hinzufügen') ?>
    <button class="btn btn-ghost" type="submit">Zur Galerie hinzufügen</button>
  </form>
</section>

<section class="card">
  <header class="card-head"><h2>Kategorien</h2></header>
  <ul class="cat-list">
    <li><a class="pill-link<?= $filter === '' ? ' is-on' : '' ?>" href="/admin/gallery">Alle (<?= (int) q_val('SELECT COUNT(*) FROM gallery_images') ?>)</a></li>
    <li><a class="pill-link<?= $filter === 'ohne' ? ' is-on' : '' ?>" href="/admin/gallery?kategorie=ohne">Ohne Kategorie</a></li>
    <?php foreach ($cats as $c): ?>
      <li class="cat-item">
        <a class="pill-link<?= (string) $c['id'] === $filter ? ' is-on' : '' ?>" href="/admin/gallery?kategorie=<?= (int) $c['id'] ?>"><?= e($c['name']) ?> (<?= (int) $c['n'] ?>)</a>
        <form method="post" class="cat-edit"><?= csrf_field() ?><input type="hidden" name="action" value="cat_rename"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><input type="text" name="name" value="<?= e($c['name']) ?>" aria-label="Kategorie umbenennen" maxlength="60"><button class="btn btn-quiet btn-sm" type="submit">Umbenennen</button></form>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="cat_delete"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><?= confirm_button('', 'Kategorie „' . $c['name'] . '“ löschen? Die Bilder bleiben erhalten.', 'btn btn-quiet btn-sm', 'trash') ?></form>
      </li>
    <?php endforeach; ?>
  </ul>
  <form method="post" class="form-inline form-inline-sm"><?= csrf_field() ?><input type="hidden" name="action" value="cat_add">
    <div class="field"><label for="cat_new">Neue Kategorie</label><input id="cat_new" name="name" type="text" maxlength="60" required></div>
    <button class="btn btn-ghost" type="submit"><?= icon('plus', '', 18) ?><span>Anlegen</span></button>
  </form>
</section>

<?php if (!$images): ?>
  <div class="empty"><?= icon('image', '', 48) ?><p>Noch keine Bilder in dieser Ansicht.</p></div>
<?php else: ?>
  <ul class="gal-admin" data-sortable="gallery_images">
    <?php foreach ($images as $g): $m = media_row((int) $g['media_id']); if (!$m) continue; ?>
      <li class="gal-item<?= $g['visible'] ? '' : ' is-off' ?>" data-id="<?= (int) $g['id'] ?>" draggable="true">
        <div class="gal-thumb"><img src="<?= e(media_url((int) $g['media_id'], 480)) ?>" alt="<?= e($m['alt']) ?>" loading="lazy"><span class="grip" aria-hidden="true">⋮⋮</span></div>
        <form method="post">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
          <div class="field"><label>Titel<input type="text" name="title" value="<?= e($g['title']) ?>" maxlength="160"></label></div>
          <div class="field"><label>Beschreibung<input type="text" name="description" value="<?= e($g['description']) ?>" maxlength="500"></label></div>
          <div class="field"><label>Alt-Text<input type="text" name="alt" value="<?= e($m['alt']) ?>" maxlength="300"></label></div>
          <div class="field"><label>Kategorie<select name="category_id"><option value="">Ohne</option><?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>"<?= (int) $g['category_id'] === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></label></div>
          <label class="check"><input type="checkbox" name="visible" value="1"<?= $g['visible'] ? ' checked' : '' ?>><span>Sichtbar</span></label>
          <div class="gal-actions">
            <button class="btn btn-primary btn-sm" type="submit" name="action" value="update">Speichern</button>
            <button type="button" class="btn btn-quiet btn-sm" data-move="-1" aria-label="Nach vorne"><?= icon('chevron-left', '', 16) ?></button>
            <button type="button" class="btn btn-quiet btn-sm" data-move="1" aria-label="Nach hinten"><?= icon('chevron-right', '', 16) ?></button>
            <button class="btn btn-danger btn-sm" type="submit" name="action" value="delete" data-confirm="Dieses Bild wirklich löschen?"><?= icon('trash', '', 16) ?></button>
          </div>
        </form>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif;
    });
}

/* ========================================================== testimonials */
function ctl_testimonials(): void
{
    if (is_post()) {
        switch (post('action')) {
            case 'add':
            case 'update':
                $name = mb_substr(post('name'), 0, 80);
                $text = mb_substr(post('text'), 0, 1000);
                if ($name === '' || $text === '') {
                    flash('Name und Text sind Pflichtfelder.', 'err');
                    break;
                }
                if (post('action') === 'add') {
                    q('INSERT INTO testimonials (name,text,place,sort,visible) VALUES (?,?,?,?,?)', [$name, $text, mb_substr(post('place'), 0, 80), 1 + (int) q_val('SELECT COALESCE(MAX(sort),0) FROM testimonials'), post('visible') === '1' ? 1 : 0]);
                } else {
                    q('UPDATE testimonials SET name=?,text=?,place=?,visible=? WHERE id=?', [$name, $text, mb_substr(post('place'), 0, 80), post('visible') === '1' ? 1 : 0, (int) post('id')]);
                }
                flash('Kundenstimme gespeichert.');
                break;
            case 'delete':
                q('DELETE FROM testimonials WHERE id=?', [(int) post('id')]);
                flash('Kundenstimme gelöscht.');
                break;
        }
        redirect('/admin/testimonials');
    }
    $rows = q_all('SELECT * FROM testimonials ORDER BY sort,id');
    admin_page('Kundenstimmen', 'testimonials', function () use ($rows) {
        ?>
<p class="lead">Tragen Sie hier nur echte Bewertungen ein, die Kunden Ihnen erlaubt haben zu veröffentlichen. Solange keine sichtbare Kundenstimme existiert, wird der Bereich auf der Startseite ausgeblendet.</p>
<section class="card">
  <header class="card-head"><h2>Neue Kundenstimme</h2></header>
  <form method="post" class="form-grid"><?= csrf_field() ?><input type="hidden" name="action" value="add">
    <?= field_input('name', 'text', 'Name (z. B. „Familie M.“)', '', ['required' => true, 'max' => 80]) ?>
    <?= field_input('place', 'text', 'Ort (optional)', '', ['max' => 80]) ?>
    <?= field_input('text', 'textarea', 'Text', '', ['required' => true, 'wide' => true, 'rows' => 4, 'max' => 1000]) ?>
    <div class="field"><label class="check"><input type="checkbox" name="visible" value="1" checked><span>Sichtbar</span></label></div>
    <div class="form-actions field-wide"><button class="btn btn-primary" type="submit"><?= icon('plus', '', 18) ?><span>Hinzufügen</span></button></div>
  </form>
</section>
<?php if (!$rows): ?><div class="empty"><p>Noch keine Kundenstimmen.</p></div><?php endif; ?>
<ul class="rows" data-sortable="testimonials">
  <?php foreach ($rows as $t): ?>
    <li class="row row-block<?= $t['visible'] ? '' : ' is-off' ?>" data-id="<?= (int) $t['id'] ?>" draggable="true">
      <form method="post" class="form-grid"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
        <?= field_input('name', 'text', 'Name', $t['name'], ['max' => 80]) ?>
        <?= field_input('place', 'text', 'Ort', $t['place'], ['max' => 80]) ?>
        <?= field_input('text', 'textarea', 'Text', $t['text'], ['wide' => true, 'rows' => 3, 'max' => 1000]) ?>
        <div class="field"><label class="check"><input type="checkbox" name="visible" value="1"<?= $t['visible'] ? ' checked' : '' ?>><span>Sichtbar</span></label></div>
        <div class="form-actions field-wide">
          <button class="btn btn-primary btn-sm" type="submit" name="action" value="update">Speichern</button>
          <button type="button" class="btn btn-quiet btn-sm" data-move="-1" aria-label="Nach oben"><?= icon('up', '', 16) ?></button>
          <button type="button" class="btn btn-quiet btn-sm" data-move="1" aria-label="Nach unten"><?= icon('down', '', 16) ?></button>
          <button class="btn btn-danger btn-sm" type="submit" name="action" value="delete" data-confirm="Diese Kundenstimme löschen?"><?= icon('trash', '', 16) ?><span>Löschen</span></button>
        </div>
      </form>
    </li>
  <?php endforeach; ?>
</ul>
<?php
    });
}

/* =================================================================== media */
function ctl_media(): void
{
    if (is_post()) {
        switch (post('action')) {
            case 'upload':
                $ok = 0;
                $fail = [];
                foreach (uploaded_files('files') as $f) {
                    $r = media_store_upload($f, post('alt'));
                    $r['ok'] ? $ok++ : $fail[] = ($f['name'] ?? 'Datei') . ': ' . $r['error'];
                }
                if ($fail) {
                    flash($ok . ' hochgeladen. Fehler – ' . implode(' | ', $fail), $ok ? 'warn' : 'err');
                } elseif ($ok) {
                    flash($ok . ' Datei' . ($ok > 1 ? 'en' : '') . ' hochgeladen.');
                } else {
                    flash('Bitte wählen Sie eine Datei aus.', 'err');
                }
                break;
            case 'alt':
                q('UPDATE media SET alt=? WHERE id=?', [mb_substr(post('alt'), 0, 300), (int) post('id')]);
                flash('Alt-Text gespeichert.');
                break;
            case 'delete':
                $id = (int) post('id');
                if (media_delete($id)) {
                    flash('Datei gelöscht.');
                } else {
                    flash('Diese Datei wird noch verwendet (' . implode(', ', media_usage($id)) . ') und wurde deshalb nicht gelöscht.', 'err');
                }
                break;
        }
        redirect('/admin/media' . (get('q') !== '' ? '?q=' . rawurlencode(get('q')) : ''));
    }
    $qs = get('q');
    $rows = $qs !== ''
        ? q_all('SELECT * FROM media WHERE orig_name LIKE ? OR alt LIKE ? ORDER BY id DESC LIMIT 200', ['%' . $qs . '%', '%' . $qs . '%'])
        : q_all('SELECT * FROM media ORDER BY id DESC LIMIT 200');
    admin_page('Mediathek', 'media', function () use ($rows, $qs) {
        ?>
<section class="card">
  <header class="card-head"><h2>Hochladen</h2></header>
  <form method="post" enctype="multipart/form-data" class="form-inline"><?= csrf_field() ?><input type="hidden" name="action" value="upload">
    <div class="field"><label for="mfiles">Bilder (JPG, PNG, WebP, max. 12 MB)</label><input id="mfiles" type="file" name="files[]" accept="image/jpeg,image/png,image/webp" multiple required></div>
    <div class="field"><label for="malt">Alt-Text (optional)</label><input id="malt" name="alt" type="text" maxlength="300"></div>
    <button class="btn btn-primary" type="submit"><?= icon('upload', '', 18) ?><span>Hochladen</span></button>
  </form>
</section>
<form method="get" class="toolbar"><label class="sr" for="mq">Suchen</label><input id="mq" type="search" name="q" value="<?= e($qs) ?>" placeholder="Dateiname oder Alt-Text suchen …"><button class="btn btn-ghost" type="submit"><?= icon('search', '', 18) ?><span>Suchen</span></button></form>
<?php if (!$rows): ?><div class="empty"><?= icon('image', '', 48) ?><p>Keine Dateien gefunden.</p></div><?php endif; ?>
<ul class="media-grid">
  <?php foreach ($rows as $m): $use = media_usage((int) $m['id']); ?>
    <li class="media-item">
      <div class="media-thumb"><img src="<?= e(media_url((int) $m['id'], 480)) ?>" alt="<?= e($m['alt']) ?>" loading="lazy"></div>
      <p class="media-meta"><strong><?= e($m['orig_name']) ?></strong><br><?= (int) $m['width'] ?>×<?= (int) $m['height'] ?> px · <?= e(number_format($m['bytes'] / 1024, 0, ',', '.')) ?> KB<br>
        <a href="<?= e(media_url((int) $m['id'])) ?>" target="_blank" rel="noopener">Original ansehen</a></p>
      <p class="media-use"><?= $use ? 'Verwendet: ' . e(implode(', ', $use)) : '<em>Nicht verwendet</em>' ?></p>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
        <label class="sr" for="alt<?= (int) $m['id'] ?>">Alt-Text</label>
        <input id="alt<?= (int) $m['id'] ?>" name="alt" type="text" value="<?= e($m['alt']) ?>" placeholder="Alt-Text (Bildbeschreibung)" maxlength="300">
        <div class="gal-actions">
          <button class="btn btn-ghost btn-sm" type="submit" name="action" value="alt">Alt-Text speichern</button>
          <button class="btn btn-danger btn-sm" type="submit" name="action" value="delete" data-confirm="Datei endgültig löschen?"<?= $use ? ' title="Wird noch verwendet"' : '' ?>><?= icon('trash', '', 16) ?></button>
        </div>
      </form>
    </li>
  <?php endforeach; ?>
</ul>
<?php
    });
}

function ctl_media_json(): void
{
    header('Content-Type: application/json; charset=UTF-8');
    $qs = get('q');
    $rows = $qs !== ''
        ? q_all('SELECT * FROM media WHERE orig_name LIKE ? OR alt LIKE ? ORDER BY id DESC LIMIT 120', ['%' . $qs . '%', '%' . $qs . '%'])
        : q_all('SELECT * FROM media ORDER BY id DESC LIMIT 120');
    echo json_encode(array_map(fn($m) => [
        'id' => (int) $m['id'], 'url' => media_url((int) $m['id'], 480), 'alt' => $m['alt'], 'name' => $m['orig_name'],
    ], $rows), JSON_UNESCAPED_UNICODE);
}

function ctl_media_upload(): void
{
    header('Content-Type: application/json; charset=UTF-8');
    if (!is_post()) {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Nur POST.']);
        return;
    }
    $files = uploaded_files('file');
    if (!$files) {
        echo json_encode(['ok' => false, 'error' => 'Keine Datei empfangen.']);
        return;
    }
    $r = media_store_upload($files[0], post('alt'));
    if (!$r['ok']) {
        http_response_code(422);
        echo json_encode($r);
        return;
    }
    echo json_encode(['ok' => true, 'id' => $r['id'], 'url' => media_url($r['id'], 480)]);
}

/* ================================================================ reorder */
function ctl_reorder(): void
{
    header('Content-Type: application/json; charset=UTF-8');
    $allowed = ['services', 'gallery_images', 'nav_items', 'gallery_categories', 'testimonials'];
    $table = post('table');
    $ids = $_POST['ids'] ?? [];
    if (!is_post() || !in_array($table, $allowed, true) || !is_array($ids)) {
        http_response_code(400);
        echo json_encode(['ok' => false]);
        return;
    }
    $ids = array_values(array_filter(array_map(fn($i) => is_string($i) && ctype_digit($i) ? (int) $i : null, $ids)));
    if (!$ids) {
        echo json_encode(['ok' => true]);
        return;
    }
    $pdo = db();
    // $table is allow-listed above. Re-use the existing sort slots of the given rows so that a
    // reorder inside a filtered view keeps the position of rows that are not part of it.
    $in = implode(',', array_fill(0, count($ids), '?'));
    $slots = array_map('intval', array_column(q_all("SELECT sort FROM $table WHERE id IN ($in)", $ids), 'sort'));
    sort($slots);
    if (count($slots) !== count($ids) || count(array_unique($slots)) !== count($slots)) {
        $slots = range(1, count($ids));
    }
    $pdo->beginTransaction();
    $st = $pdo->prepare("UPDATE $table SET sort=? WHERE id=?");
    foreach ($ids as $i => $id) {
        $st->execute([$slots[$i] ?? ($i + 1), $id]);
    }
    $pdo->commit();
    echo json_encode(['ok' => true]);
}
