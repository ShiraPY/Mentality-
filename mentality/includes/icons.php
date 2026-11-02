<?php
// includes/icons.php - the Mentality logo, a small icon set, and the mood faces. Everything is drawn as inline SVG,
// so it works offline, stays sharp at any size, and follows the page colours (icons use "currentColor").

// ---- Logo: two mountain peaks with snow and a little sun/moon
function brand_logo(int $size = 40): string {
    static $n = 0; $n++;
    $id = 'lg' . $n;
    return '<svg class="brand-logo" width="' . $size . '" height="' . $size . '" viewBox="0 0 64 64" role="img" aria-label="Mentality logo">'
        . '<defs><linearGradient id="' . $id . '" x1="0" y1="0" x2="1" y2="1">'
        . '<stop offset="0" style="stop-color:#8fb8ff"/><stop offset="1" style="stop-color:var(--primary,#3b82f6)"/></linearGradient></defs>'
        . '<path d="M3 54 L23 17 L33 35 L41 24 L61 54 Z" fill="url(#' . $id . ')"/>'
        . '<path d="M23 17 L16.5 29 L21 26.5 L24 31 L27 26.5 L29.8 29 Z" fill="#fff" opacity=".92"/>'
        . '<path d="M41 24 L35.2 32 L39 30 L41.5 33 L44 30 L46.3 32 Z" fill="#fff" opacity=".85"/>'
        . '<circle cx="50" cy="13" r="5" fill="#ffd978"/></svg>';
}

// ---- Logo + name + tagline shown at the top of the login / register / reset pages
function auth_brand(): string {
    return '<div class="auth-brand">' . brand_logo(64)
         . '<div class="auth-brand-name">MENTALITY</div>'
         . '<div class="auth-tagline">Build better habits. Care for your mind.</div></div>';
}

// ---- Icons (24x24 grid, rounded line style)
function icon(string $name, int $size = 20, string $class = ''): string {
    static $p = null;
    if ($p === null) {
        $p = [
            'dashboard' => '<rect x="3" y="3" width="7.5" height="7.5" rx="2"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="2"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="2"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="2"/>',
            'habits'    => '<circle cx="12" cy="12" r="9"/><polyline points="7.8 12.4 10.8 15.4 16.4 9.2"/>',
            'goals'     => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.2"/>',
            'mood'      => '<circle cx="12" cy="12" r="9"/><path d="M8 14.2c1 1.6 2.3 2.4 4 2.4s3-.8 4-2.4"/><line x1="9" y1="9.5" x2="9.01" y2="9.5"/><line x1="15" y1="9.5" x2="15.01" y2="9.5"/>',
            'stats'     => '<line x1="6" y1="20" x2="6" y2="11"/><line x1="12" y1="20" x2="12" y2="4.5"/><line x1="18" y1="20" x2="18" y2="14"/>',
            'bell'      => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
            'settings'  => '<line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/><circle cx="9" cy="6" r="2.3" fill="var(--card)"/><circle cx="15" cy="12" r="2.3" fill="var(--card)"/><circle cx="8" cy="18" r="2.3" fill="var(--card)"/>',
            'users'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 3-6 6.5-6s6.5 2.4 6.5 6"/><circle cx="17" cy="9" r="2.6"/><path d="M17.5 14.2c2.6.3 4.5 2.2 4.5 5.3"/>',
            'logout'    => '<path d="M9 4H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h4"/><polyline points="16 8 20 12 16 16"/><line x1="20" y1="12" x2="9" y2="12"/>',
            'edit'      => '<path d="M17 3a2.8 2.8 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>',
            'trash'     => '<polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>',
            'plus'      => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
            'x'         => '<line x1="6" y1="6" x2="18" y2="18"/><line x1="18" y1="6" x2="6" y2="18"/>',
            'download'  => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
            'moon'      => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
            'sun'       => '<circle cx="12" cy="12" r="4"/><line x1="12" y1="2" x2="12" y2="4.5"/><line x1="12" y1="19.5" x2="12" y2="22"/><line x1="2" y1="12" x2="4.5" y2="12"/><line x1="19.5" y1="12" x2="22" y2="12"/><line x1="4.9" y1="4.9" x2="6.6" y2="6.6"/><line x1="17.4" y1="17.4" x2="19.1" y2="19.1"/><line x1="4.9" y1="19.1" x2="6.6" y2="17.4"/><line x1="17.4" y1="6.6" x2="19.1" y2="4.9"/>',
            'lock'      => '<rect x="4" y="11" width="16" height="10" rx="2.5"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
            'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2.5"/><polyline points="3.5 7.5 12 13 20.5 7.5"/>',
            'user'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>',
            'eye'       => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
            'eye-off'   => '<path d="M2 12s3.6-7 10-7c2 0 3.8.7 5.3 1.6M22 12s-3.6 7-10 7c-2 0-3.8-.7-5.3-1.6"/><circle cx="12" cy="12" r="3"/><line x1="3" y1="3" x2="21" y2="21"/>',
            'message'   => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>',
            'database'  => '<ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/><path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/>',
            'check'     => '<polyline points="5 12.5 10 17.5 19 7"/>',
        ];
    }
    if (!isset($p[$name])) { return ''; }
    return '<svg class="icon' . ($class !== '' ? ' ' . $class : '') . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" '
         . 'stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p[$name] . '</svg>';
}

// ---- Mood faces: round, colour-coded, hand-drawn expressions (replaces the system emojis)
function mood_face(string $mood, int $size = 44): string {
    static $n = 0; $n++;
    $id = 'mf' . $n;
    $colors = [
        'Great'      => ['#86efac', '#22c55e'],
        'Good'       => ['#d9f99d', '#84cc16'],
        'Okay'       => ['#fde68a', '#facc15'],
        'Low'        => ['#fdba74', '#f97316'],
        'Frustrated' => ['#fca5a5', '#ef4444'],
        'Tired'      => ['#e9d5ff', '#a855f7'],
    ];
    [$light, $base] = $colors[$mood] ?? ['#e2e8f0', '#94a3b8'];

    $dots = '<circle cx="17" cy="22" r="2.3" fill="#1f2937" stroke="none"/><circle cx="31" cy="22" r="2.3" fill="#1f2937" stroke="none"/>';
    switch ($mood) {
        case 'Great':
            $f = '<path d="M14.5 22.5q3-5 6 0"/><path d="M27.5 22.5q3-5 6 0"/>'
               . '<path d="M14.5 29Q24 42 33.5 29Z" fill="#1f2937"/><path d="M19.5 35.5q4.5 3.2 9 0" fill="#fb7185" stroke="none"/>';
            break;
        case 'Good':
            $f = $dots . '<path d="M15.5 29.5Q24 38 32.5 29.5"/>';
            break;
        case 'Okay':
            $f = $dots . '<path d="M17 32.5H31"/>';
            break;
        case 'Low':
            $f = $dots . '<path d="M17 35Q24 28.5 31 35"/><path d="M36.5 27q1.6 2.4 0 4.2q-1.6-1.8 0-4.2z" fill="#7dd3fc" stroke="none"/>';
            break;
        case 'Frustrated':
            $f = '<path d="M12.5 16.5L20.5 20.5"/><path d="M35.5 16.5L27.5 20.5"/>'
               . '<circle cx="17" cy="24" r="2.3" fill="#1f2937" stroke="none"/><circle cx="31" cy="24" r="2.3" fill="#1f2937" stroke="none"/>'
               . '<path d="M16.5 35.5Q24 29.5 31.5 35.5"/>';
            break;
        case 'Tired':
            $f = '<path d="M13.5 22.5q3.5 3.4 7 0"/><path d="M27.5 22.5q3.5 3.4 7 0"/>'
               . '<ellipse cx="24" cy="33.5" rx="3.2" ry="2.6" fill="#1f2937" stroke="none"/>'
               . '<path d="M35.5 8.5h5l-5 6h5" stroke-width="2"/>';
            break;
        default:
            $f = $dots . '<path d="M17 32.5H31"/>';
    }
    return '<svg class="mood-svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 48 48" role="img" aria-label="' . htmlspecialchars($mood, ENT_QUOTES) . '">'
         . '<defs><radialGradient id="' . $id . '" cx="35%" cy="28%" r="80%"><stop offset="0" stop-color="' . $light . '"/><stop offset="1" stop-color="' . $base . '"/></radialGradient></defs>'
         . '<circle cx="24" cy="24" r="22" fill="url(#' . $id . ')"/>'
         . '<ellipse cx="17" cy="12" rx="9" ry="4.5" fill="#fff" opacity=".22"/>'
         . '<g stroke="#1f2937" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" fill="none">' . $f . '</g></svg>';
}
