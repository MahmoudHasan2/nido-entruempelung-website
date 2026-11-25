<?php
declare(strict_types=1);

/**
 * Database migrations. SQLite `PRAGMA user_version` tracks the applied version.
 * To change the schema: append a NEW migration to $migrations (never edit an old one).
 */

function password_algo(): string|int
{
    return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
}

function migrate(): void
{
    $pdo = db();
    $current = (int) $pdo->query('PRAGMA user_version')->fetchColumn();

    $migrations = [
        1 => function (PDO $pdo): void {
            $pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                must_change INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL,
                last_login TEXT
            );
            CREATE TABLE login_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip TEXT NOT NULL,
                username TEXT,
                created_at INTEGER NOT NULL
            );
            CREATE INDEX idx_login_attempts ON login_attempts (ip, created_at);
            CREATE TABLE settings (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL DEFAULT '',
                updated_at TEXT
            );
            CREATE TABLE media (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                file TEXT NOT NULL UNIQUE,         -- base name without size suffix/extension, e.g. 3f9a..
                ext TEXT NOT NULL,                  -- original-size extension: jpg|png|webp
                orig_name TEXT,
                alt TEXT NOT NULL DEFAULT '',
                width INTEGER NOT NULL DEFAULT 0,
                height INTEGER NOT NULL DEFAULT 0,
                bytes INTEGER NOT NULL DEFAULT 0,
                variants TEXT NOT NULL DEFAULT '', -- comma separated widths of generated -<w>.webp files
                created_at TEXT NOT NULL
            );
            CREATE TABLE services (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT NOT NULL UNIQUE,
                title TEXT NOT NULL,
                short TEXT NOT NULL DEFAULT '',
                description TEXT NOT NULL DEFAULT '',
                benefits TEXT NOT NULL DEFAULT '',  -- one benefit per line
                icon TEXT NOT NULL DEFAULT 'home',
                media_id INTEGER REFERENCES media(id) ON DELETE SET NULL,
                seo_title TEXT NOT NULL DEFAULT '',
                seo_description TEXT NOT NULL DEFAULT '',
                sort INTEGER NOT NULL DEFAULT 0,
                active INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );
            CREATE TABLE gallery_categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                sort INTEGER NOT NULL DEFAULT 0
            );
            CREATE TABLE gallery_images (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INTEGER REFERENCES gallery_categories(id) ON DELETE SET NULL,
                media_id INTEGER NOT NULL REFERENCES media(id) ON DELETE CASCADE,
                title TEXT NOT NULL DEFAULT '',
                description TEXT NOT NULL DEFAULT '',
                sort INTEGER NOT NULL DEFAULT 0,
                visible INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL
            );
            CREATE TABLE inquiries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                email TEXT NOT NULL,
                phone TEXT NOT NULL DEFAULT '',
                subject TEXT NOT NULL,
                message TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'new',   -- new|progress|done|archived
                is_read INTEGER NOT NULL DEFAULT 0,
                mail_status TEXT NOT NULL DEFAULT 'pending', -- pending|sent|failed
                ip TEXT NOT NULL DEFAULT '',
                created_at TEXT NOT NULL
            );
            CREATE INDEX idx_inquiries_status ON inquiries (status, created_at);
            CREATE TABLE inquiry_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip TEXT NOT NULL,
                created_at INTEGER NOT NULL
            );
            CREATE TABLE nav_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                area TEXT NOT NULL,                 -- header|footer|legal
                label TEXT NOT NULL,
                url TEXT NOT NULL,
                sort INTEGER NOT NULL DEFAULT 0,
                visible INTEGER NOT NULL DEFAULT 1
            );
            CREATE TABLE page_seo (
                page_key TEXT PRIMARY KEY,
                title TEXT NOT NULL DEFAULT '',
                description TEXT NOT NULL DEFAULT '',
                keywords TEXT NOT NULL DEFAULT '',
                canonical TEXT NOT NULL DEFAULT '',
                og_title TEXT NOT NULL DEFAULT '',
                og_description TEXT NOT NULL DEFAULT '',
                og_media_id INTEGER REFERENCES media(id) ON DELETE SET NULL,
                noindex INTEGER NOT NULL DEFAULT 0
            );
            CREATE TABLE testimonials (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                text TEXT NOT NULL,
                place TEXT NOT NULL DEFAULT '',
                sort INTEGER NOT NULL DEFAULT 0,
                visible INTEGER NOT NULL DEFAULT 1
            );
            CREATE TABLE page_views (
                day TEXT NOT NULL,
                path TEXT NOT NULL,
                views INTEGER NOT NULL DEFAULT 0,
                PRIMARY KEY (day, path)
            );
            ");

            /* ---- initial admin: must change password on first login ---- */
            $initial = env('ADMIN_INITIAL_PASSWORD', 'ChangeMe-2026!');
            $st = $pdo->prepare('INSERT INTO users (username, password_hash, must_change, created_at) VALUES (?,?,1,?)');
            $st->execute(['admin', password_hash((string) $initial, password_algo()), now()]);

            /* ---- navigation ---- */
            $nav = $pdo->prepare('INSERT INTO nav_items (area,label,url,sort,visible) VALUES (?,?,?,?,1)');
            $items = [
                ['header', 'Start', '/'], ['header', 'Leistungen', '/leistungen'],
                ['header', 'Galerie', '/galerie'], ['header', 'Kontakt', '/kontakt'],
                ['footer', 'Start', '/'], ['footer', 'Leistungen', '/leistungen'],
                ['footer', 'Galerie', '/galerie'], ['footer', 'Kontakt', '/kontakt'],
                ['legal', 'Impressum', '/impressum'], ['legal', 'Datenschutz', '/datenschutz'],
            ];
            foreach ($items as $i => [$a, $l, $u]) {
                $nav->execute([$a, $l, $u, $i]);
            }

            /* ---- gallery categories (empty until real photos are uploaded) ---- */
            $cat = $pdo->prepare('INSERT INTO gallery_categories (name,slug,sort) VALUES (?,?,?)');
            foreach ([['Entrümpelungen', 'entruempelungen'], ['Keller & Garagen', 'keller-garagen'], ['Haushaltsauflösungen', 'haushaltsaufloesungen']] as $i => [$n, $s]) {
                $cat->execute([$n, $s, $i]);
            }

            /* ---- services: exactly the seven services printed on the flyer ---- */
            $svc = $pdo->prepare('INSERT INTO services (slug,title,short,description,benefits,icon,sort,active,created_at,updated_at) VALUES (?,?,?,?,?,?,?,1,?,?)');
            $t = now();
            foreach (default_services() as $i => $s) {
                $svc->execute([$s['slug'], $s['title'], $s['short'], $s['description'], $s['benefits'], $s['icon'], $i, $t, $t]);
            }

            /* ---- SEO defaults per page ---- */
            $seo = $pdo->prepare('INSERT INTO page_seo (page_key,title,description,keywords) VALUES (?,?,?,?)');
            foreach (default_page_seo() as $k => $v) {
                $seo->execute([$k, $v[0], $v[1], $v[2]]);
            }
        },
    ];

    foreach ($migrations as $version => $fn) {
        if ($version <= $current) {
            continue;
        }
        $pdo->beginTransaction();
        try {
            $fn($pdo);
            $pdo->exec('PRAGMA user_version = ' . (int) $version);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}

/** The seven services of the original flyer (Flyer.pdf, page 2 "UNSERE LEISTUNGEN"). */
function default_services(): array
{
    return [
        [
            'slug' => 'entruempelung', 'icon' => 'home',
            'title' => 'Entrümpelung',
            'short' => 'Entrümpelung von Wohnungen und Häusern.',
            'description' => "Ob Wohnung oder Haus: Wir räumen Ihre Räume vollständig leer – vom Sperrigen bis zum Kleinkram. Sie müssen nichts vorsortieren und nichts selbst tragen.\n\nNach einer kostenlosen Besichtigung erhalten Sie ein faires, unverbindliches Angebot. Auf Wunsch räumen wir komplett oder nur einzelne Bereiche.",
            'benefits' => "Kostenlose Besichtigung vor Ort\nTransparentes, unverbindliches Angebot\nKurzfristige Termine möglich\nFachgerechte Entsorgung",
        ],
        [
            'slug' => 'haushaltsaufloesungen', 'icon' => 'box',
            'title' => 'Haushaltsauflösungen',
            'short' => 'Komplette Auflösung von Haushalten jeglicher Art.',
            'description' => "Eine Haushaltsauflösung ist oft mit Erinnerungen und viel Arbeit verbunden. Wir übernehmen sie komplett oder in Teilen – diskret, strukturiert und zuverlässig.\n\nSprechen Sie uns an: Wir klären bei der Besichtigung, was geräumt, transportiert und entsorgt werden soll.",
            'benefits' => "Komplett- oder Teilauflösung\nDiskrete und zuverlässige Abwicklung\nBesenreine Übergabe auf Wunsch\nFaire Preise ohne versteckte Kosten",
        ],
        [
            'slug' => 'keller-garagenraeumungen', 'icon' => 'garage',
            'title' => 'Keller- & Garagenräumungen',
            'short' => 'Wir räumen Keller, Garagen und sonstige Räume.',
            'description' => "Keller, Garage, Dachboden oder Lagerraum: Wir schaffen wieder Platz. Wir räumen aus, transportieren ab und entsorgen fachgerecht, was nicht mehr gebraucht wird.\n\nAuch schwer zugängliche oder sehr volle Räume sind für uns kein Problem – wir sehen sie uns vorab kostenlos an.",
            'benefits' => "Auch schwer zugängliche Räume\nAbtransport und Entsorgung aus einer Hand\nKostenlose Besichtigung\nFlexible Terminvereinbarung",
        ],
        [
            'slug' => 'sperrmuellentsorgung', 'icon' => 'trash',
            'title' => 'Sperrmüllentsorgung',
            'short' => 'Fachgerechte Entsorgung von Sperrmüll aller Art.',
            'description' => "Alte Möbel, Matratzen, Elektrogeräte und sonstiger Sperrmüll: Wir holen ab und entsorgen fachgerecht und umweltbewusst.\n\nSie sparen sich den Weg zum Wertstoffhof und das Tragen – wir kümmern uns um alles.",
            'benefits' => "Abholung direkt bei Ihnen\nFachgerechte Entsorgung\nTransparentes Angebot\nSchnelle Terminfindung",
        ],
        [
            'slug' => 'entruempelung-gewerbeobjekte', 'icon' => 'building',
            'title' => 'Entrümpelung von Gewerbeobjekten',
            'short' => 'Für Büros, Lagerräume, Ladenlokale und mehr.',
            'description' => "Geschäftsräume müssen oft zügig und ohne Störung des Betriebsablaufs geräumt werden. Wir entrümpeln Büros, Lagerräume, Ladenlokale und weitere Gewerbeobjekte zuverlässig und strukturiert.\n\nFragen Sie uns nach einem individuellen Angebot für Ihr Objekt.",
            'benefits' => "Büros, Lager und Ladenlokale\nZuverlässig und termintreu\nIndividuelles Angebot nach Besichtigung\nFachgerechte Entsorgung",
        ],
        [
            'slug' => 'besenreine-uebergabe', 'icon' => 'broom',
            'title' => 'Besenreine Übergabe',
            'short' => 'Wir hinterlassen die Räume besenrein – bereit für die Übergabe.',
            'description' => "Nach der Räumung hinterlassen wir die Räume besenrein, damit Sie sie ohne Sorgen übergeben können – an Vermieter, Käufer oder Nachmieter.\n\nDie besenreine Übergabe können Sie mit jeder unserer Räumungsleistungen kombinieren.",
            'benefits' => "Räume besenrein hinterlassen\nBereit für Vermieter, Käufer oder Nachmieter\nKombinierbar mit jeder Räumung\nEin Ansprechpartner für alles",
        ],
        [
            'slug' => 'firmenaufloesungen', 'icon' => 'briefcase',
            'title' => 'Firmenauflösungen',
            'short' => 'Komplette Auflösung von Firmen, Betrieben und Geschäftsauflösung.',
            'description' => "Bei Firmen-, Betriebs- und Geschäftsauflösungen übernehmen wir die komplette Räumung der Objekte – von der Einrichtung bis zu Lagerbeständen.\n\nWir stimmen Umfang und Termin mit Ihnen ab und sorgen für einen reibungslosen Ablauf.",
            'benefits' => "Komplette Auflösung aus einer Hand\nAbstimmung von Umfang und Termin\nFachgerechte Entsorgung\nFür Gewerbekunden und Privatkunden",
        ],
    ];
}

/** key => [title, description, keywords] */
function default_page_seo(): array
{
    return [
        'home' => [
            'Entrümpelung & Haushaltsauflösung in Gifhorn | Nido Entrümpelung',
            'Nido Entrümpelung in Gifhorn: Entrümpelung, Haushaltsauflösung und Sperrmüllentsorgung. Kostenlose Besichtigung und faires, unverbindliches Angebot. Jetzt anfragen.',
            'Entrümpelung Gifhorn, Haushaltsauflösung Gifhorn, Wohnungsauflösung, Kellerentrümpelung, Entrümpelungsservice',
        ],
        'leistungen' => [
            'Leistungen: Entrümpelung, Haushaltsauflösung & mehr | Nido Entrümpelung',
            'Alle Leistungen von Nido Entrümpelung in Gifhorn und Umgebung: Entrümpelung, Haushaltsauflösungen, Keller- und Garagenräumungen, Sperrmüllentsorgung, Gewerbe und besenreine Übergabe.',
            'Entrümpelungsservice Gifhorn, Haushaltsauflösung, Kellerräumung, Garagenräumung, Sperrmüllentsorgung',
        ],
        'galerie' => [
            'Galerie | Nido Entrümpelung Gifhorn',
            'Einblicke in unsere Arbeit: Bilder aus Entrümpelungen, Räumungen und Haushaltsauflösungen von Nido Entrümpelung in Gifhorn und Umgebung.',
            '',
        ],
        'kontakt' => [
            'Kontakt & kostenlose Besichtigung | Nido Entrümpelung Gifhorn',
            'Kontakt zu Nido Entrümpelung in Gifhorn: Rufen Sie an, schreiben Sie per WhatsApp oder nutzen Sie das Formular für Ihre unverbindliche Anfrage.',
            '',
        ],
        'impressum' => ['Impressum | Nido Entrümpelung', 'Impressum von Nido Entrümpelung, Inhaber Max Mustermann, Gifhorn.', ''],
        'datenschutz' => ['Datenschutzerklärung | Nido Entrümpelung', 'Datenschutzerklärung von Nido Entrümpelung: Informationen zur Verarbeitung personenbezogener Daten.', ''],
    ];
}
