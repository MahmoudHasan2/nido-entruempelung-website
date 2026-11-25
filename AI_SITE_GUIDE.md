# AI_SITE_GUIDE — Nido Entrümpelung website

> Operational specification for AI agents. Read this file, then `SITE_MAP.md` and `AI_CHANGE_PROTOCOL.md`,
> before changing anything. Everything here describes what is **actually implemented** (verified by
> `tests/e2e.py`, 109 checks, and Lighthouse 100/100/100/100 on `/`, `/kontakt`).
> Last verified: 2026-11-24. If you change the site, update this file in the same change.

---

## 1. Company identity and business information

| Field | Value (default in `app/lib/content.php`, editable in dashboard → *Kontakt & Firma*) |
|---|---|
| Company | Nido Entrümpelung |
| Owner (Inhaber) | Max Mustermann |
| Address | Musterstraße 1, 38500 Gifhorn, Germany |
| Phone | +49 151 00000000 (href `+4915100000000`) |
| WhatsApp | `https://wa.me/4915100000000` (setting `whatsapp_number` = `4915100000000`) |
| E-mail | info@nido-entruempelung.de |
| Domain | nido-entruempelung.de |
| Service area | Gifhorn & Umgebung; places list: Gifhorn, Wolfsburg, Braunschweig (source: newer flyer `NIDO_Entruempelung_Flyer.pdf`) |
| Language | German (`de`), all public copy is German |
| Tagline (flyer) | "Sauber. Schnell. Zuverlässig." / "Wir schaffen Platz für Neues." |
| Not provided (never invent) | opening hours (hidden until configured), USt-IdNr., Handelsregister, geo-coordinates, ratings/reviews |

Legal pages use only the supplied data: Impressum (§ 5 DDG + "Verantwortlich nach § 55 Abs. 2 RStV: Max Mustermann") and Datenschutz.
**The Datenschutzerklärung is a conventional template matching what the site really does; it is not verified legal advice.** The owner should have it reviewed (see §30).

## 2. Brand identity and original logo location

* **Source of truth for the visual identity: the logo in the flyer.** The flyer is `Flyer.pdf` (2 pages; page 1 holds the logo as vector shapes + text in MyriadPro-Bold).
* Original files: the company flyer (`Flyer.pdf`) is kept private and is not part of the public repository.
* The logo was rebuilt 1:1 from the flyer's vector paths (see `source/extract_logo.py`, output `source/logo-from-flyer.pdf`) and exported to:
  * `assets/img/logo.svg` (dark text, for light backgrounds), `assets/img/logo-light.svg` (white text, for dark backgrounds; used in header/footer/admin)
  * `assets/img/logo.png`, `logo-light.png` (900 px), `mark.svg` (house symbol only)
  * `favicon.ico`, `assets/img/favicon-{48,96,192,512}.png`, `apple-touch-icon.png` (house symbol, orange on charcoal tile), `og-default.jpg` (1200×630)
* Do **not** redraw, recolour or replace the logo with an icon. The owner can upload a different logo in dashboard → *Logo & Design*.
* The flyer's photographs look AI-generated; they are intentionally **not** used on the site as "our work". Real photos are uploaded by the owner (gallery, hero image, service images).

## 3. Colour palette (exact HEX)

Sampled from the flyer vector (`fill` 0.9037/0.46/0.143 and 0.1367/0.1220/0.1253):

| Token (CSS var) | HEX | Use | Setting key |
|---|---|---|---|
| `--c-primary` | `#E77524` | logo orange: buttons, accents, strokes | `color_primary` |
| `--c-primary-dark` | `#A94A0C` | orange text on light backgrounds (≥ 5:1 contrast) | `color_primary_dark` |
| `--c-secondary` | `#231F20` | logo charcoal: header, hero, footer | `color_secondary` |
| `--c-accent` | `#FFB37A` | light orange highlights on dark | `color_accent` |
| `--c-bg` | `#F4F3F1` | page background | `color_bg` |
| `--c-surface` | `#FFFFFF` | cards | `color_surface` |
| `--c-text` | `#231F20` | body text | `color_text` |

Fixed (not editable) greys live in `assets/css/site.css` `:root`: `--c-line #DEDAD4`, `--c-muted #5E5955`, `--c-ink-soft #3A3534`. Buttons use charcoal text on orange for contrast.
Brand values are injected per request by `brand_css()` (`app/lib/content.php`) into a nonce'd `<style>` in the layout.

## 4. Typography and design system

* Display: **Barlow Condensed** 600/700/800 (uppercase headings, echoes the flyer's condensed caps). Body: **Barlow** 400–700. Both SIL OFL, self-hosted in `assets/fonts/*.woff2` (no Google Fonts request → GDPR-friendly). Licence: `assets/fonts/LICENSE-Barlow-OFL.txt`.
* Settings: `font_display` (barlow-condensed | barlow | system), `font_body` (barlow | system), `btn_style` (angled | square | rounded | pill).
* Signature motif: diagonal/angled cuts (`clip-path`) taken from the flyer's slanted shapes; one orchestrated page-load "sweep" in the hero (CSS only, disabled with `prefers-reduced-motion`).
* Layout: `.wrap` max 1180 px; sections `.section` with `--space-section`; mobile-first breakpoints 1000 / 900 / 640 px; sticky mobile action bar (Anrufen / WhatsApp / Anfrage) below 900 px.
* Components (all in `assets/css/site.css`): `.btn` (+`-primary/-dark/-light/-ghost/-ghost-dark/-whatsapp`), `.svc` cards (bento grid), `.steps`, `.checks`, `.chips`, `.cta-band`, `.page-head`, `.form-card`, `.gal-grid`, `.lightbox`, `.map-box`.
* Accessibility: skip link, visible focus ring, aria on menu/lightbox/forms, reduced-motion respected, contrast checked. Lighthouse a11y 100.

## 5. Website architecture

Server-rendered PHP, no framework, no Composer, no build step.

```
index.php                     front controller (public routes + contact handler)
.htaccess                     rewrite everything non-file to index.php; blocks app/ storage/ source/ docs/ tests/ .env *.sqlite *.md
favicon.ico
app/
  bootstrap.php               env, error handling, security headers, session, CSRF, PDO, helpers; runs migrate()
  lib/schema.php              migrations (+ seed data: admin user, services, nav, SEO)  ← DB changes go here
  lib/content.php             REGISTRY of every editable setting + setting() + brand_css()
  lib/icons.php               inline SVG icon set
  lib/media.php               secure upload (GD re-encode), variants, media_img(), usage/delete
  lib/mail.php                send_mail(): SMTP (STARTTLS/SSL) or PHP mail()
  lib/auth.php                login/lockout/password policy
  lib/seo.php                 per-page meta, JSON-LD, sitemap.xml, robots.txt
  lib/view.php                render_public(), logo_img(), btn(), paragraphs(), page counter
  views/public/*.php          templates (layout, home, services, service, gallery, contact, impressum, datenschutz, notfound)
  admin/router.php            /admin dispatcher (auth + CSRF gate)
  admin/ui.php                admin layout + form widgets (field_input, image_picker)
  admin/ctl_core.php          login, dashboard, password, generic settings forms
  admin/ctl_content.php       services, gallery, testimonials, media library, reorder
  admin/ctl_inbox.php         inquiries, SEO, navigation
assets/css/site.css admin.css | assets/js/site.js admin.js | assets/img | assets/fonts
uploads/                      media library files (writable, PHP execution disabled)
storage/                      nido.sqlite, sessions/, php-error.log, mail.log (private)
source/                       original artwork + logo extraction script (private)
docs/ tests/                  deployment notes, e2e test (private)
AI_SITE_GUIDE.md SITE_MAP.md AI_CHANGE_PROTOCOL.md README.md .env.example
```

Request flow: web server → `index.php` → `app/bootstrap.php` → router `switch` → `render_public(template, meta, data)` → `layout.php`. `/admin/*` is handed to `app/admin/router.php`.

## 6. Complete page structure
See `SITE_MAP.md`. Public: `/`, `/leistungen`, `/leistungen/{slug}`, `/galerie`, `/kontakt`, `/impressum`, `/datenschutz`, `/sitemap.xml`, `/robots.txt`, 404.

## 7. Website sections
* **Header**: logo (light variant), nav from `nav_items(area=header)`, phone link, hamburger on mobile. **Footer**: logo, text, nav (`footer`), contact list, social (only filled URLs), legal links (`legal`), copyright (`{year}` placeholder).
* **Home** (`views/public/home.php`): hero (headline, text, 2 CTAs, badge; graphic house mark or optional uploaded hero photo) → trust bar (4 items) → services bento (7 services + "Besichtigung" offer card) → process (4 steps "Wir räumen. Wir transportieren. Wir übergeben. Sie entspannen.") → why/about card → gallery preview (**only if visible images exist**) → testimonials (**only if visible entries exist**) → service area chips → CTA band.
* **Leistungen**: page head + alternating rows per service (image or icon art, text, benefits, CTA); **detail page per service** with sidebar. **Galerie**: filter by category, grid, lightbox (`<dialog>`), empty state. **Kontakt**: info block + 3 actions (WhatsApp, Anrufen, Nachricht), form, click-to-load Google map, address/route. **Impressum / Datenschutz**: static text driven by settings.

## 8. Frontend technology stack
PHP templates, vanilla CSS (`site.css`) and JS (`site.js`: menu, map consent loader, lightbox, form validation), self-hosted fonts, inline SVG icons. No frameworks, no CDN, no cookies except the technical session cookie `nido_sid` (set on `/kontakt` and `/admin`). CSP: `default-src 'self'`; scripts/styles only `'self'` + per-request nonce; `frame-src https://www.google.com https://maps.google.com`; **no inline style attributes or inline event handlers anywhere** (set widths via JS, use classes).

## 9. Backend technology stack
PHP ≥ 8.1 (developed/tested on 8.3). Required extensions: `pdo_sqlite`, `gd` (jpeg, png, webp), `mbstring`, `fileinfo`; optional `exif` (photo rotation). No third-party PHP packages.

## 10. Database architecture
SQLite file `storage/nido.sqlite` (override `DB_PATH`). WAL mode, foreign keys ON. Schema version = `PRAGMA user_version` (currently **1**). Migrations: `migrate()` in `app/lib/schema.php`, executed on every request (cheap version check).

## 11. Database tables and relationships

| Table | Purpose / key columns |
|---|---|
| `users` | `username` UNIQUE, `password_hash` (Argon2id, fallback bcrypt), `must_change` (1 until first password change), `last_login` |
| `login_attempts` | `ip`, `username`, `created_at` (unix) – brute-force limiter |
| `settings` | `key` PK, `value`, `updated_at` – all editable text/colours/image ids (see §15) |
| `media` | `file` (random hex base), `ext` (jpg/png/webp), `alt`, `width`, `height`, `bytes`, `variants` (e.g. `webp:480,960,1600`) |
| `services` | `slug` UNIQUE, `title`, `short`, `description` (blank line = paragraph), `benefits` (one per line), `icon`, `media_id`→media (SET NULL), `seo_title`, `seo_description`, `sort`, `active` |
| `gallery_categories` | `name`, `slug` UNIQUE, `sort` |
| `gallery_images` | `category_id`→categories (SET NULL), `media_id`→media (CASCADE), `title`, `description`, `sort`, `visible` |
| `inquiries` | contact form submissions: names, `email`, `phone`, `subject`, `message`, `status` (new/progress/done/archived), `is_read`, `mail_status` (pending/sent/failed), `ip`, `created_at` |
| `inquiry_attempts` | `ip`, `created_at` – form rate limiter (5/hour/IP) |
| `nav_items` | `area` (header/footer/legal), `label`, `url`, `sort`, `visible` |
| `page_seo` | `page_key` PK (home, leistungen, galerie, kontakt, impressum, datenschutz), `title`, `description`, `keywords`, `canonical`, `og_title`, `og_description`, `og_media_id`→media, `noindex` |
| `testimonials` | `name`, `text`, `place`, `sort`, `visible` |
| `page_views` | (`day`,`path`) PK, `views` – anonymous counter (no IP, no cookie) |

Per-service SEO lives in `services.seo_*` (page key `service:{slug}` is only used for `resolve_meta`, not stored in `page_seo`).

## 12. API endpoints

There is no public REST API. HTTP surface:

| Method | Path | Purpose | Auth |
|---|---|---|---|
| GET | `/`, `/leistungen`, `/leistungen/{slug}`, `/galerie[?kategorie=slug]`, `/kontakt[?leistung=slug][&gesendet=1]`, `/impressum`, `/datenschutz` | pages | – |
| POST | `/kontakt` | submit inquiry (CSRF, honeypot `website`, ≥3 s time trap, rate limit, validation) | – |
| GET | `/sitemap.xml`, `/robots.txt` | generated | – |
| GET/POST | `/admin/login`, POST `/admin/logout` | session | – / session |
| GET/POST | `/admin`, `/admin/password`, `/admin/settings?g={company\|brand\|home\|pages\|footer\|seo}`, `/admin/services`, `/admin/services/edit[?id=]`, `/admin/gallery`, `/admin/testimonials`, `/admin/media`, `/admin/inquiries[?id=]`, `/admin/seo[?page=]`, `/admin/navigation[?area=]` | dashboard modules (HTML forms) | session + CSRF on POST |
| GET | `/admin/media/json` | media list for picker | session |
| POST | `/admin/media/upload` | single image upload (JSON), header `X-CSRF-Token` | session + CSRF |
| POST | `/admin/reorder` | `table` (allow-list: services, gallery_images, nav_items, gallery_categories, testimonials) + `ids[]` | session + CSRF |

## 13. Authentication architecture
* Initial account: **admin / ChangeMe-2026!** (hash created at first DB creation; override with `ADMIN_INITIAL_PASSWORD` *before* the first request). `must_change=1` → every admin route redirects to `/admin/password` until changed. New password: ≥ 10 chars, not the initial/obvious values, not containing the username, must differ from the old one.
* Sessions: cookie `nido_sid` (HttpOnly, SameSite=Lax, Secure on HTTPS), `session_regenerate_id` on login/password change, 2 h idle timeout, logout destroys the session. Session files in `storage/sessions/`.
* CSRF token per session on every admin POST and on the contact form (`_csrf` field or `X-CSRF-Token`).
* Lockout: 5 failed logins per IP (or 10 per username) in 15 min → HTTP 429 with generic message. Timing-safe (dummy hash on unknown user).
* Admin responses send `Cache-Control: no-store` and `X-Robots-Tag: noindex`.

## 14. Admin dashboard structure
Sidebar (`app/admin/ui.php::admin_menu()`): Übersicht · Anfragen · Startseite · Seitentexte · Leistungen · Galerie · Kundenstimmen · Logo & Design · Kontakt & Firma · Navigation · Fußbereich · SEO · Allgemein · Medien · Passwort. Full tree in `SITE_MAP.md`.

## 15. Every editable website setting
All keys are declared in `registry()` in `app/lib/content.php` (key → type, German label, default, help, options). The dashboard forms are generated from it; templates call `setting('key')`.
Groups: **company** (names, phone, whatsapp, email, address, service area, area_list, opening_hours, social_*), **brand** (logo, logo_light, favicon, 7 colours, fonts, btn_style), **home** (hero_*, trust1-4_*, services_*, step1-4_*, why_*, about_*, gallery_home_title, testimonials_title, area_*, cta_*), **pages** (services_*, gallery_*, contact_*, form_*, map_title, impressum_extra, datenschutz_extra), **footer** (footer_logo, footer_text, footer_copyright), **seo** (site_title, site_description, default_lang, default_og_image, schema_price_range, robots_extra, head_note = Search Console verification).
Other editable data (own tables): services, gallery, categories, testimonials, navigation, per-page SEO, media alt texts.

## 16. Where content is stored
`storage/nido.sqlite`: table `settings` (key/value), plus the tables of §11. Defaults live in code (`registry()`, `default_services()`, `default_page_seo()` in `schema.php`) and apply when a key has no DB row.

## 17. Where images and media are stored
`uploads/{hex}.{jpg|png|webp}` (re-encoded original, max 2400 px long edge, EXIF stripped) and `uploads/{hex}-{480|960|1600}.{webp|jpg|png}` variants; metadata in table `media`. Rendered with `media_img()` (srcset, width/height, lazy). Brand assets shipped with the code are in `assets/img/`.

## 18. How to add, edit and remove services
Dashboard → *Leistungen*: add / edit (title, slug, short, description, benefits, icon, photo, SEO, active) / hide / delete / reorder. Public effects are immediate: list, detail page `/leistungen/{slug}`, contact-form subject dropdown, sitemap, JSON-LD (`makesOffer`, `ItemList`, `Service`).
By code: edit `default_services()` only affects **new** databases; for an existing DB write a migration (see `AI_CHANGE_PROTOCOL.md`).
The 7 services come from the flyer: Entrümpelung · Haushaltsauflösungen · Keller- & Garagenräumungen · Sperrmüllentsorgung · Entrümpelung von Gewerbeobjekten · Besenreine Übergabe · Firmenauflösungen.

## 19. How to add, edit and remove gallery images
Dashboard → *Galerie*: multi-upload (+category, alt), edit title/description/alt/category/visibility, reorder (drag or arrows), delete (removes the file when no other module uses it), categories CRUD. A gallery with zero visible images shows an empty state, is omitted from the home page and from the sitemap.

## 20. How to modify colours and typography
Dashboard → *Logo & Design* (colour pickers + hex, font selects, button style). Code: defaults in `registry()['brand']`; CSS reads `--c-*`/`--f-*` from `brand_css()`. Never hard-code brand colours in templates; fixed neutrals are in `site.css :root`.

## 21. How contact forms work
`views/public/contact.php` + `handle_contact()` in `index.php`. Fields: Vorname*, Nachname*, E-Mail*, Telefon, Betreff* (select = active services + "Allgemeine Anfrage"), Nachricht* (10–5000 chars, max 2 links), Datenschutz checkbox*. Client validation in `site.js`; server validation authoritative. Anti-spam: CSRF, honeypot, ≥ 3 s time trap (`$_SESSION['form_t']`), 5/hour/IP, link limit. On success: row in `inquiries`, mail to `MAIL_TO`, redirect `/kontakt?gesendet=1`. The inquiry is saved **even if mail fails** (`mail_status=failed`, dashboard warns).

## 22. How e-mail delivery is configured
`app/lib/mail.php::send_mail()`. If `SMTP_HOST` is set → built-in SMTP client (STARTTLS on 587 or SSL on 465, AUTH LOGIN, peer verification). Otherwise PHP `mail()` (works on Hostinger with a sender on the hosted domain). `From` = `MAIL_FROM` (use an address on the sending domain), `Reply-To` = the visitor. Failures are written to `storage/mail.log`. To use SMTP create a mailbox in hPanel and put host/user/password in `.env` (never commit it).
**Not verifiable offline:** real delivery to info@nido-entruempelung.de needs a mail-enabled server; test with `/kontakt` after deployment and check the dashboard badge.

## 23. SEO architecture
`resolve_meta()` in `seo.php` merges `page_seo` (dashboard) over template defaults: title, description, keywords, canonical, robots (`noindex`), Open Graph/Twitter (image from page → default → `assets/img/og-default.jpg`). JSON-LD `@graph` on every page: `Organization`, `LocalBusiness` (address, telephone, email, `areaServed`, `makesOffer`; **no** geo/rating/hours unless provided), `WebSite`; subpages add `BreadcrumbList`; service pages add `Service`; list adds `ItemList`. One `<h1>` per page, semantic landmarks, descriptive alts, clean URLs, internal links (nav, cards, breadcrumbs, CTAs). Target terms (only real services): Entrümpelung Gifhorn, Haushaltsauflösung Gifhorn, Wohnungsauflösung, Kellerentrümpelung, Entrümpelungsservice Gifhorn. No ranking promises.

## 24. Sitemap and robots configuration
`/sitemap.xml` is generated live (home, leistungen, active service pages, galerie if images exist, kontakt, legal pages; pages with `noindex` are skipped; `lastmod` from DB). `/robots.txt`: `Allow: /`, `Disallow: /admin`, `/app/`, `/storage/`, sitemap URL, plus dashboard setting `robots_extra`. Submit the sitemap in Google Search Console (`head_note` stores the verification meta code).

## 25. Deployment architecture
Target: Hostinger (Cloud Startup, LiteSpeed/Apache + PHP ≥ 8.1) – upload the project **contents** into the site's `public_html`. `.htaccess` does the routing and protection. First request creates `storage/nido.sqlite` and the admin user. Details and DNS cut-over: `docs/DEPLOYMENT.md`.
**Packaging:** Hostinger's importer rejects `.py` files, so `tests/` and `source/extract_logo.py` stay out of the deployment zip (they live only in the local project).
**Current state (2026-11-24):** the domain `nido-entruempelung.de` still serves an older WordPress site from another provider (nameservers kasserver.com). The new site was deployed to a Hostinger temporary domain for review; DNS was **not** changed.

## 26. Environment variables (`.env`, template `.env.example`)
`APP_ENV` (production|development|testing), `SITE_URL`, `MAIL_TO`, `MAIL_FROM`, `MAIL_FROM_NAME`, `SMTP_HOST`, `SMTP_PORT`, `SMTP_SECURE` (tls|ssl|none), `SMTP_USER`, `SMTP_PASS`, `ADMIN_INITIAL_PASSWORD`, `DB_PATH`, `TRUST_PROXY` (1 = use X-Forwarded-For for client IP). Real environment variables override `.env`. `.env` is blocked from the web and git-ignored.

## 27. Installation instructions
1. Copy the project to the document root. 2. Ensure PHP ≥ 8.1 with `pdo_sqlite gd mbstring fileinfo`. 3. Make `storage/` and `uploads/` writable by the PHP user. 4. Optionally create `.env`. 5. Open the site; log in at `/admin` with `admin` / `ChangeMe-2026!`; set a new password. 6. Fill *Kontakt & Firma* (opening hours optional), upload real gallery photos, set SEO, submit sitemap.

## 28. Build and development commands
No build. Local dev (Docker, PHP 8.3 + GD; Dockerfile used: `FROM php:8.3-cli` + `docker-php-ext-install gd` with jpeg/webp):
```bash
docker build -t nido-php .        # from a Dockerfile with the extensions above
docker run --rm -p 8080:8080 -v "$PWD:/app" -w /app -e APP_ENV=development -e DB_PATH=/tmp/nido.sqlite nido-php php -S 0.0.0.0:8080 index.php
```
(The built-in server uses `index.php` as router and serves only `/assets`, `/uploads`, `/favicon.ico` statically.) Lint: `php -l` on every `*.php`.

## 29. Testing procedures
`python tests/e2e.py http://localhost:8080` against a **fresh** DB (needs `pip install requests pillow`). It covers public pages/blocked paths/security headers, contact form (time trap, validation, CSRF, honeypot), login/lockout/forced password change, settings and brand changes, media upload (incl. refusing a `.php` upload), logo swap, services CRUD + reorder, gallery, inquiries, SEO/noindex, navigation (rejects `javascript:`), testimonials, XSS escaping, logout. Expected: `109 passed, 0 failed`. Also run Lighthouse (mobile) on `/`, `/kontakt`. Live check on the Hostinger temporary domain: Lighthouse mobile 100/100/100/100, LCP 333 ms, CLS 0.00; protected paths return 403/404. Not covered: real e-mail delivery, Google Maps iframe, real photos.

## 30. Security considerations
* Passwords: Argon2id; forced change; lockout; generic errors. Sessions as in §13. CSRF everywhere.
* SQL: PDO prepared statements only (table names for `reorder` come from an allow-list). Output: `e()` escaping everywhere; JSON-LD encoded with `JSON_HEX_*`.
* Uploads: finfo + `getimagesize` + GD re-encode, random names, size/dimension limits, `uploads/.htaccess` disables PHP execution, SVG uploads are not accepted.
* Headers: CSP with nonce, `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS on HTTPS.
* Private files blocked by `.htaccess` + per-folder `Require all denied` (`app/`, `storage/`, `source/`, `docs/`) and by code (`bootstrap.php` recreates `storage/.htaccess`, `uploads/.htaccess`). **Verify after deployment** that `/storage/nido.sqlite` and `/.env` return 404/403.
* Privacy: Google Maps only after click; anonymous page counter; no tracking.
* Open items for the owner: review Datenschutz/Impressum legally; add USt-IdNr. in *Seitentexte → Impressum* if applicable; set up a real mailbox/SMTP; change the initial password immediately.

## 31. Backup and restoration
Back up: `storage/nido.sqlite` (use `sqlite3 storage/nido.sqlite ".backup backup.sqlite"` or copy while idle; ignore `-wal/-shm` after a clean stop), the whole `uploads/` folder, and `.env`. Restore: put the files back at the same paths, ensure write permissions. Code is reproducible from the project folder. A schema migration is applied automatically on the next request.

## 32. Important file and directory locations
Editable-setting registry `app/lib/content.php` · migrations/seed `app/lib/schema.php` · public routes `index.php` · templates `app/views/public/` · admin `app/admin/` · styles `assets/css/` · scripts `assets/js/` · brand assets `assets/img/` + `source/` · DB `storage/nido.sqlite` · uploads `uploads/` · logs `storage/php-error.log`, `storage/mail.log` · tests `tests/e2e.py` · deployment `docs/DEPLOYMENT.md`.
