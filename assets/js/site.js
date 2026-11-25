/* Nido Entrümpelung – public site behaviour (no dependencies). */
(function () {
  'use strict';

  /* ---- mobile menu ---- */
  var btn = document.querySelector('[data-menu]');
  var nav = document.getElementById('nav');
  if (btn && nav) {
    var setOpen = function (open) {
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      nav.classList.toggle('is-open', open);
    };
    btn.addEventListener('click', function () { setOpen(btn.getAttribute('aria-expanded') !== 'true'); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
    nav.addEventListener('click', function (e) { if (e.target.closest('a')) setOpen(false); });
  }

  /* ---- click-to-load map (privacy: no request to Google before consent) ---- */
  document.querySelectorAll('[data-map]').forEach(function (box) {
    var load = box.querySelector('[data-map-load]');
    if (!load) return;
    load.addEventListener('click', function () {
      var f = document.createElement('iframe');
      f.src = box.getAttribute('data-src');
      f.title = 'Karte: Standort von Nido Entrümpelung';
      f.loading = 'lazy';
      f.referrerPolicy = 'no-referrer-when-downgrade';
      f.setAttribute('allowfullscreen', '');
      box.replaceChildren(f);
    });
  });

  /* ---- lightbox gallery ---- */
  var grid = document.querySelector('[data-lightbox]');
  var dlg = document.getElementById('lightbox');
  if (grid && dlg && typeof dlg.showModal === 'function') {
    var links = Array.prototype.slice.call(grid.querySelectorAll('a'));
    var img = dlg.querySelector('[data-lb-img]');
    var cap = dlg.querySelector('[data-lb-cap]');
    var idx = 0;
    var show = function (i) {
      idx = (i + links.length) % links.length;
      var a = links[idx];
      var t = a.getAttribute('data-title') || '';
      var d = a.getAttribute('data-desc') || '';
      img.src = a.getAttribute('href');
      img.alt = t;
      cap.textContent = [t, d].filter(Boolean).join(' – ');
    };
    links.forEach(function (a, i) {
      a.addEventListener('click', function (e) {
        e.preventDefault();
        show(i);
        dlg.showModal();
      });
    });
    dlg.querySelector('[data-lb-close]').addEventListener('click', function () { dlg.close(); });
    dlg.querySelector('[data-lb-prev]').addEventListener('click', function () { show(idx - 1); });
    dlg.querySelector('[data-lb-next]').addEventListener('click', function () { show(idx + 1); });
    dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });
    dlg.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowLeft') show(idx - 1);
      if (e.key === 'ArrowRight') show(idx + 1);
    });
    dlg.addEventListener('close', function () { img.src = ''; });
  }

  /* ---- contact form: client-side validation (server validates again) ---- */
  var form = document.querySelector('[data-contact-form]');
  if (form) {
    var msg = {
      first_name: 'Bitte geben Sie Ihren Vornamen an.',
      last_name: 'Bitte geben Sie Ihren Nachnamen an.',
      email: 'Bitte geben Sie eine gültige E-Mail-Adresse an.',
      subject: 'Bitte wählen Sie einen Betreff aus.',
      message: 'Bitte beschreiben Sie Ihr Anliegen (mindestens 10 Zeichen).',
      privacy: 'Bitte stimmen Sie der Datenschutzerklärung zu.'
    };
    var setErr = function (el, text) {
      var id = 'err-' + el.name;
      var old = document.getElementById(id);
      if (old) old.remove();
      el.removeAttribute('aria-invalid');
      el.removeAttribute('aria-describedby');
      if (!text) return;
      var p = document.createElement('p');
      p.className = 'field-err';
      p.id = id;
      p.setAttribute('role', 'alert');
      p.textContent = text;
      el.setAttribute('aria-invalid', 'true');
      el.setAttribute('aria-describedby', id);
      el.closest('.field').appendChild(p);
    };
    var check = function (el) {
      var ok = true;
      var v = (el.type === 'checkbox') ? el.checked : el.value.trim();
      if (el.name === 'message') ok = v.length >= 10;
      else if (el.name === 'email') ok = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v);
      else if (el.required) ok = !!v;
      setErr(el, ok ? '' : msg[el.name]);
      return ok;
    };
    form.addEventListener('submit', function (e) {
      var first = null;
      form.querySelectorAll('[required]').forEach(function (el) {
        if (!check(el) && !first) first = el;
      });
      if (first) { e.preventDefault(); first.focus(); }
    });
    form.querySelectorAll('[required]').forEach(function (el) {
      el.addEventListener('blur', function () { if (el.type !== 'checkbox') check(el); });
      el.addEventListener('change', function () { check(el); });
    });
  }

  var focusEl = document.querySelector('[data-focus]');
  if (focusEl) focusEl.focus({ preventScroll: false });
})();
