# SITE_MAP — Nido Entrümpelung

Companion of `AI_SITE_GUIDE.md`. Describes every page, section, link and dashboard screen **as implemented**.

## 1. Public page hierarchy

```mermaid
flowchart TD
    HOME["/ Startseite"]
    SVC["/leistungen"]
    SVCD["/leistungen/{slug} (one per active service)"]
    GAL["/galerie (?kategorie=slug)"]
    CON["/kontakt (?leistung=slug, ?gesendet=1)"]
    IMP["/impressum"]
    DS["/datenschutz"]
    SM["/sitemap.xml (generated)"]
    RB["/robots.txt (generated)"]
    NF["404 page"]
    HOME --> SVC --> SVCD
    HOME --> GAL
    HOME --> CON
    HOME --- IMP
    HOME --- DS
    SVCD -->|"Anfrage zu dieser Leistung"| CON
    SVC -->|"Anfrage"| CON
    GAL -.->|"empty state CTA"| CON
```

Default service pages (from the flyer): `entruempelung`, `haushaltsaufloesungen`, `keller-garagenraeumungen`, `sperrmuellentsorgung`, `entruempelung-gewerbeobjekte`, `besenreine-uebergabe`, `firmenaufloesungen`. Services can be added/removed in the dashboard; slugs follow the title.

## 2. Navigation structure (editable in dashboard → Navigation)

| Area | Default items |
|---|---|
| Header (`header`) | Start `/` · Leistungen `/leistungen` · Galerie `/galerie` · Kontakt `/kontakt` + phone button (from settings) |
| Footer pages (`footer`) | Start · Leistungen · Galerie · Kontakt |
| Footer legal (`legal`) | Impressum · Datenschutz |
| Mobile action bar (fixed, < 900 px) | Anrufen (`tel:`) · WhatsApp (`wa.me`) · Anfrage (`/kontakt#formular`) |

## 3. Sections per page

```mermaid
flowchart LR
  subgraph Home["/ Startseite"]
    h1[Hero: H1, text, 2 CTAs, badge, house mark or photo] --> h2[Trust bar x4] --> h3[Services bento x7 + offer card] --> h4[Process: 4 steps] --> h5[Why + about card] --> h6[Gallery preview*] --> h7[Testimonials*] --> h8[Service area chips] --> h9[CTA band]
  end
  subgraph Services["/leistungen"]
    s1[Page head] --> s2[Row per service: media/icon, text, benefits, CTAs] --> s3[CTA band]
  end
  subgraph Service["/leistungen/slug"]
    d1[Breadcrumb + H1] --> d2[Photo*, description, benefits] --> d3[Sidebar: Besichtigung card, other services]
  end
  subgraph Gallery["/galerie"]
    g1[Page head] --> g2[Category filter*] --> g3[Grid + lightbox, or empty state]
  end
  subgraph Contact["/kontakt"]
    c1[Page head] --> c2[Info: phone, mail, address, owner, hours*] --> c3[Actions: WhatsApp, Anrufen, Nachricht] --> c4[Form] --> c5[Map click-to-load + route link]
  end
```
`*` = rendered only when data exists (images / testimonials / categories / hours / photo).

## 4. Internal linking
* Header/footer on every page; breadcrumbs on all subpages (also emitted as `BreadcrumbList` JSON-LD).
* Home service cards → `/leistungen/{slug}`; service pages → pre-filled contact form `/kontakt?leistung={slug}#formular`; area section → `/kontakt#karte`; trust/CTA buttons → `tel:`, `wa.me`, `/kontakt#formular`.
* Contact form → `/datenschutz`; map consent text → `/datenschutz#karte`.
* Sitemap lists all indexable pages; robots.txt points to the sitemap.

## 5. Admin dashboard navigation tree (`/admin`)

```mermaid
flowchart TD
  A["/admin/login"] --> B["/admin Übersicht"]
  A -.first login.-> PW["/admin/password (forced)"]
  B --> I["/admin/inquiries  Anfragen (list, ?id= detail)"]
  B --> S1["/admin/settings?g=home  Startseite"]
  B --> S2["/admin/settings?g=pages  Seitentexte"]
  B --> SV["/admin/services  Leistungen"] --> SVE["/admin/services/edit?id="]
  B --> G["/admin/gallery  Galerie + Kategorien"]
  B --> T["/admin/testimonials  Kundenstimmen"]
  B --> BR["/admin/settings?g=brand  Logo & Design"]
  B --> CO["/admin/settings?g=company  Kontakt & Firma"]
  B --> N["/admin/navigation  Navigation (header, footer, legal)"]
  B --> F["/admin/settings?g=footer  Fußbereich"]
  B --> SE["/admin/seo  SEO pro Seite (+ Sitemap/robots/JSON-LD Info)"]
  B --> AL["/admin/settings?g=seo  Allgemein (Titel, Standard-OG-Bild, robots, Search Console)"]
  B --> M["/admin/media  Mediathek"]
  B --> PW
  B --> L["POST /admin/logout"]
  M -.JSON.-> MJ["/admin/media/json, /admin/media/upload (picker)"]
  SV -.-> R["/admin/reorder (drag & drop)"]
```

Dashboard module → what it controls:

| Module | Controls |
|---|---|
| Übersicht | stats (inquiries, services, gallery, views), recent inquiries, quick actions, status checks |
| Anfragen | inbox: search, status filter, read/unread, status (Neu / In Bearbeitung / Erledigt / Archiviert), delete, quick mail/phone/WhatsApp |
| Startseite / Seitentexte | all headings, paragraphs, buttons, labels |
| Leistungen / Galerie / Kundenstimmen | CRUD, order, visibility, images, categories |
| Logo & Design | logo(s), favicon, colours, fonts, button style |
| Kontakt & Firma | company, phone, WhatsApp, mail, address, hours, social |
| Navigation / Fußbereich | menus (add/edit/reorder/hide), footer texts/logo/copyright |
| SEO / Allgemein | per-page title, description, keywords, canonical, OG, noindex; site defaults; robots extras; Search Console code |
| Medien | uploads, previews, search, alt texts, size/dimensions, safe delete (blocked while in use) |
| Passwort | change admin password |
