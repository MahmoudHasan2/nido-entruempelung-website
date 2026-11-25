<?php
declare(strict_types=1);

/**
 * Registry of every website setting that is editable in the admin dashboard.
 * Admin forms are generated from this registry; public templates read values with setting().
 *
 * Field types: text | textarea | url | email | image | color | select | bool | number
 * To add a new editable value: add it here (with a German default) and call setting('key') in a template.
 */
function registry(): array
{
    static $r = null;
    if ($r !== null) {
        return $r;
    }
    $r = [
        'company' => [
            'label' => 'Kontakt & Firma',
            'help' => 'Diese Angaben erscheinen im Kopf, Fuß, Kontaktbereich, Impressum und in den Suchmaschinen-Daten.',
            'fields' => [
                'company_name' => ['text', 'Firmenname', 'Nido Entrümpelung'],
                'owner_name' => ['text', 'Inhaber', 'Max Mustermann'],
                'phone_display' => ['text', 'Telefonnummer (Anzeige)', '+49 151 00000000'],
                'phone_href' => ['text', 'Telefonnummer (für Anruf-Link, nur Ziffern und +)', '+4915100000000'],
                'whatsapp_number' => ['text', 'WhatsApp-Nummer (internationales Format, nur Ziffern)', '4915100000000', 'Beispiel: 4915100000000 (ohne + und ohne Leerzeichen).'],
                'whatsapp_text' => ['text', 'WhatsApp-Vorlagentext', 'Hallo Nido Entrümpelung, ich habe eine Anfrage.'],
                'email' => ['email', 'E-Mail-Adresse', 'info@nido-entruempelung.de'],
                'street' => ['text', 'Straße und Hausnummer', 'Musterstraße 1'],
                'zip' => ['text', 'PLZ', '38500'],
                'city' => ['text', 'Ort', 'Gifhorn'],
                'country' => ['text', 'Land', 'Deutschland'],
                'service_area' => ['text', 'Einsatzgebiet (Kurzform)', 'Gifhorn & Umgebung'],
                'area_list' => ['textarea', 'Einsatzorte (ein Ort pro Zeile)', "Gifhorn\nWolfsburg\nBraunschweig", 'Quelle: aktuelles Firmen-Flyer („Gifhorn, Wolfsburg, Braunschweig und Umgebung“).'],
                'opening_hours' => ['textarea', 'Öffnungs- bzw. Erreichbarkeitszeiten (eine Zeile pro Eintrag)', '', 'Leer lassen = Bereich wird auf der Website nicht angezeigt.'],
                'social_facebook' => ['url', 'Facebook-URL', ''],
                'social_instagram' => ['url', 'Instagram-URL', ''],
                'social_tiktok' => ['url', 'TikTok-URL', ''],
                'social_youtube' => ['url', 'YouTube-URL', ''],
            ],
        ],
        'brand' => [
            'label' => 'Logo & Design',
            'help' => 'Änderungen wirken sofort auf der gesamten öffentlichen Website.',
            'fields' => [
                'logo' => ['image', 'Logo (für helle Hintergründe)', '', 'PNG/JPG/WebP. Leer = Original-Logo aus dem Flyer (SVG).'],
                'logo_light' => ['image', 'Logo (für dunkle Hintergründe)', '', 'Leer = Original-Logo in heller Version.'],
                'favicon' => ['image', 'Favicon / Website-Symbol', '', 'Quadratisch, mindestens 192×192 Pixel. Leer = Standard-Symbol (Haus-Symbol aus dem Logo).'],
                'color_primary' => ['color', 'Primärfarbe (Aktionen, Buttons)', '#E77524', 'Logo-Orange.'],
                'color_primary_dark' => ['color', 'Primärfarbe dunkel (Text auf hellem Grund, Hover)', '#A94A0C', 'Mindestens 4,5:1 Kontrast zu Weiß, damit Text lesbar bleibt.'],
                'color_secondary' => ['color', 'Sekundärfarbe (dunkle Flächen)', '#231F20', 'Logo-Anthrazit.'],
                'color_accent' => ['color', 'Akzentfarbe', '#FFB37A'],
                'color_bg' => ['color', 'Hintergrundfarbe', '#F4F3F1'],
                'color_surface' => ['color', 'Kartenfarbe', '#FFFFFF'],
                'color_text' => ['color', 'Textfarbe', '#231F20'],
                'font_display' => ['select', 'Schrift für Überschriften', 'barlow-condensed', '', ['barlow-condensed' => 'Barlow Condensed (Standard)', 'barlow' => 'Barlow', 'system' => 'Systemschrift']],
                'font_body' => ['select', 'Schrift für Fließtext', 'barlow', '', ['barlow' => 'Barlow (Standard)', 'system' => 'Systemschrift']],
                'btn_style' => ['select', 'Button-Stil', 'angled', '', ['angled' => 'Angeschrägt (Standard, wie im Logo)', 'square' => 'Eckig', 'rounded' => 'Abgerundet', 'pill' => 'Pillenform']],
            ],
        ],
        'home' => [
            'label' => 'Startseite',
            'help' => 'Alle Texte der Startseite.',
            'fields' => [
                'hero_title' => ['text', 'Hero: Überschrift', 'Wir schaffen Platz für Neues.'],
                'hero_text' => ['textarea', 'Hero: Beschreibung', 'Entrümpelung und Haushaltsauflösung in Gifhorn und Umgebung. Wir übernehmen den Rest – Sie lehnen sich zurück und erhalten Ihre Räume besenrein zurück.'],
                'hero_cta_primary' => ['text', 'Hero: Haupt-Button', 'Kostenlose Besichtigung anfragen'],
                'hero_cta_secondary' => ['text', 'Hero: zweiter Button', 'Jetzt anrufen'],
                'hero_image' => ['image', 'Hero: Foto (optional)', '', 'Nur echte Fotos der Firma verwenden. Leer = grafische Gestaltung ohne Foto.'],
                'hero_badge' => ['text', 'Hero: Hinweisfeld', 'Kostenlose Besichtigung & unverbindliches Angebot'],
                'trust1_title' => ['text', 'Vorteil 1: Titel', 'Kostenlose Besichtigung'],
                'trust1_text' => ['text', 'Vorteil 1: Text', 'Wir sehen uns Ihr Objekt vor Ort an.'],
                'trust2_title' => ['text', 'Vorteil 2: Titel', 'Faire Preise'],
                'trust2_text' => ['text', 'Vorteil 2: Text', 'Transparente Angebote ohne versteckte Kosten.'],
                'trust3_title' => ['text', 'Vorteil 3: Titel', 'Zuverlässig'],
                'trust3_text' => ['text', 'Vorteil 3: Text', 'Wir halten, was wir versprechen.'],
                'trust4_title' => ['text', 'Vorteil 4: Titel', 'Schnell'],
                'trust4_text' => ['text', 'Vorteil 4: Text', 'Kurzfristige Termine möglich.'],
                'services_title' => ['text', 'Leistungen: Überschrift', 'Was wir für Sie räumen'],
                'services_text' => ['textarea', 'Leistungen: Einleitung', 'Von der einzelnen Garage bis zur kompletten Haushaltsauflösung – für Privatkunden und Gewerbekunden.'],
                'process_title' => ['text', 'Ablauf: Überschrift', 'Wir räumen. Wir transportieren. Wir übergeben. Sie entspannen.'],
                'step1_title' => ['text', 'Schritt 1: Titel', 'Anfrage'],
                'step1_text' => ['textarea', 'Schritt 1: Text', 'Sie rufen an, schreiben per WhatsApp oder nutzen das Kontaktformular.'],
                'step2_title' => ['text', 'Schritt 2: Titel', 'Besichtigung & Angebot'],
                'step2_text' => ['textarea', 'Schritt 2: Text', 'Wir besichtigen Ihr Objekt kostenlos vor Ort und erstellen ein faires, unverbindliches Angebot.'],
                'step3_title' => ['text', 'Schritt 3: Titel', 'Räumung & Entsorgung'],
                'step3_text' => ['textarea', 'Schritt 3: Text', 'Wir räumen aus, transportieren ab und entsorgen fachgerecht.'],
                'step4_title' => ['text', 'Schritt 4: Titel', 'Besenreine Übergabe'],
                'step4_text' => ['textarea', 'Schritt 4: Text', 'Wir hinterlassen die Räume besenrein – bereit für die Übergabe.'],
                'why_title' => ['text', 'Vorteile: Überschrift', 'Warum Nido Entrümpelung'],
                'why_text' => ['textarea', 'Vorteile: Einleitung', 'Für Privatkunden und Gewerbekunden in Gifhorn und Umgebung.'],
                'why_list' => ['textarea', 'Vorteile: Liste (ein Punkt pro Zeile)', "Kostenlose Besichtigung\nFaire Preise – transparente Angebote ohne versteckte Kosten\nZuverlässig – wir halten, was wir versprechen\nKurzfristige Termine möglich\nFachgerechte und umweltbewusste Entsorgung\nBesenreine Übergabe"],
                'about_title' => ['text', 'Über uns: Überschrift', 'Ihr Ansprechpartner vor Ort'],
                'about_text' => ['textarea', 'Über uns: Text', "Nido Entrümpelung ist ein Entrümpelungs- und Haushaltsauflösungsservice aus Gifhorn. Inhaber Max Mustermann und sein Team kümmern sich um Räumungen für Privat- und Gewerbekunden in Gifhorn und Umgebung.\n\nUnser Anspruch: sauber, schnell, zuverlässig – mit einem klaren Angebot vorab und einer besenreinen Übergabe am Ende."],
                'gallery_home_title' => ['text', 'Galerie-Vorschau: Überschrift', 'Einblicke in unsere Arbeit'],
                'testimonials_title' => ['text', 'Kundenstimmen: Überschrift', 'Was Kunden sagen'],
                'area_title' => ['text', 'Einsatzgebiet: Überschrift', 'Im Einsatz in Gifhorn und Umgebung'],
                'area_text' => ['textarea', 'Einsatzgebiet: Text', 'Wir sind in Gifhorn zuhause und räumen in der gesamten Umgebung. Fragen Sie uns einfach, ob wir auch Ihren Ort anfahren.'],
                'cta_title' => ['text', 'Abschluss: Überschrift', 'Jetzt anfragen – wir melden uns schnell.'],
                'cta_text' => ['textarea', 'Abschluss: Text', 'Beschreiben Sie uns kurz Ihr Vorhaben. Die Besichtigung ist kostenlos, das Angebot unverbindlich.'],
            ],
        ],
        'pages' => [
            'label' => 'Seitentexte',
            'help' => 'Überschriften und Einleitungen der Unterseiten.',
            'fields' => [
                'services_h1' => ['text', 'Leistungen: Hauptüberschrift (H1)', 'Unsere Leistungen'],
                'services_intro' => ['textarea', 'Leistungen: Einleitung', 'Entrümpelung, Haushaltsauflösung, Keller- und Garagenräumung, Sperrmüllentsorgung und mehr – für Privatkunden und Gewerbekunden in Gifhorn und Umgebung.'],
                'gallery_h1' => ['text', 'Galerie: Hauptüberschrift (H1)', 'Galerie'],
                'gallery_intro' => ['textarea', 'Galerie: Einleitung', 'Einblicke in unsere Arbeit.'],
                'gallery_empty' => ['text', 'Galerie: Hinweis ohne Bilder', 'Hier erscheinen in Kürze Bilder unserer Arbeit.'],
                'contact_h1' => ['text', 'Kontakt: Hauptüberschrift (H1)', 'Kontakt'],
                'contact_intro' => ['textarea', 'Kontakt: Einleitung', 'Rufen Sie uns an, schreiben Sie uns bei WhatsApp oder senden Sie uns eine Nachricht. Die Besichtigung ist kostenlos, das Angebot unverbindlich.'],
                'contact_lead' => ['text', 'Kontakt: Überschrift Kontaktbereich', 'Wir sind für Sie da'],
                'form_title' => ['text', 'Kontaktformular: Überschrift', 'Schreiben Sie uns'],
                'form_success' => ['text', 'Kontaktformular: Erfolgsmeldung', 'Vielen Dank! Ihre Nachricht ist bei uns angekommen. Wir melden uns schnellstmöglich bei Ihnen.'],
                'map_title' => ['text', 'Karte: Überschrift', 'So finden Sie uns'],
                'impressum_extra' => ['textarea', 'Impressum: zusätzliche Angaben (z. B. USt-IdNr., nur wenn vorhanden)', '', 'Leer lassen, wenn es nichts zu ergänzen gibt. Bitte keine Angaben erfinden.'],
                'datenschutz_extra' => ['textarea', 'Datenschutz: zusätzliche Hinweise', '', 'Wird am Ende der Datenschutzerklärung angezeigt.'],
            ],
        ],
        'footer' => [
            'label' => 'Fußbereich',
            'help' => 'Texte im Footer. Die Links bearbeiten Sie unter „Navigation“.',
            'fields' => [
                'footer_logo' => ['image', 'Footer-Logo (optional)', '', 'Leer = helles Logo.'],
                'footer_text' => ['textarea', 'Footer-Text', 'Entrümpelung und Haushaltsauflösung in Gifhorn und Umgebung. Sauber. Schnell. Zuverlässig.'],
                'footer_copyright' => ['text', 'Copyright-Zeile', '© {year} Nido Entrümpelung', '{year} wird automatisch durch das aktuelle Jahr ersetzt.'],
            ],
        ],
        'seo' => [
            'label' => 'Allgemeine SEO-Einstellungen',
            'help' => 'Standardwerte für alle Seiten. Die Einstellungen pro Seite finden Sie unter „SEO“.',
            'fields' => [
                'site_title' => ['text', 'Website-Titel', 'Nido Entrümpelung'],
                'site_description' => ['textarea', 'Website-Beschreibung', 'Entrümpelung und Haushaltsauflösung in Gifhorn und Umgebung – kostenlose Besichtigung, faire Preise.'],
                'default_lang' => ['text', 'Sprache (HTML-Attribut)', 'de'],
                'default_og_image' => ['image', 'Standard-Bild für soziale Netzwerke (Open Graph)', '', 'Empfohlen: 1200×630 Pixel. Leer = mitgeliefertes Standardbild.'],
                'schema_price_range' => ['text', 'Preisklasse für Suchmaschinen (optional)', ''],
                'robots_extra' => ['textarea', 'robots.txt: zusätzliche Zeilen', '', 'Wird an die automatisch erzeugte robots.txt angehängt.'],
                'head_note' => ['text', 'Google Search Console: Verifizierungs-Code (nur der Content-Wert)', ''],
            ],
        ],
    ];
    return $r;
}

/** Flat lookup: key => [type,label,default,help,options] */
function field_def(string $key): ?array
{
    static $flat = null;
    if ($flat === null) {
        $flat = [];
        foreach (registry() as $g) {
            foreach ($g['fields'] as $k => $d) {
                $flat[$k] = $d;
            }
        }
    }
    return $flat[$key] ?? null;
}

function all_settings(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (q_all('SELECT key, value FROM settings') as $row) {
            $cache[$row['key']] = $row['value'];
        }
    }
    return $cache;
}

/** Reads a setting; falls back to the registry default. */
function setting(string $key): string
{
    $all = all_settings();
    if (array_key_exists($key, $all)) {
        return $all[$key];
    }
    $def = field_def($key);
    return $def ? (string) $def[2] : '';
}

function save_setting(string $key, string $value): void
{
    q('INSERT INTO settings (key,value,updated_at) VALUES (?,?,?)
       ON CONFLICT(key) DO UPDATE SET value=excluded.value, updated_at=excluded.updated_at', [$key, $value, now()]);
}

/** Validates a raw value according to its field type. Returns [cleanValue|null, errorMessage|null]. */
function clean_field(string $key, string $raw): array
{
    $def = field_def($key);
    if (!$def) {
        return [null, 'Unbekanntes Feld.'];
    }
    $type = $def[0];
    $label = $def[1];
    $raw = trim($raw);
    switch ($type) {
        case 'color':
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $raw)) {
                return [null, "„{$label}“: bitte einen Hex-Farbwert wie #E77524 angeben."];
            }
            return [strtoupper($raw), null];
        case 'email':
            if ($raw !== '' && !filter_var($raw, FILTER_VALIDATE_EMAIL)) {
                return [null, "„{$label}“: ungültige E-Mail-Adresse."];
            }
            return [$raw, null];
        case 'url':
            if ($raw !== '' && !preg_match('#^https?://#i', $raw)) {
                return [null, "„{$label}“: URL muss mit https:// beginnen."];
            }
            if ($raw !== '' && !filter_var($raw, FILTER_VALIDATE_URL)) {
                return [null, "„{$label}“: ungültige URL."];
            }
            return [$raw, null];
        case 'select':
            if (!isset($def[4][$raw])) {
                return [null, "„{$label}“: ungültige Auswahl."];
            }
            return [$raw, null];
        case 'image':
            if ($raw === '') {
                return ['', null];
            }
            if (!ctype_digit($raw) || !q_val('SELECT id FROM media WHERE id=?', [(int) $raw])) {
                return [null, "„{$label}“: Bild nicht gefunden."];
            }
            return [$raw, null];
        case 'number':
            return is_numeric($raw) ? [$raw, null] : [null, "„{$label}“: bitte eine Zahl angeben."];
        default:
            if (mb_strlen($raw) > 6000) {
                return [null, "„{$label}“: Text ist zu lang."];
            }
            if ($key === 'phone_href' && !preg_match('/^\+?[0-9]{6,20}$/', $raw)) {
                return [null, "„{$label}“: nur Ziffern und ein führendes + erlaubt."];
            }
            if ($key === 'whatsapp_number' && !preg_match('/^[0-9]{6,20}$/', $raw)) {
                return [null, "„{$label}“: nur Ziffern (ohne + und Leerzeichen)."];
            }
            return [$raw, null];
    }
}

/* ----------------------------------------------------------- derived */
function company_address_line(): string
{
    return setting('street') . ', ' . setting('zip') . ' ' . setting('city') . ', ' . setting('country');
}

function whatsapp_url(?string $text = null): string
{
    $n = preg_replace('/\D+/', '', setting('whatsapp_number')) ?? '';
    return 'https://wa.me/' . $n . '?text=' . rawurlencode($text ?? setting('whatsapp_text'));
}

function tel_url(): string
{
    return 'tel:' . preg_replace('/[^\d+]/', '', setting('phone_href'));
}

function nav_items(string $area): array
{
    return q_all('SELECT * FROM nav_items WHERE area=? AND visible=1 ORDER BY sort, id', [$area]);
}

function lines(string $text): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R/', $text) ?: []), fn($l) => $l !== ''));
}

function brand_css(): string
{
    $c = fn(string $k) => preg_match('/^#[0-9A-Fa-f]{6}$/', setting($k)) ? setting($k) : (string) field_def($k)[2];
    $display = match (setting('font_display')) {
        'barlow' => "'Barlow', system-ui, sans-serif",
        'system' => "system-ui, -apple-system, 'Segoe UI', sans-serif",
        default => "'Barlow Condensed', 'Barlow', system-ui, sans-serif",
    };
    $body = setting('font_body') === 'system' ? "system-ui, -apple-system, 'Segoe UI', sans-serif" : "'Barlow', system-ui, sans-serif";
    $btn = setting('btn_style');
    $clip = $btn === 'angled' ? 'polygon(0 0, 100% 0, calc(100% - 14px) 100%, 0 100%)' : 'none';
    $radius = match ($btn) { 'rounded' => '10px', 'pill' => '999px', default => '0' };
    return ':root{'
        . '--c-primary:' . $c('color_primary') . ';'
        . '--c-primary-dark:' . $c('color_primary_dark') . ';'
        . '--c-secondary:' . $c('color_secondary') . ';'
        . '--c-accent:' . $c('color_accent') . ';'
        . '--c-bg:' . $c('color_bg') . ';'
        . '--c-surface:' . $c('color_surface') . ';'
        . '--c-text:' . $c('color_text') . ';'
        . '--f-display:' . $display . ';'
        . '--f-body:' . $body . ';'
        . '--btn-clip:' . $clip . ';'
        . '--btn-radius:' . $radius . ';'
        . '}';
}
