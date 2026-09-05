@php
    use App\Models\StoreSetting;

    $settings = StoreSetting::current();

    $primaryColor = $settings->dashboard_primary_color ?: '';
    $sidebarColor = $settings->dashboard_sidebar_color ?: '';
    $sidebarTextColor = $settings->dashboard_sidebar_text_color ?: '';
    $topbarColor = $settings->dashboard_topbar_color ?: '';

    $buttonRadius = (int) ($settings->dashboard_button_radius ?: 8);
    $cardRadius = (int) ($settings->dashboard_card_radius ?: 12);
    $loginBackgroundImage = $settings->login_background_image
    ? asset('storage/' . $settings->login_background_image)
    : '';

$loginBackgroundOpacity = $settings->login_background_opacity ?: 0.35;
$loginCardBackgroundColor = $settings->login_card_background_color ?: '#ffffff';
$loginCardOpacity = $settings->login_card_opacity ?: 0.92;
$loginCardBlur = (bool) $settings->login_card_blur;

$hexToRgb = function (?string $hex, string $fallback = '255, 255, 255') {
    if (! $hex) {
        return $fallback;
    }

    $hex = str_replace('#', '', $hex);

    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    if (strlen($hex) !== 6) {
        return $fallback;
    }

    return hexdec(substr($hex, 0, 2)) . ', ' .
        hexdec(substr($hex, 2, 2)) . ', ' .
        hexdec(substr($hex, 4, 2));
};

/*
 * Dark mode needs its own primary.
 *
 * ELLE's brand colour is near black (#111111), so a "primary" button painted
 * with it disappears against the dark dashboard. Rather than hard-coding a
 * second colour, work out how light the brand colour is and, when it is too
 * dark to sit on a dark surface, lift it towards white. If the shop later
 * picks a bright colour, this leaves it untouched.
 */
$relativeLuminance = function (?string $hex) use ($hexToRgb): float {
    [$r, $g, $b] = array_map('intval', explode(', ', $hexToRgb($hex, '17, 17, 17')));

    $channel = function (int $value): float {
        $v = $value / 255;

        return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
    };

    return 0.2126 * $channel($r) + 0.7152 * $channel($g) + 0.0722 * $channel($b);
};

$mixWithWhite = function (?string $hex, float $amount) use ($hexToRgb): string {
    [$r, $g, $b] = array_map('intval', explode(', ', $hexToRgb($hex, '17, 17, 17')));

    $lift = fn (int $c): int => (int) round($c + (255 - $c) * $amount);

    return sprintf('#%02x%02x%02x', $lift($r), $lift($g), $lift($b));
};

$primaryLuminance = $relativeLuminance($primaryColor ?: '#111111');

// Below this the colour cannot carry white text on a dark panel.
$primaryIsDark = $primaryLuminance < 0.25;

$darkPrimary = $primaryIsDark
    ? $mixWithWhite($primaryColor ?: '#111111', 0.92)
    : ($primaryColor ?: '#111111');

// Text that sits on top of a primary-coloured button, per mode.
$onPrimary = $primaryIsDark ? '#ffffff' : '#111827';
$onDarkPrimary = $primaryIsDark ? '#111827' : '#ffffff';

$loginCardRgb = $hexToRgb($loginCardBackgroundColor);

/*
 * The login card in dark mode.
 *
 * The shop picks a card colour for the light theme; painting that same light
 * colour in dark mode left white text on a white card - the whole form was
 * unreadable. Rather than add a second colour picker, darken the chosen one:
 * a light choice becomes a deep neutral panel, and a shop that already picked
 * something dark keeps it.
 */
$mixWithBlack = function (?string $hex, float $amount) use ($hexToRgb): string {
    [$r, $g, $b] = array_map('intval', explode(', ', $hexToRgb($hex, '255, 255, 255')));

    $drop = fn (int $c): int => (int) round($c * (1 - $amount));

    return sprintf('#%02x%02x%02x', $drop($r), $drop($g), $drop($b));
};

$loginCardIsLight = $relativeLuminance($loginCardBackgroundColor) > 0.35;

$loginCardDarkRgb = $loginCardIsLight
    ? '24, 24, 27'
    : $hexToRgb($mixWithBlack($loginCardBackgroundColor, 0.25));
$loginLogo = $settings->dashboard_logo
    ? asset('storage/' . $settings->dashboard_logo)
    : '';
 $loginLogoWidth = (int) ($settings->login_logo_width ?: 96);
$loginLogoHeight = (int) ($settings->login_logo_height ?: 96);
@endphp

<style>
    :root {
        --dashboard-primary-color: {{ $primaryColor }};
        --dashboard-sidebar-color: {{ $sidebarColor }};
        --dashboard-sidebar-text-color: {{ $sidebarTextColor }};
        --dashboard-topbar-color: {{ $topbarColor }};
        --dashboard-button-radius: {{ $buttonRadius }}px;
        --dashboard-card-radius: {{ $cardRadius }}px;

        /* Colour actually used for primary surfaces, per colour scheme. */
        --dashboard-primary-active: {{ $primaryColor ?: '#111111' }};
        --dashboard-on-primary: {{ $onPrimary }};
    }

    /* Filament puts .dark on <html> when the dark theme is on. */
    html.dark {
        --dashboard-primary-active: {{ $darkPrimary }};
        --dashboard-on-primary: {{ $onDarkPrimary }};
    }

    .fi-sidebar {
        background-color: var(--dashboard-sidebar-color) !important;
    }

    .fi-sidebar a,
    .fi-sidebar button,
    .fi-sidebar span,
    .fi-sidebar svg {
        color: var(--dashboard-sidebar-text-color) !important;
    }

    /*
     * Hover / keyboard-focus state.
     * Filament paints its own light background on hover (hover:bg-gray-100),
     * which made the near-white sidebar label vanish. Mirror the active item
     * instead: light background, dark label, primary-coloured icon.
     * Scoped to .fi-sidebar-nav so the brand logo in the header is untouched.
     */
    .fi-sidebar-nav a:hover,
    .fi-sidebar-nav a:focus-visible,
    .fi-sidebar-nav button:hover,
    .fi-sidebar-nav button:focus-visible {
        background: linear-gradient(90deg,
                color-mix(in srgb, var(--dashboard-primary-color) 14%, white),
                color-mix(in srgb, var(--dashboard-primary-color) 5%, white)) !important;
        border-radius: 14px !important;
    }

    /* Hovered label */
    .fi-sidebar-nav a:hover span,
    .fi-sidebar-nav a:focus-visible span,
    .fi-sidebar-nav button:hover span,
    .fi-sidebar-nav button:focus-visible span {
        color: #111827 !important;
    }

    /* Hovered icon */
    .fi-sidebar-nav a:hover svg,
    .fi-sidebar-nav a:focus-visible svg,
    .fi-sidebar-nav button:hover svg,
    .fi-sidebar-nav button:focus-visible svg {
        color: var(--dashboard-primary-color) !important;
    }

    /* Active sidebar item */
    .fi-sidebar a[aria-current="page"],
    .fi-sidebar .fi-sidebar-item.fi-active>a,
    .fi-sidebar .fi-sidebar-item.fi-active button,
    .fi-sidebar .fi-sidebar-item-active>a {
        background: linear-gradient(90deg,
                color-mix(in srgb, var(--dashboard-primary-color) 18%, white),
                color-mix(in srgb, var(--dashboard-primary-color) 7%, white)) !important;
        border-radius: 14px !important;
        border-inline-start: 4px solid var(--dashboard-primary-color) !important;
        font-weight: 700 !important;
    }

    /* Active item text */
    .fi-sidebar a[aria-current="page"] span,
    .fi-sidebar .fi-sidebar-item.fi-active span,
    .fi-sidebar .fi-sidebar-item-active span {
        color: #111827 !important;
        font-weight: 700 !important;
    }

    /* Active item icon */
    .fi-sidebar a[aria-current="page"] svg,
    .fi-sidebar .fi-sidebar-item.fi-active svg,
    .fi-sidebar .fi-sidebar-item-active svg {
        color: var(--dashboard-primary-color) !important;
    }

    .fi-topbar {
        background-color: var(--dashboard-topbar-color) !important;
    }

    .fi-btn {
        border-radius: var(--dashboard-button-radius) !important;
    }

    .fi-section,
    .fi-ta,
    .fi-fo-section,
    .fi-in-section {
        border-radius: var(--dashboard-card-radius) !important;
    }

    /*
     * Primary surfaces use --dashboard-primary-active, which flips to a light
     * shade in dark mode. With a near-black brand colour these buttons were
     * black on a dark panel - present but invisible.
     */
    .fi-btn-color-primary {
        background-color: var(--dashboard-primary-active) !important;
        color: var(--dashboard-on-primary) !important;
        border-color: var(--dashboard-primary-active) !important;
    }

    .fi-btn-color-primary svg,
    .fi-btn-color-primary .fi-btn-label {
        color: var(--dashboard-on-primary) !important;
    }

    .fi-btn-color-primary:hover {
        filter: brightness(0.92);
    }

    /* Outlined primary buttons (the "New product" style header action). */
    .dark .fi-btn-outlined.fi-btn-color-primary,
    .dark .fi-btn-color-gray {
        border-color: rgba(255, 255, 255, 0.25) !important;
    }

    .fi-tabs-item-active {
        color: var(--dashboard-primary-active) !important;
        border-color: var(--dashboard-primary-active) !important;
    }

    .fi-link,
    .fi-breadcrumbs a {
        color: var(--dashboard-primary-active) !important;
    }

    input[type="checkbox"]:checked,
    input[type="radio"]:checked {
        background-color: var(--dashboard-primary-active) !important;
        border-color: var(--dashboard-primary-active) !important;
    }

    /*
     * The ELLE wordmark is solid black artwork, so it vanishes on the dark
     * topbar and the dark login card. Render it white there.
     */
    html.dark .fi-logo,
    html.dark img.fi-logo,
    html.dark .fi-sidebar-header img,
    html.dark .fi-topbar img.fi-logo,
    html.dark .fi-simple-header img.fi-logo {
        filter: brightness(0) invert(1);
    }
    /* Login Page Appearance */
.fi-simple-layout {
    position: relative !important;
    min-height: 100vh !important;
    background: transparent !important;
    overflow: hidden;
}

.fi-simple-layout::before {
    content: "";
    position: fixed;
    inset: 0;
    background-image: url('{{ $loginBackgroundImage }}');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    opacity: {{ $loginBackgroundImage ? $loginBackgroundOpacity : 0 }};
    z-index: -2;
}

.fi-simple-layout::after {
    content: "";
    position: fixed;
    inset: 0;
    background: rgba(255, 255, 255, 0.18);
    z-index: -1;
}

/* In dark mode the wash darkens the page instead of bleaching it. */
html.dark .fi-simple-layout::after {
    background: rgba(9, 9, 11, 0.55);
}

html.dark .fi-simple-layout {
    background-color: #09090b !important;
}

.fi-simple-main {
    background: rgba({{ $loginCardRgb }}, {{ $loginCardOpacity }}) !important;
    border-radius: 24px !important;
    border: 1px solid rgba(255, 255, 255, 0.35) !important;
    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.12) !important;
    padding: 20px !important;
    max-width: 520px !important;
    width: 100% !important;
    height: auto !important;
    {{ $loginCardBlur ? 'backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);' : '' }}
    
}

/*
 * Dark mode.
 *
 * The card colour is a light-theme choice, so in dark mode it is replaced by a
 * dark panel. Without this the card stayed white while Filament switched every
 * label, heading and input to white text - the form was there but unreadable.
 */
html.dark .fi-simple-main {
    background: rgba({{ $loginCardDarkRgb }}, {{ $loginCardOpacity }}) !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
    box-shadow: 0 24px 70px rgba(0, 0, 0, 0.55) !important;
}

/* Headings and helper text on the dark card. */
html.dark .fi-simple-main h1,
html.dark .fi-simple-main .fi-simple-header-heading {
    color: #f4f4f5 !important;
}

html.dark .fi-simple-main .fi-simple-header-subheading,
html.dark .fi-simple-main .fi-fo-field-wrp-hint,
html.dark .fi-simple-main .fi-fo-field-wrp-helper-text {
    color: #a1a1aa !important;
}

html.dark .fi-simple-main .fi-fo-field-wrp-label,
html.dark .fi-simple-main label {
    color: #e4e4e7 !important;
}

/* Inputs: a slightly lifted surface so the field edges are visible. */
html.dark .fi-simple-main .fi-input-wrp {
    background-color: rgba(255, 255, 255, 0.04) !important;
    box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.10) !important;
}

/* Focus ring uses the panel accent, the same colour the light theme shows. */
html.dark .fi-simple-main .fi-input-wrp:focus-within {
    box-shadow: 0 0 0 2px rgb(var(--primary-500)) !important;
}

html.dark .fi-simple-main .fi-input {
    color: #fafafa !important;
}

html.dark .fi-simple-main .fi-input::placeholder {
    color: #71717a !important;
}

html.dark .fi-simple-main .fi-icon-btn,
html.dark .fi-simple-main .fi-input-wrp-suffix svg,
html.dark .fi-simple-main .fi-input-wrp-prefix svg {
    color: #a1a1aa !important;
}

/* Links (forgot password). */
html.dark .fi-simple-main a {
    color: var(--dashboard-primary-active) !important;
}

/*
 * The theme switcher on the login page.
 *
 * The login screen has no topbar and no user menu, so it was the only place
 * with no way to switch theme. It floats at the top of the page, outside the
 * card, in a pill of its own.
 */
.fi-login-theme-switcher {
    position: fixed;
    top: 18px;
    inset-inline-end: 20px;
    z-index: 50;
    padding: 4px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.78);
    box-shadow: 0 6px 20px rgba(15, 23, 42, 0.12);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

html.dark .fi-login-theme-switcher {
    background: rgba(255, 255, 255, 0.07);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.5);
}

html.dark .fi-login-theme-switcher .fi-icon-btn {
    color: #a1a1aa !important;
}

html.dark .fi-login-theme-switcher .fi-icon-btn:hover {
    color: #fafafa !important;
}

.fi-simple-main .fi-logo {
    margin-bottom: 10px !important;
    justify-content: center !important;
}

.fi-simple-main .fi-logo img {
    max-height: 76px !important;
    object-fit: contain !important;
}

.fi-simple-main h1,
.fi-simple-main .fi-simple-header-heading {
    text-align: center !important;
}

.fi-simple-main form {
    margin-top: 24px !important;
}
/* Force Login Logo Above Store Name */

/* Store name under logo */
.fi-simple-main .fi-logo {
    margin-bottom: 8px !important;
    justify-content: center !important;
    text-align: center !important;
    font-size: 22px !important;
    font-weight: 800 !important;
}

/* Login title */
.fi-simple-main .fi-simple-header-heading {
    margin-top: 4px !important;
    text-align: center !important;
}
/* Login Page Header Order: Logo then Sign in only */
.fi-simple-header {
    gap: 14px !important;
}

/* Keep only Filament real logo */
.fi-simple-header .fi-logo {
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    margin-bottom: 0 !important;
}

/* Logo image size */
/* Login Logo Size */
.fi-simple-header img.fi-logo {
    width: {{ $loginLogoWidth }}px !important;
    height: {{ $loginLogoHeight }}px !important;
    max-width: {{ $loginLogoWidth }}px !important;
    max-height: {{ $loginLogoHeight }}px !important;
    object-fit: contain !important;
}

/* Remove Filament default small logo height */
.fi-simple-header .fi-logo {
    height: {{ $loginLogoHeight }}px !important;
}

/* If logo is image, hide any text beside it */
.fi-simple-header .fi-logo span {
    display: none !important;
}

/* Sign in directly under logo */
.fi-simple-header-heading {
    margin-top: 0 !important;
    text-align: center !important;
}
</style>
