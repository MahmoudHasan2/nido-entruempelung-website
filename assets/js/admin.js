/* Nido dashboard behaviour (no dependencies; CSP-safe: no inline handlers). */
(function () {
  'use strict';
  var csrf = (document.querySelector('meta[name="csrf"]') || {}).content || '';

  /* ---- mobile sidebar ---- */
  var side = document.getElementById('side');
  var toggle = document.querySelector('[data-side]');
  if (side && toggle) {
    toggle.addEventListener('click', function () {
      var open = !side.classList.contains('is-open');
      side.classList.toggle('is-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', function (e) {
      if (side.classList.contains('is-open') && !side.contains(e.target) && !toggle.contains(e.target)) {
        side.classList.remove('is-open'); toggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /* ---- confirm dialogs for destructive buttons ---- */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-confirm]');
    if (b && !window.confirm(b.getAttribute('data-confirm'))) e.preventDefault();
  });

  /* ---- bar widths (CSP forbids style attributes in markup) ---- */
  document.querySelectorAll('.bar i[data-w]').forEach(function (i) { i.style.width = i.getAttribute('data-w') + '%'; });

  /* ---- colour picker <-> hex field ---- */
  document.querySelectorAll('[data-color-sync]').forEach(function (picker) {
    var text = document.getElementById(picker.getAttribute('data-color-sync'));
    if (!text) return;
    picker.addEventListener('input', function () { text.value = picker.value.toUpperCase(); });
    text.addEventListener('input', function () { if (/^#[0-9a-fA-F]{6}$/.test(text.value)) picker.value = text.value; });
  });

  /* ---- media picker ---- */
  var dlg = document.getElementById('picker');
  var grid = dlg && dlg.querySelector('[data-picker-grid]');
  var msg = dlg && dlg.querySelector('[data-picker-msg]');
  var target = null;

  function renderItems(items) {
    grid.replaceChildren();
    if (!items.length) { msg.textContent = 'Die Mediathek ist leer. Laden Sie oben ein Bild hoch.'; return; }
    msg.textContent = '';
    items.forEach(function (m) {
      var li = document.createElement('li');
      var b = document.createElement('button');
      b.type = 'button';
      b.title = m.name + (m.alt ? ' – ' + m.alt : '');
      var img = document.createElement('img');
      img.src = m.url; img.alt = m.alt || m.name; img.loading = 'lazy';
      b.appendChild(img);
      b.addEventListener('click', function () { choose(m); });
      li.appendChild(b); grid.appendChild(li);
    });
  }
  function load() {
    msg.textContent = 'Lädt …';
    fetch('/admin/media/json', { credentials: 'same-origin' })
      .then(function (r) { return r.json(); }).then(renderItems)
      .catch(function () { msg.textContent = 'Die Mediathek konnte nicht geladen werden.'; });
  }
  function choose(m) {
    if (!target) return;
    target.querySelector('[data-pick-value]').value = m.id;
    var prev = target.querySelector('[data-pick-prev]');
    prev.classList.remove('is-empty');
    var img = document.createElement('img'); img.src = m.url; img.alt = '';
    prev.replaceChildren(img);
    var clr = target.querySelector('[data-pick-clear]'); if (clr) clr.hidden = false;
    dlg.close();
  }
  if (dlg && grid) {
    document.addEventListener('click', function (e) {
      var pick = e.target.closest('[data-pick]');
      if (pick) { target = pick.closest('[data-imgpick]'); dlg.showModal(); load(); return; }
      var clear = e.target.closest('[data-pick-clear]');
      if (clear) {
        var box = clear.closest('[data-imgpick]');
        box.querySelector('[data-pick-value]').value = '';
        var prev = box.querySelector('[data-pick-prev]');
        prev.classList.add('is-empty');
        var s = document.createElement('span'); s.textContent = 'Kein Bild gewählt';
        prev.replaceChildren(s); clear.hidden = true;
      }
    });
    dlg.querySelector('[data-picker-close]').addEventListener('click', function () { dlg.close(); });
    dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });
    dlg.querySelector('[data-picker-upload]').addEventListener('change', function (e) {
      var f = e.target.files[0]; if (!f) return;
      var fd = new FormData(); fd.append('file', f);
      msg.textContent = 'Wird hochgeladen …';
      fetch('/admin/media/upload', { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-CSRF-Token': csrf } })
        .then(function (r) { return r.json(); })
        .then(function (j) { if (j.ok) { choose({ id: j.id, url: j.url, name: f.name, alt: '' }); } else { msg.textContent = j.error || 'Upload fehlgeschlagen.'; } })
        .catch(function () { msg.textContent = 'Upload fehlgeschlagen.'; });
      e.target.value = '';
    });
  }

  /* ---- sortable lists: drag & drop + up/down buttons ---- */
  document.querySelectorAll('[data-sortable]').forEach(function (list) {
    var table = list.getAttribute('data-sortable');
    var items = function () { return Array.prototype.slice.call(list.children).filter(function (c) { return c.hasAttribute('data-id'); }); };
    var save = function () {
      var fd = new FormData();
      fd.append('table', table);
      items().forEach(function (li) { fd.append('ids[]', li.getAttribute('data-id')); });
      fetch('/admin/reorder', { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-CSRF-Token': csrf } })
        .then(function (r) { return r.json(); }).catch(function () {});
    };
    var dragging = null;
    list.addEventListener('dragstart', function (e) {
      var li = e.target.closest('[data-id]');
      if (!li || e.target.closest('input,textarea,select,button')) { e.preventDefault(); return; }
      dragging = li; li.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move';
      try { e.dataTransfer.setData('text/plain', li.getAttribute('data-id')); } catch (x) {}
    });
    list.addEventListener('dragover', function (e) {
      if (!dragging) return;
      e.preventDefault();
      var over = e.target.closest('[data-id]');
      if (!over || over === dragging) return;
      var r = over.getBoundingClientRect();
      var after = (e.clientY - r.top) > r.height / 2 || (e.clientX - r.left) > r.width / 2 && list.classList.contains('gal-admin');
      list.insertBefore(dragging, after ? over.nextSibling : over);
    });
    list.addEventListener('dragend', function () { if (dragging) { dragging.classList.remove('dragging'); dragging = null; save(); } });
    list.addEventListener('click', function (e) {
      var b = e.target.closest('[data-move]');
      if (!b) return;
      var li = b.closest('[data-id]');
      var dir = parseInt(b.getAttribute('data-move'), 10);
      var sib = dir < 0 ? li.previousElementSibling : li.nextElementSibling;
      if (!sib || !sib.hasAttribute('data-id')) return;
      list.insertBefore(li, dir < 0 ? sib : sib.nextSibling);
      b.focus();
      save();
    });
  });
})();
