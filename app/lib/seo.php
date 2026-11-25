<?php
declare(strict_types=1);

/** SEO helpers: per-page metadata, JSON-LD structured data, sitemap.xml and robots.txt. */

function page_seo_row(string $key): array
{
    static $cache = [];
    return $cache[$key] ??= (q_one('SELECT * FROM page_seo WHERE page_key=?', [$key]) ?? [
        'page_key' => $key, 'title' => '', 'description' => '', 'keywords' => '', 'canonical' => '',
        'og_title' => '', 'og_description' => '', 'og_media_id' => null, 'noindex' => 0,
    ]);
}

function abs_url(string $pathOrUrl): string
{
    return preg_match('#^https?://#i', $pathOrUrl) ? $pathOrUrl : site_url($pathOrUrl);
}

/**
 * Resolves the final metadata of a page.
 * $meta: key (page_key), path, title, description, [og_image_media], [noindex], [breadcrumbs], [schema], [og_type]
 */
function resolve_meta(array $meta): array
{
    $row = page_seo_row($meta['key'] ?? '');
    $title = $row['title'] !== '' ? $row['title'] : ($meta['title'] ?? setting('site_title'));
    $desc = $row['description'] !== '' ? $row['description'] : ($meta['description'] ?? setting('site_description'));
    $path = $meta['path'] ?? '/';
    $canonical = $row['canonical'] !== '' ? abs_url($row['canonical']) : site_url($path);

    $ogImage = '';
    if (!empty($row['og_media_id'])) {
        $ogImage = abs_url(media_url((int) $row['og_media_id'], 1600));
    } elseif (!empty($meta['og_image_media'])) {
        $ogImage = abs_url(media_url((int) $meta['og_image_media'], 1600));
    } elseif ((int) setting('default_og_image') && media_row((int) setting('default_og_image'))) {
        $ogImage = abs_url(media_url((int) setting('default_og_image'), 1600));
    } else {
        $ogImage = site_url('/assets/img/og-default.jpg');
    }
    return [
        'title' => $title,
        'description' => $desc,
        'keywords' => $row['keywords'],
        'canonical' => $canonical,
        'og_title' => $row['og_title'] !== '' ? $row['og_title'] : $title,
        'og_description' => $row['og_description'] !== '' ? $row['og_description'] : $desc,
        'og_image' => $ogImage,
        'og_type' => $meta['og_type'] ?? 'website',
        'noindex' => !empty($row['noindex']) || !empty($meta['noindex']),
    ];
}

function org_schema(): array
{
    $logoId = (int) setting('logo');
    $logo = $logoId && media_row($logoId) ? abs_url(media_url($logoId)) : site_url('/assets/img/logo.png');
    $same = array_values(array_filter([
        setting('social_facebook'), setting('social_instagram'), setting('social_tiktok'), setting('social_youtube'),
    ]));
    $areas = array_map(fn($c) => ['@type' => 'City', 'name' => $c], lines(setting('area_list')));

    $org = [
        '@type' => 'Organization',
        '@id' => site_url('/#organization'),
        'name' => setting('company_name'),
        'url' => site_url('/'),
        'logo' => $logo,
        'email' => setting('email'),
        'telephone' => setting('phone_display'),
        'founder' => ['@type' => 'Person', 'name' => setting('owner_name')],
    ];
    if ($same) {
        $org['sameAs'] = $same;
    }
    $biz = [
        '@type' => 'LocalBusiness',
        '@id' => site_url('/#localbusiness'),
        'name' => setting('company_name'),
        'url' => site_url('/'),
        'image' => $logo,
        'logo' => $logo,
        'description' => setting('site_description'),
        'telephone' => setting('phone_display'),
        'email' => setting('email'),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => setting('street'),
            'postalCode' => setting('zip'),
            'addressLocality' => setting('city'),
            'addressCountry' => 'DE',
        ],
        'areaServed' => $areas ?: [['@type' => 'City', 'name' => setting('city')]],
        'parentOrganization' => ['@id' => site_url('/#organization')],
    ];
    if (setting('schema_price_range') !== '') {
        $biz['priceRange'] = setting('schema_price_range');
    }
    if ($same) {
        $biz['sameAs'] = $same;
    }
    $svcs = q_all('SELECT title FROM services WHERE active=1 ORDER BY sort,id');
    if ($svcs) {
        $biz['makesOffer'] = array_map(fn($s) => ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => $s['title']]], $svcs);
    }
    // NOTE: no geo coordinates, ratings or opening hours are emitted unless the owner supplies verified data.
    $site = [
        '@type' => 'WebSite', '@id' => site_url('/#website'), 'url' => site_url('/'),
        'name' => setting('site_title'), 'inLanguage' => setting('default_lang') ?: 'de',
        'publisher' => ['@id' => site_url('/#organization')],
    ];
    return [$org, $biz, $site];
}

function breadcrumb_schema(array $crumbs): array
{
    $items = [];
    foreach (array_values($crumbs) as $i => [$name, $path]) {
        $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $name, 'item' => site_url($path)];
    }
    return ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
}

function jsonld(array $graph): string
{
    $json = json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
    return '<script type="application/ld+json">' . $json . '</script>';
}

function sitemap_xml(): string
{
    $urls = [];
    $add = function (string $path, string $prio, string $freq, ?string $lastmod = null) use (&$urls) {
        $urls[] = [site_url($path), $prio, $freq, $lastmod];
    };
    $skip = fn(string $k) => !empty(page_seo_row($k)['noindex']);
    $lastSettings = substr((string) q_val('SELECT MAX(updated_at) FROM settings'), 0, 10) ?: date('Y-m-d');

    if (!$skip('home')) $add('/', '1.0', 'weekly', $lastSettings);
    if (!$skip('leistungen')) $add('/leistungen', '0.9', 'monthly', $lastSettings);
    foreach (q_all('SELECT slug, updated_at FROM services WHERE active=1 ORDER BY sort,id') as $s) {
        $add('/leistungen/' . $s['slug'], '0.8', 'monthly', substr($s['updated_at'], 0, 10));
    }
    if (!$skip('galerie') && (int) q_val('SELECT COUNT(*) FROM gallery_images WHERE visible=1') > 0) {
        $add('/galerie', '0.6', 'weekly', substr((string) q_val('SELECT MAX(created_at) FROM gallery_images'), 0, 10));
    }
    if (!$skip('kontakt')) $add('/kontakt', '0.9', 'yearly', $lastSettings);
    if (!$skip('impressum')) $add('/impressum', '0.2', 'yearly');
    if (!$skip('datenschutz')) $add('/datenschutz', '0.2', 'yearly');

    $x = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as [$loc, $prio, $freq, $last]) {
        $x .= "  <url><loc>" . e($loc) . "</loc>" . ($last ? "<lastmod>$last</lastmod>" : '')
            . "<changefreq>$freq</changefreq><priority>$prio</priority></url>\n";
    }
    return $x . "</urlset>\n";
}

function robots_txt(): string
{
    $t = "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /app/\nDisallow: /storage/\n\n";
    $t .= 'Sitemap: ' . site_url('/sitemap.xml') . "\n";
    if (trim(setting('robots_extra')) !== '') {
        $t .= "\n" . trim(setting('robots_extra')) . "\n";
    }
    return $t;
}
