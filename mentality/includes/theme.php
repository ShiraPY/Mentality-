<?php
// includes/theme.php - appearance helpers: fonts, accent colours, colour checks, and the custom-colour CSS.

const THEME_FONTS = [
    'modern'  => ['Modern',  '"Segoe UI", system-ui, Arial, sans-serif'],
    'rounded' => ['Rounded', '"Trebuchet MS", "Segoe UI", sans-serif'],
    'classic' => ['Classic', 'Georgia, "Times New Roman", serif'],
    'clean'   => ['Clean',   'Verdana, Geneva, sans-serif'],
    'mono'    => ['Mono',    'Consolas, "Courier New", monospace'],
];

// Accent colours (all dark enough for white button text)
const THEME_ACCENTS = [
    '#3b82f6' => 'Blue (default)',
    '#4f46e5' => 'Indigo',
    '#7c3aed' => 'Purple',
    '#db2777' => 'Pink',
    '#dc2626' => 'Red',
    '#ea580c' => 'Orange',
    '#059669' => 'Green',
    '#0d9488' => 'Teal',
];
const DEFAULT_ACCENT = '#3b82f6';

function valid_hex($c): bool {
    return is_string($c) && preg_match('/^#[0-9a-fA-F]{6}$/', $c) === 1;
}

// Brightness of a colour (0 = black, 1 = white), as defined by the WCAG accessibility standard.
function hex_luminance(string $hex): float {
    $hex = ltrim($hex, '#');
    $c = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    foreach ($c as &$v) {
        $v /= 255;
        $v = ($v <= 0.03928) ? $v / 12.92 : pow(($v + 0.055) / 1.055, 2.4);
    }
    unset($v);
    return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
}

// How different two colours are (1 = identical, 21 = black on white). 4.5 or more is comfortable to read.
function contrast_ratio(string $a, string $b): float {
    $la = hex_luminance($a);
    $lb = hex_luminance($b);
    return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
}

function theme_defaults(string $theme): array {
    return $theme === 'light'
        ? ['bg' => '#eef2f9', 'card' => '#ffffff', 'text' => '#0f172a']
        : ['bg' => '#0b1220', 'card' => '#111a2e', 'text' => '#e8eefc'];
}

function default_ui(): array {
    return ['bg' => null, 'card' => null, 'text' => null, 'accent' => null, 'font' => 'modern'];
}

// Turns the user's saved choices into CSS. Every value is validated here again, so nothing unsafe can reach the page.
function custom_theme_css(array $ui): string {
    $d = [];
    $bg   = valid_hex($ui['bg']   ?? null) ? $ui['bg']   : null;
    $card = valid_hex($ui['card'] ?? null) ? $ui['card'] : null;
    $text = valid_hex($ui['text'] ?? null) ? $ui['text'] : null;
    $acc  = valid_hex($ui['accent'] ?? null) && isset(THEME_ACCENTS[$ui['accent']]) && $ui['accent'] !== DEFAULT_ACCENT ? $ui['accent'] : null;

    if ($bg)   { $d[] = '--bg:' . $bg; }
    if ($card) { $d[] = '--card:' . $card; }
    if ($text) { $d[] = '--text:' . $text; }
    if ($bg || $card || $text) {
        $d[] = '--input-bg:color-mix(in srgb, var(--bg) 72%, var(--card))';
        $d[] = '--hover:color-mix(in srgb, var(--text) 8%, var(--card))';
        $d[] = '--border:color-mix(in srgb, var(--text) 16%, var(--card))';
        $d[] = '--muted:color-mix(in srgb, var(--text) 62%, var(--card))';
    }
    if ($acc) {
        $d[] = '--primary:' . $acc;
        $d[] = '--primary-hover:color-mix(in srgb, ' . $acc . ' 82%, #000)';
        $d[] = '--link:color-mix(in srgb, ' . $acc . ' 70%, var(--text))';
    }
    $font = THEME_FONTS[$ui['font'] ?? 'modern'][1] ?? THEME_FONTS['modern'][1];
    $d[] = '--font:' . $font;

    $css = 'html:root[data-theme]{' . implode(';', $d) . '}';
    if ($bg) {
        $scheme = hex_luminance($bg) < 0.4 ? 'dark' : 'light';
        $css .= 'html:root[data-theme]{color-scheme:' . $scheme . '}html:root[data-theme] input[type=date]{color-scheme:' . $scheme . '}';
    }
    return $css;
}
