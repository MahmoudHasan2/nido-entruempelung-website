<?php
declare(strict_types=1);

/** Inline SVG icon set (24×24, stroke based). icon('phone', 'ic-sm') */
function icon_paths(): array
{
    return [
        'home' => 'M3 11l9-8 9 8M5 9.5V21h14V9.5M10 21v-6h4v6',
        'box' => 'M12 3l8 4v10l-8 4-8-4V7l8-4zM4 7l8 4 8-4M12 11v10',
        'garage' => 'M3 21V9l9-6 9 6v12M7 21v-8h10v8M7 15.5h10M7 18h10',
        'trash' => 'M4 7h16M9 7V4h6v3M6 7l1 14h10l1-14M10 11v6M14 11v6',
        'building' => 'M5 21V4h9v17M14 9h5v12M3 21h18M8 8h2M8 12h2M8 16h2',
        'broom' => 'M19 3l-7.5 9M11 12l4 4-5 6H3l-1.5-5 9.5-5',
        'briefcase' => 'M3 8h18v12H3zM9 8V5h6v3M3 13h18M11 13h2',
        'phone' => 'M5 4h4l2 5-2.5 1.5a11 11 0 005 5L15 13l5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2z',
        'mail' => 'M3 5h18v14H3zM3 7l9 7 9-7',
        'pin' => 'M12 21s7-6.2 7-11a7 7 0 10-14 0c0 4.8 7 11 7 11zM12 12a2.5 2.5 0 100-5 2.5 2.5 0 000 5z',
        'whatsapp' => 'M3 21l1.6-4.8A9 9 0 1112 21a9 9 0 01-4.4-1.2L3 21zM9 8.5c0 3.5 3 6.5 6.5 6.5l1-1.6-2.2-1-1 .9c-1-.4-2-1.4-2.4-2.4l.9-1-1-2.2L9 8.5z',
        'check' => 'M5 12.5l4.5 4.5L19 7',
        'clock' => 'M12 3a9 9 0 100 18 9 9 0 000-18zM12 7v5l3.5 2',
        'shield' => 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3zM8.5 12l2.5 2.5 4.5-5',
        'euro' => 'M12 3a9 9 0 100 18 9 9 0 000-18zM15.5 9a4 4 0 100 6M7.5 11h6M7.5 13.5h6',
        'truck' => 'M2 6h12v10H2zM14 9h4l3 3v4h-7M6.5 19.5a2 2 0 100-4 2 2 0 000 4zM17.5 19.5a2 2 0 100-4 2 2 0 000 4z',
        'arrow' => 'M5 12h14M13 6l6 6-6 6',
        'menu' => 'M4 7h16M4 12h16M4 17h16',
        'close' => 'M6 6l12 12M18 6L6 18',
        'clipboard' => 'M9 4h6v3H9zM6 5.5H4.5V21h15V5.5H18M9 12.5l1.5 1.5L14 10.5M9 17.5h6',
        'leaf' => 'M5 19c0-9 5-14 15-14 0 10-5 15-14 15M5 19c3-5 6-8 10-10',
        'image' => 'M3 5h18v14H3zM3 16l5-5 4 4 3-3 6 6M16 9.5h.01',
        'chevron-down' => 'M6 9l6 6 6-6',
        'chevron-left' => 'M15 6l-6 6 6 6',
        'chevron-right' => 'M9 6l6 6-6 6',
        'map' => 'M9 3L3 5v16l6-2 6 2 6-2V3l-6 2-6-2zM9 3v16M15 5v16',
        'user' => 'M12 12a4 4 0 100-8 4 4 0 000 8zM4 21c0-4 4-6 8-6s8 2 8 6',
        'grid' => 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z',
        'sliders' => 'M4 7h10M18 7h2M4 17h2M10 17h10M16 5v4M8 15v4',
        'edit' => 'M4 20h4L19 9l-4-4L4 16v4zM13 7l4 4',
        'plus' => 'M12 5v14M5 12h14',
        'search' => 'M11 4a7 7 0 100 14 7 7 0 000-14zM20 20l-4-4',
        'globe' => 'M12 3a9 9 0 100 18 9 9 0 000-18zM3 12h18M12 3c3.5 3.5 3.5 14.500 0 18M12 3c-3.500 3.500-3.500 14.500 0 18',
        'lock' => 'M5 11h14v10H5zM8 11V8a4 4 0 018 0v3',
        'logout' => 'M9 4H5v16h4M16 8l4 4-4 4M20 12H9',
        'upload' => 'M12 16V4M7 9l5-5 5 5M4 20h16',
        'eye' => 'M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zM12 15a3 3 0 100-6 3 3 0 000 6z',
        'star' => 'M12 3l2.7 5.600 6.100.900-4.400 4.300 1 6.100L12 17l-5.400 2.900 1-6.100L3.200 9.500l6.100-.900L12 3z',
        'quote' => 'M5 18c0-5 1-9 5-11M4 11h5v7H4zM14 18c0-5 1-9 5-11M13 11h5v7h-5z',
        'folder' => 'M3 6h6l2 2h10v11H3z',
        'up' => 'M12 19V5M6 11l6-6 6 6',
        'down' => 'M12 5v14M6 13l6 6 6-6',
        'x-circle' => 'M12 3a9 9 0 100 18 9 9 0 000-18zM9 9l6 6M15 9l-6 6',
        'inbox' => 'M3 13l3-8h12l3 8v6H3zM3 13h5l1 3h6l1-3h5',
        'link' => 'M10 14a4 4 0 005.700 0l3-3a4 4 0 00-5.700-5.700L12 6.300M14 10a4 4 0 00-5.700 0l-3 3a4 4 0 005.700 5.700L12 17.700',
        'wrench' => 'M14.500 6.500a4 4 0 005 5L21 13l-8 8-2-2 .5-3.500L8 13 3 8l2.500-2.500L10 7l2-2 2.500 1.500z',
        'external' => 'M14 4h6v6M20 4l-9 9M18 14v6H4V6h6',
    ];
}

function icon(string $name, string $class = '', int $size = 24): string
{
    static $paths = null;
    $paths ??= icon_paths();
    $d = $paths[$name] ?? $paths['check'];
    $cls = trim('ic ' . $class);
    return '<svg class="' . e($cls) . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" '
        . 'stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
        . '<path d="' . e($d) . '"/></svg>';
}

/** Icons selectable for services in the dashboard. */
function service_icon_choices(): array
{
    return ['home' => 'Haus', 'box' => 'Karton', 'garage' => 'Garage', 'trash' => 'Tonne', 'building' => 'Gebäude',
        'broom' => 'Besen', 'briefcase' => 'Aktenkoffer', 'truck' => 'Transporter', 'leaf' => 'Blatt', 'check' => 'Haken', 'clipboard' => 'Checkliste'];
}
