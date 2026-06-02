<?php
/* ============================================================
   BAMBOO SEGUROS · ÍCONOS SVG INLINE
   bb_icon('nombre', $extraClass) -> <svg> stroke=currentColor.
   Sin dependencias (no FontAwesome). 24x24, stroke 1.7.
   ============================================================ */

function bb_icon(string $name, string $cls = ''): string {
    $paths = [
        // ---- Servicios ----
        'car'        => '<path d="M5 11l1.5-4.5A2 2 0 0 1 8.4 5h7.2a2 2 0 0 1 1.9 1.5L19 11M5 11h14M5 11a2 2 0 0 0-2 2v3h2m14-5a2 2 0 0 1 2 2v3h-2M7 16h10M6 16v1.5M18 16v1.5M7.5 13.5h0M16.5 13.5h0"/>',
        'heart'      => '<path d="M19.5 12.6 12 20l-7.5-7.4A4.6 4.6 0 0 1 12 6a4.6 4.6 0 0 1 7.5 6.6Z"/><path d="M3.5 12.5H8l1.5-3 2.5 6 1.5-3h4.5"/>',
        'plane'      => '<path d="M10.5 13.5 3 11l1-2 8 1 4.2-4.2a2 2 0 0 1 2.8 2.8L15 13l1 8-2 1-2.5-7.5L8 16l.2 2.5-1.7.9-1.2-3.4-3.4-1.2.9-1.7L5 14"/>',
        'shield-user'=> '<path d="M12 3l7 2.5v5c0 4.7-3 8.4-7 9.5-4-1.1-7-4.8-7-9.5v-5L12 3Z"/><circle cx="12" cy="10" r="2.2"/><path d="M8.5 16a3.6 3.6 0 0 1 7 0"/>',
        'home'       => '<path d="M4 11.5 12 5l8 6.5"/><path d="M6 10.5V19h12v-8.5"/><path d="M10 19v-4.5h4V19"/>',
        'scale'      => '<path d="M12 4v16M7 20h10M5 8l3.5-1.5L12 5l3.5 1.5L19 8"/><path d="M5 8 2.8 13a2.4 2.4 0 0 0 4.4 0L5 8Zm14 0-2.2 5a2.4 2.4 0 0 0 4.4 0L19 8Z"/>',
        'document'   => '<path d="M7 3h7l4 4v14H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/><path d="M14 3v4h4M9 12h6M9 16h6M9 8h2"/>',
        'truck'      => '<path d="M3 6h11v9H3zM14 9h4l3 3v3h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17.5" cy="17.5" r="1.8"/>',
        'key'        => '<circle cx="8" cy="8" r="3.5"/><path d="m10.5 10.5 8 8M16 16l2-2M14 18l2-2"/>',
        'piggy'      => '<path d="M4 12.5C4 9.5 6.7 7 11 7c4.3 0 7 2.5 7 5.5 0 1.7-.9 3.2-2.3 4.2V19h-2v-1.3a9 9 0 0 1-5.4 0V19H6.3v-2.3C5 15.7 4 14.2 4 12.5Z"/><path d="M4.5 11.5 2.8 10M15 8.5 12.5 7M8.5 11h0M18 12.5h1.5"/>',
        'hardhat'    => '<path d="M4 17a8 8 0 0 1 16 0M4 17h16M4 17v1.5h16V17M10 9.2V7a2 2 0 0 1 4 0v2.2M9 9.5C6.5 10.5 5 13 5 15.5M15 9.5c2.5 1 4 3.5 4 6"/>',
        'briefcase'  => '<rect x="3" y="7.5" width="18" height="12" rx="1.6"/><path d="M9 7.5V6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1.5M3 12.5h18M11 12h2"/>',

        // ---- UI ----
        'whatsapp'   => '<path d="M12 3.5A8.5 8.5 0 0 0 4.5 16L3.5 20l4.1-1A8.5 8.5 0 1 0 12 3.5Z"/><path d="M9 8.5c-.3 0-.6.1-.8.4-.3.3-.9.9-.9 2s.9 2.3 1 2.5c.1.2 1.7 2.8 4.3 3.7 2.1.7 2.6.6 3 .5.6-.1 1.4-.6 1.6-1.2.2-.6.2-1 .1-1.2-.1-.1-.3-.2-.6-.4l-1.6-.8c-.2-.1-.4-.1-.6.1l-.6.8c-.1.2-.3.2-.5.1-.6-.2-1.4-.5-2.2-1.3-.6-.6-1-1.3-1.2-1.6-.1-.2 0-.3.1-.5l.4-.5c.1-.2.1-.3.2-.5v-.4l-.7-1.7c-.2-.4-.4-.4-.6-.4Z"/>',
        'phone'      => '<path d="M6 3.5h3l1.5 4-2 1.3a11 11 0 0 0 5.2 5.2l1.3-2 4 1.5v3a2 2 0 0 1-2.2 2A16 16 0 0 1 4 5.7 2 2 0 0 1 6 3.5Z"/>',
        'mail'       => '<rect x="3" y="5.5" width="18" height="13" rx="2"/><path d="m4 7 8 6 8-6"/>',
        'arrow'      => '<path d="M5 12h13M13 6l6 6-6 6"/>',
        'arrow-down' => '<path d="M12 5v13M6 13l6 6 6-6"/>',
        'check'      => '<path d="m5 12.5 4.5 4.5L19 7"/>',
        'check-circle'=> '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.2 2.4 2.4 4.6-4.8"/>',
        'shield-check'=> '<path d="M12 3l7 2.5v5c0 4.7-3 8.4-7 9.5-4-1.1-7-4.8-7-9.5v-5L12 3Z"/><path d="m9 11.5 2 2 4-4.2"/>',
        'star'       => '<path d="M12 4l2.4 5 5.5.6-4.1 3.7 1.2 5.4L12 16l-5 2.7 1.2-5.4L4 9.6 9.6 9 12 4Z"/>',
        'chat'       => '<path d="M5 5h14a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H9l-4 3.5V6a1 1 0 0 1 1-1Z"/><path d="M8.5 10h7M8.5 12.8h4"/>',
        'clock'      => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'compare'    => '<path d="M12 4v16M7 8 4 12l3 4M17 8l3 4-3 4M4 12h6M14 12h6"/>',
        'handshake'  => '<path d="m11 7-2.5 2.5a1.5 1.5 0 0 0 2.1 2.1L12 10.5l2 2a1.5 1.5 0 0 0 2.1-2.1L13 7.2a2 2 0 0 0-2.8 0L9 8.5"/><path d="M3 8.5 6 7l5 .5M21 8.5 18 7l-3 1M3 8.5v6l3 1.5M21 8.5v6l-3 1.5"/>',
        'menu'       => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close'      => '<path d="M6 6l12 12M18 6 6 18"/>',
        'leaf'       => '<path d="M5 19c0-7 5-13 14-14 .5 6-2 13-9 13-2 0-3.5-1-3.5-1M6 18 12 11"/>',
        'instagram'  => '<rect x="4" y="4" width="16" height="16" rx="4.5"/><circle cx="12" cy="12" r="3.4"/><circle cx="17" cy="7" r="1"/>',
        'facebook'   => '<path d="M14 8.5h2V5.5h-2.3C11.7 5.5 11 6.8 11 8.3V10H9v3h2v6h3v-6h2.2l.3-3H14V8.8c0-.2.1-.3.4-.3Z"/>',
        'linkedin'   => '<rect x="4" y="4" width="16" height="16" rx="2.5"/><path d="M8 10v6M8 7.5v.5M12 16v-3.2c0-1 .8-1.8 1.8-1.8s1.7.8 1.7 1.8V16M12 11v5"/>',
        'pin'        => '<path d="M12 21c4-4.5 6-8 6-11a6 6 0 1 0-12 0c0 3 2 6.5 6 11Z"/><circle cx="12" cy="10" r="2.3"/>',
        'login'      => '<path d="M10 4H6.5A1.5 1.5 0 0 0 5 5.5v13A1.5 1.5 0 0 0 6.5 20H10"/><path d="M14 12H21M18 9l3 3-3 3"/>',
        'user'       => '<circle cx="12" cy="8" r="3.4"/><path d="M5.5 19a6.5 6.5 0 0 1 13 0"/>',
    ];
    $d = $paths[$name] ?? '';
    $c = $cls ? ' ' . htmlspecialchars($cls, ENT_QUOTES) : '';
    return '<svg class="bb-ic' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
        . 'stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
        . $d . '</svg>';
}
