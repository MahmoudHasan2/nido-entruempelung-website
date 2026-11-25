# AI_CHANGE_PROTOCOL — how to change the Nido website safely

Audience: future AI agents (and humans). You have no memory of earlier conversations. Read `AI_SITE_GUIDE.md`
and `SITE_MAP.md` first. This protocol is mandatory for every change.

## 0. Golden rules
1. **Content first, code second.** If the owner can do it in the dashboard (`/admin`), prefer data/registry changes over template edits.
2. **Never destroy data.** `storage/nido.sqlite` and `uploads/` are the owner's content. Never delete, reset or re-seed them. Never run a command that drops tables, truncates, or removes `uploads/*`. Work on a copy for experiments.
3. **Never invent facts** (services, prices, opening hours, reviews, certifications, coordinates, legal data). Use only what the owner supplied or `AI_SITE_GUIDE.md` §1 lists. Unknown = leave empty / hide.
4. **Keep the brand.** Colours/logo come from the flyer logo (`AI_SITE_GUIDE.md` §2–3). Don't introduce arbitrary colours or replace the logo.
5. **Don't weaken security** (§9 below). No inline scripts/styles (CSP), no SVG uploads, no new public file-writing endpoints without CSRF + auth.
6. **Update the docs in the same change** (§10).

## 1. Understand before editing
1. Read `AI_SITE_GUIDE.md` (§5 architecture, §11 tables, §15 settings) and `SITE_MAP.md`.
2. `grep` for the visible German text you want to change: it is either a **setting default** in `app/lib/content.php` (`registry()`), a **service** default in `app/lib/schema.php`, or literal text in `app/views/public/*.php`.
3. Check the live value first: settings may differ from defaults. Query: `sqlite3 storage/nido.sqlite "select key,value from settings"` (read-only!).

## 2. Locating editable content
| You want to change | Where |
|---|---|
| A heading/paragraph/button label | `registry()` key → `setting('key')` in the template; owner edits it in dashboard (Startseite / Seitentexte / Fußbereich) |
| Contact data, WhatsApp, hours, social | registry group `company` |
| Colours, fonts, button style, logo, favicon | registry group `brand` |
| Services (text, icon, order, SEO) | table `services` (dashboard → Leistungen) |
| Gallery / categories / testimonials | tables `gallery_*`, `testimonials` |
| Menus | table `nav_items` |
| SEO per page | table `page_seo`, services `seo_*`, registry group `seo` |
| Legal page body text | `app/views/public/impressum.php`, `datenschutz.php` (+ editable extras) |
| Layout/spacing/animation | `assets/css/site.css` |

## 3. Reusable components
Use the helpers instead of new markup: `btn()`, `icon()`, `logo_img()`, `media_img()`, `paragraphs()`, `field_input()`/`image_picker()` (admin), CSS classes listed in `AI_SITE_GUIDE.md` §4. New icon → add its path to `app/lib/icons.php`.

## 4. Safely modifying design
* Change tokens, not literals: `--c-*`, `--f-*` in `brand_css()`; fixed neutrals in `site.css :root`.
* Keep WCAG AA contrast (orange text on light must use `--c-primary-dark`; buttons use charcoal text).
* Respect `prefers-reduced-motion`; keep one deliberate motion moment (hero sweep), don't add scroll-reveal everywhere.
* Test at 360 px, 768 px, 1280 px. Lighthouse (mobile) must stay 100 in Accessibility/Best Practices/SEO.
* No `style="…"` attributes, no `onclick=`, no inline `<script>` (CSP will block them). Dynamic values: CSS variable in the nonce'd `<style>` or set via JS.

## 5. Adding a new page
1. Template `app/views/public/{name}.php` (content only; `layout.php` wraps it).
2. Route: add a `case` in `index.php`: `render_public('{name}', ['key'=>'{key}','path'=>'/{path}','title'=>…,'description'=>…,'breadcrumbs'=>[…]], $data)`.
3. SEO: add `{key}` row in `default_page_seo()` (new DB needs a migration to insert it into an existing DB) and in the `$pages` list of `ctl_seo()` (`app/admin/ctl_inbox.php`). Add to `sitemap_xml()` in `app/lib/seo.php`.
4. Navigation: dashboard → Navigation, or seed in the migration.
5. Editable texts: add keys to `registry()` (never hard-code German copy that the owner may want to change).
6. Extend `tests/e2e.py` and both docs (`SITE_MAP.md` diagrams too).

## 6. Modifying existing sections
Edit the template (`views/public/*.php`) and its CSS block. Keep all `setting()` keys that exist; if you remove a key from a template, keep it in `registry()` unless you also provide a migration that deletes the row. Keep the "render only if data exists" behaviour for gallery/testimonials/hours.

## 7. Updating the database schema
1. **Never edit** an existing migration. Append a new numbered entry to `$migrations` in `app/lib/schema.php` (e.g. `2 => function (PDO $pdo) { … }`). It runs once per database, inside a transaction; `PRAGMA user_version` is updated automatically.
2. Make migrations additive (`ALTER TABLE … ADD COLUMN`, new tables, `INSERT … ON CONFLICT DO NOTHING`). Provide defaults so old rows stay valid.
3. For data that must exist on **new and old** databases (e.g. a new `page_seo` row), put the insert into the new migration, not only into the seed.
4. Back up the DB first (`AI_SITE_GUIDE.md` §31). Test on a copy with existing data **and** on a fresh DB.
5. Document the table/column in `AI_SITE_GUIDE.md` §11.

## 8. Preserving functionality & testing
1. `php -l` every changed PHP file.
2. Run the e2e suite on a **fresh** database: `python tests/e2e.py http://localhost:8080` (see `AI_SITE_GUIDE.md` §28/§29). It must print `0 failed`. Add tests for new behaviour.
3. Open the changed pages in a browser at mobile + desktop widths; check the browser console for CSP violations.
4. Run Lighthouse (mobile) on `/`, `/kontakt` and the changed page.
5. Check dashboard flows that touch your change (save → public page changes → refresh persists).

## 9. Avoiding destructive operations (checklist)
* No `DROP`, `DELETE FROM` without `WHERE`, no `rm -rf uploads storage`, no overwriting `.env`.
* Don't change the initial-password logic or `must_change` handling; don't log passwords or tokens.
* Don't expose `app/`, `storage/`, `source/`, `docs/`, `.env`, `*.sqlite`, `*.md` (the `.htaccess` rules and per-folder `Require all denied` must stay; verify the URLs return 403/404 after deploying).
* Don't add third-party scripts/fonts/trackers (privacy page would become wrong). If unavoidable, update `datenschutz.php` and the CSP in `send_security_headers()`.
* Don't point DNS / replace the live site at `nido-entruempelung.de` without the owner's explicit instruction (it currently serves a different WordPress site).
* Deploying = copy files only; never overwrite `storage/` or `uploads/` on the server with local copies.

## 10. Documentation update duty
After any change: update `AI_SITE_GUIDE.md` (affected sections + the "Last verified" date), `SITE_MAP.md` (pages/sections/nav), `docs/DEPLOYMENT.md` if deployment changes, and `.env.example` if you add env vars. Never document something that is not implemented and tested.

## 11. Quick recipes
* **Change a button label:** find its key in `registry()`; the owner changes it in the dashboard; for a new default edit the registry default (only affects settings without a DB row).
* **Add a service:** dashboard → Leistungen (or migration for all environments).
* **New colour in the palette:** add a registry field (type `color`), output the CSS variable in `brand_css()`, use `var(--…)` in CSS.
* **Add an environment variable:** read via `env('NAME', default)`, document in `.env.example` + guide §26.
* **Mail problem:** check dashboard badge, `storage/mail.log`, set SMTP vars (guide §22).
