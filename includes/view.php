<?php
/*
 * Page layouts and small presentation helpers shared by every page.
 */

function icon(string $name, string $class = ''): string
{
    static $paths = [
        'home' => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10v9.5h13V10"/><path d="M10 19.5v-5h4v5"/>',
        'grid' => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>',
        'calendar' => '<rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
        'calendar-plus' => '<rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M3.5 10h17M8 3v4M16 3v4M12 12.5v5M9.5 15h5"/>',
        'clipboard' => '<rect x="5" y="4.5" width="14" height="16.5" rx="2.5"/><path d="M9 3.5h6v2.5H9zM9 11h6M9 15h6M9 19h3"/>',
        'users' => '<circle cx="9" cy="8.5" r="3.5"/><path d="M3 20a6 6 0 0 1 12 0"/><path d="M15.5 5.2a3.5 3.5 0 0 1 0 6.6M18 14.5a6 6 0 0 1 3 5.5"/>',
        'user' => '<circle cx="12" cy="8" r="3.75"/><path d="M4.75 20.25a7.25 7.25 0 0 1 14.5 0"/>',
        'stethoscope' => '<path d="M6 3.5v5a4 4 0 0 0 8 0v-5"/><path d="M10 12.5v2a5 5 0 0 0 10 0v-1.5"/><circle cx="20" cy="11" r="2"/>',
        'settings' => '<path d="M4 7h10M18 7h2M4 17h4M12 17h8"/><circle cx="16" cy="7" r="2"/><circle cx="10" cy="17" r="2"/>',
        'search' => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'edit' => '<path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/>',
        'trash' => '<path d="M4 7h16M9.5 7V4.5h5V7M6.5 7l1 13h9l1-13"/><path d="M10 11v5M14 11v5"/>',
        'eye' => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/>',
        'logout' => '<path d="M14 4.5h4.5A1.5 1.5 0 0 1 20 6v12a1.5 1.5 0 0 1-1.5 1.5H14"/><path d="m10 8-4 4 4 4M6 12h9"/>',
        'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 3 3 5-6"/>',
        'x' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'alert' => '<path d="M12 4 2.8 19.5h18.4L12 4Z"/><path d="M12 10v4.5M12 17.2v.1"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5.5M12 7.8v.1"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-left' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'download' => '<path d="M12 4v11M7 10.5l5 5 5-5M5 20h14"/>',
        'shield' => '<path d="M12 3.5 19 6v5.5c0 4.5-3 7.8-7 9-4-1.2-7-4.5-7-9V6l7-2.5Z"/><path d="m9 12 2 2 4-4"/>',
        'phone' => '<path d="M5 4h3.5l1.5 4-2 1.5a11 11 0 0 0 6.5 6.5l1.5-2 4 1.5V19a1.5 1.5 0 0 1-1.5 1.5A16 16 0 0 1 3.5 5.5 1.5 1.5 0 0 1 5 4Z"/>',
        'mail' => '<rect x="3.5" y="5.5" width="17" height="13" rx="2"/><path d="m4 7 8 6 8-6"/>',
        'map-pin' => '<path d="M12 21s7-6.1 7-11.5a7 7 0 0 0-14 0C5 14.9 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'heart-pulse' => '<path d="M12 20s-8-4.8-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 9c0 6.2-8 11-8 11Z"/><path d="M4.5 12h4l1.5-3 2.5 6 1.5-3h5.5"/>',
        'refresh' => '<path d="M19.5 12a7.5 7.5 0 0 1-13.3 4.7M4.5 12a7.5 7.5 0 0 1 13.3-4.7"/><path d="M18 3.5v4h-4M6 20.5v-4h4"/>',
        'lock' => '<rect x="5" y="10.5" width="14" height="10" rx="2"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/>',
        'accessibility' => '<circle cx="12" cy="4.5" r="1.8"/><path d="M5 8.5c4.5 1.3 9.5 1.3 14 0M12 9.8v4.2M8.5 21l3.5-7 3.5 7"/>',
        'whatsapp' => '<path class="icon-fill" d="M12 3a8.8 8.8 0 0 0-7.6 13.2L3.2 21l5-1.3A8.8 8.8 0 1 0 12 3m0 15.8a7 7 0 0 1-3.6-1l-.3-.2-3 .8.8-2.9-.2-.3A7 7 0 1 1 12 18.8m3.9-5.2c-.2-.1-1.4-.7-1.6-.8s-.4-.1-.5.1-.6.8-.8.9-.3.2-.5.1c-.2-.1-1-.4-1.9-1.2-.7-.6-1.2-1.4-1.3-1.6-.1-.2 0-.3.1-.5l.4-.4.2-.4c.1-.1 0-.3 0-.4l-.7-1.7c-.2-.4-.4-.4-.5-.4h-.5c-.2 0-.4.1-.6.3s-.8.8-.8 1.9.8 2.2.9 2.3c.1.2 1.6 2.5 3.9 3.4.5.2.9.3 1.2.4.5.2 1 .1 1.4.1.4-.1 1.4-.6 1.6-1.1.2-.5.2-1 .1-1.1-.1-.1-.2-.2-.4-.3"/>',
        'facebook' => '<path class="icon-fill" d="M13.5 21v-8h2.7l.4-3.1h-3.1v-2c0-.9.3-1.5 1.6-1.5h1.7V3.6c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3v2.1H7.3V13h2.8v8z"/>',
        'linkedin' => '<path class="icon-fill" d="M6.5 8.5A2 2 0 1 0 6.5 4.5a2 2 0 0 0 0 4M5 10h3v9H5zm5 0h2.9v1.2h.1a3.2 3.2 0 0 1 2.9-1.5c3.1 0 3.7 2 3.7 4.6V19h-3v-4.2c0-1 0-2.4-1.5-2.4s-1.8 1.1-1.8 2.3V19h-3z"/>',
        'x-brand' => '<path class="icon-fill" d="M18.9 3H22l-6.8 7.8L23.2 21h-6.3L12 14.7 6.5 21H3.3l7.3-8.4L2.8 3h6.5l4.5 5.9zm-1.1 16h1.7L8.3 4.9H6.5z"/>',
    ];
    $body = $paths[$name] ?? $paths['info'];
    return '<svg class="icon' . ($class !== '' ? ' ' . e($class) : '') . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $body . '</svg>';
}

/** Inline wordmark so it renders with the site font. */
function logo(string $variant = 'dark'): string
{
    $tile = $variant === 'light' ? '#c7ec83' : '#1d5949';
    $letter = $variant === 'light' ? '#1d5949' : '#f7f8f2';
    $leaf = $variant === 'light' ? '#1d5949' : '#c7ec83';
    $text = $variant === 'light' ? '#f7f8f2' : '#14302a';
    return '<svg class="logo" viewBox="0 0 248 56" aria-hidden="true" focusable="false">'
        . '<rect x="2" y="2" width="52" height="52" rx="18" fill="' . $tile . '"/>'
        . '<path d="M17 17v13.5a11 11 0 0 0 22 0V17h-7v13.5a4 4 0 0 1-8 0V17z" fill="' . $letter . '"/>'
        . '<path d="M34 12c5-6 12-5 15-4-1 7-5 12-12 12" fill="' . $leaf . '"/>'
        . '<text x="67" y="37" fill="' . $text . '" font-size="27" font-weight="500" letter-spacing="-1.2">uhai <tspan font-weight="700">bora</tspan></text>'
        . '</svg>';
}

function render_flashes(): string
{
    $html = '';
    foreach (take_flashes() as $flash) {
        $type = in_array($flash['type'], ['success', 'error', 'warning', 'info'], true) ? $flash['type'] : 'info';
        $role = in_array($type, ['error', 'warning'], true) ? 'alert' : 'status';
        $icons = ['success' => 'check-circle', 'error' => 'alert', 'warning' => 'alert', 'info' => 'info'];
        $html .= '<div class="alert alert--' . $type . '" role="' . $role . '">' . icon($icons[$type])
            . '<p>' . e($flash['message']) . '</p></div>';
    }
    return $html === '' ? '' : '<div class="flash-stack">' . $html . '</div>';
}

function nav_link(string $label, string $path, bool $current, string $class = ''): string
{
    return '<a class="' . e($class) . '" href="' . e(url($path)) . '"' . ($current ? ' aria-current="page"' : '') . '>' . e($label) . '</a>';
}

function page_head(array $page, array $stylesheets): void
{
    $errors = form_errors();
    $title = $page['title'] !== '' ? $page['title'] . ' | Uhai Bora' : 'Uhai Bora — Book appointments with listed doctors';
    if ($errors) {
        $title = 'Error: ' . $title;
    }
    ?>
<!DOCTYPE html>
<html lang="en-KE">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($page['description']) ?>">
    <?php if ($page['robots'] !== ''): ?><meta name="robots" content="<?= e($page['robots']) ?>"><?php endif; ?>
    <meta name="theme-color" content="#1d5949">
    <meta property="og:site_name" content="Uhai Bora">
    <meta property="og:title" content="<?= e($page['title'] !== '' ? $page['title'] : 'Uhai Bora') ?>">
    <meta property="og:description" content="<?= e($page['description']) ?>">
    <meta property="og:type" content="website">
    <link rel="icon" href="<?= e(asset('assets/brand/uhai-bora-mark.svg')) ?>" type="image/svg+xml">
    <link rel="preload" href="<?= e(url('css/fonts/inter/inter-latin-400.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?= e(url('css/fonts/inter/inter-latin-600.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
    <?php foreach ($stylesheets as $sheet): ?>
    <link rel="stylesheet" href="<?= e(asset('css/' . $sheet . '.css')) ?>">
    <?php endforeach; ?>
    <script src="<?= e(asset('js/site.js')) ?>"></script>
</head>
    <?php
}

/* ---------- Public website layout ---------- */

function page_start(array $page = []): void
{
    $page += [
        'title' => '',
        'description' => 'Uhai Bora helps you browse listed doctors and specialties, choose an available appointment session, and manage your bookings.',
        'nav' => '',
        'body_class' => '',
        'robots' => '',
    ];
    page_head($page, ['site']);
    echo '<body class="page ' . e($page['body_class']) . '">';
    echo '<a class="skip-link" href="#main">Skip to main content</a>';
    site_header($page['nav']);
    echo '<main id="main" class="site-main" tabindex="-1">';
    $flashes = render_flashes();
    if ($flashes !== '') {
        echo '<div class="container container--flash">' . $flashes . '</div>';
    }
}

function page_end(): void
{
    echo '</main>';
    site_footer();
    echo '</body></html>';
}

function account_menu(string $id, string $context = 'site'): void
{
    $user = current_user();
    ?>
    <div class="account account--<?= e($context) ?>" data-disclosure>
        <button class="account__toggle" type="button" aria-expanded="false" aria-controls="<?= e($id) ?>" data-disclosure-toggle>
            <?php if ($user && $context === 'app'): ?>
                <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($user['name'] !== '' ? $user['name'] : 'U', 0, 1))) ?></span>
                <span class="account__name"><?= e($user['name'] !== '' ? $user['name'] : role_info($user['role'])['label']) ?></span>
                <span class="visually-hidden">— account menu</span>
            <?php else: ?>
                <?= icon('user') ?><span>Account</span>
            <?php endif; ?>
            <?= icon('chevron-down', 'account__chevron') ?>
        </button>
        <div class="account__panel" id="<?= e($id) ?>" data-disclosure-panel>
            <?php if (!$user): ?>
                <ul class="account__list">
                    <li><a href="<?= e(url('signup.php')) ?>">Register</a></li>
                    <li><a href="<?= e(url('login.php')) ?>">Login</a></li>
                </ul>
            <?php else: $info = role_info($user['role']); ?>
                <p class="account__who">Signed in as <strong><?= e($user['name'] !== '' ? $user['name'] : $info['label']) ?></strong><span><?= e($info['label']) ?> account</span></p>
                <ul class="account__list">
                    <?php if ($context === 'site'): ?>
                        <li><a href="<?= e(url($info['home'])) ?>">My dashboard</a></li>
                    <?php else: ?>
                        <li><a href="<?= e(url('index.php')) ?>">Public website</a></li>
                    <?php endif; ?>
                    <?php if ($user['role'] === 'p'): ?>
                        <li><a href="<?= e(url('patient/appointment.php')) ?>">My bookings</a></li>
                    <?php endif; ?>
                    <li><a href="<?= e(url($info['settings'])) ?>">Settings</a></li>
                    <li>
                        <form method="post" action="<?= e(url('logout.php')) ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="account__logout"><?= icon('logout') ?>Log out</button>
                        </form>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function site_header(string $current): void
{
    $links = [
        'home' => ['Home', 'index.php'],
        'services' => ['Services', 'services.php'],
        'about' => ['About', 'about.php'],
        'contact' => ['Contact', 'contact.php'],
    ];
    ?>
    <header class="site-header">
        <div class="container site-header__inner">
            <a class="brand" href="<?= e(url('index.php')) ?>"><?= logo() ?><span class="visually-hidden">Uhai Bora home</span></a>
            <a class="button button--primary button--small site-header__book" href="<?= e(url('book.php')) ?>">Book Now</a>
            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" data-nav-toggle>
                <span class="nav-toggle__bars" aria-hidden="true"><span></span><span></span><span></span></span>
                <span class="nav-toggle__label">Menu</span>
            </button>
            <nav class="site-nav" id="site-nav" aria-label="Main" data-nav-panel>
                <ul class="site-nav__links">
                    <?php foreach ($links as $key => $link): ?>
                        <li><?= nav_link($link[0], $link[1], $current === $key, 'site-nav__link') ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="site-nav__actions">
                    <a class="button button--primary site-nav__book" href="<?= e(url('book.php')) ?>">Book Now<?= icon('arrow-right') ?></a>
                    <?php account_menu('account-menu'); ?>
                </div>
            </nav>
        </div>
    </header>
    <?php
}

function share_links(): string
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '' || preg_match('/^[A-Za-z0-9.\-]+(:\d+)?$/', $host) !== 1) {
        return '';
    }
    $pageUrl = (is_https() ? 'https://' : 'http://') . $host . url(request_path());
    $text = 'Uhai Bora — book appointments with listed doctors.';
    $targets = [
        'whatsapp' => ['WhatsApp', 'https://wa.me/?text=' . rawurlencode($text . ' ' . $pageUrl)],
        'facebook' => ['Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($pageUrl)],
        'x-brand' => ['X', 'https://twitter.com/intent/tweet?text=' . rawurlencode($text) . '&url=' . rawurlencode($pageUrl)],
        'linkedin' => ['LinkedIn', 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode($pageUrl)],
    ];
    $html = '<ul class="share-links">';
    foreach ($targets as $iconName => $target) {
        $html .= '<li><a href="' . e($target[1]) . '" target="_blank" rel="noopener noreferrer">' . icon($iconName)
            . '<span class="visually-hidden">Share on ' . e($target[0]) . ' (opens in a new tab)</span></a></li>';
    }
    return $html . '</ul>';
}

function site_footer(): void
{
    $user = current_user();
    $operator = config('operator.legal_name');
    ?>
    <footer class="site-footer">
        <div class="container site-footer__safety">
            <?= icon('heart-pulse') ?>
            <p><strong>Uhai Bora is not an emergency service.</strong> If you or someone with you needs urgent medical help, call your local emergency number or go to the nearest emergency department. <a href="<?= e(url('contact.php')) ?>#urgent-help">Urgent help</a></p>
        </div>
        <div class="container site-footer__grid">
            <div class="site-footer__brand">
                <a class="brand brand--light" href="<?= e(url('index.php')) ?>"><?= logo('light') ?><span class="visually-hidden">Uhai Bora home</span></a>
                <p>Book and manage appointments with listed doctors, in one calm place.</p>
                <p class="site-footer__operator">
                    <?php if ($operator !== null): ?>
                        Operated by <?= e($operator) ?>.
                    <?php else: ?>
                        The organisation operating this service will be named here before launch.
                    <?php endif; ?>
                </p>
            </div>
            <nav class="site-footer__nav" aria-labelledby="footer-explore">
                <h2 id="footer-explore">Explore</h2>
                <ul>
                    <li><a href="<?= e(url('index.php')) ?>">Home</a></li>
                    <li><a href="<?= e(url('services.php')) ?>">Services</a></li>
                    <li><a href="<?= e(url('how-it-works.php')) ?>">How it works</a></li>
                    <li><a href="<?= e(url('about.php')) ?>">About</a></li>
                    <li><a href="<?= e(url('contact.php')) ?>">Contact</a></li>
                </ul>
            </nav>
            <nav class="site-footer__nav" aria-labelledby="footer-account">
                <h2 id="footer-account">Your account</h2>
                <ul>
                    <?php if ($user): ?>
                        <li><a href="<?= e(url(role_info($user['role'])['home'])) ?>">My dashboard</a></li>
                        <li><a href="<?= e(url(role_info($user['role'])['settings'])) ?>">Settings</a></li>
                    <?php else: ?>
                        <li><a href="<?= e(url('signup.php')) ?>">Register</a></li>
                        <li><a href="<?= e(url('login.php')) ?>">Login</a></li>
                    <?php endif; ?>
                    <li><a href="<?= e(url('book.php')) ?>">Book an appointment</a></li>
                </ul>
            </nav>
            <nav class="site-footer__nav" aria-labelledby="footer-policies">
                <h2 id="footer-policies">Policies</h2>
                <ul>
                    <li><a href="<?= e(url('privacy.php')) ?>">Privacy notice</a></li>
                    <li><a href="<?= e(url('your-data.php')) ?>">Your data rights</a></li>
                    <li><a href="<?= e(url('terms.php')) ?>">Terms of use</a></li>
                    <li><a href="<?= e(url('cookies.php')) ?>">Cookie notice</a></li>
                    <li><a href="<?= e(url('accessibility.php')) ?>">Accessibility</a></li>
                </ul>
            </nav>
        </div>
        <div class="container site-footer__bottom">
            <p>© <?= e(date('Y')) ?> Uhai Bora</p>
            <div class="site-footer__share">
                <span>Share Uhai Bora</span>
                <?= share_links() ?>
            </div>
        </div>
    </footer>
    <?php
}

/* ---------- Legal and policy pages ---------- */

/** $o: title, eyebrow, intro, description, sections ([id => heading]), updated. */
function legal_start(array $o): void
{
    page_start(['title' => $o['title'], 'description' => $o['description'] ?? $o['intro']]);
    ?>
    <section class="page-hero" aria-labelledby="legal-title">
        <div class="container">
            <p class="eyebrow"><?= e($o['eyebrow']) ?></p>
            <h1 id="legal-title"><?= e($o['title']) ?></h1>
            <p class="lead"><?= e($o['intro']) ?></p>
            <p class="meta-line"><?= e($o['updated'] ?? ('Effective ' . format_date_long((string) config('policy_effective_date')) . ' · Version ' . config('policy_version'))) ?></p>
        </div>
    </section>
    <div class="container legal">
        <nav class="legal__toc" aria-labelledby="toc-title">
            <h2 id="toc-title">On this page</h2>
            <ol>
                <?php foreach ($o['sections'] as $id => $heading): ?>
                    <li><a href="#<?= e($id) ?>"><?= e($heading) ?></a></li>
                <?php endforeach; ?>
            </ol>
        </nav>
        <article class="prose">
            <?php if (!operator_configured()): ?>
                <div class="notice-draft" role="note">
                    <?= icon('alert') ?>
                    <p><strong>Draft — operator details pending.</strong> The legal name and contact details of the organisation operating this service have not been published yet. This page will be completed before the service is used with real patient information.</p>
                </div>
            <?php endif; ?>
    <?php
}

function legal_end(): void
{
    echo '</article></div>';
    page_end();
}

/** Contact sentence for privacy requests, using configured details only. */
function privacy_contact_html(): string
{
    $email = config('privacy.email') ?? config('contact.email');
    $phone = config('contact.phone');
    $parts = [];
    if ($email !== null) {
        $parts[] = 'email <a href="mailto:' . e($email) . '">' . e($email) . '</a>';
    }
    if ($phone !== null) {
        $parts[] = 'call ' . e($phone);
    }
    if (!$parts) {
        return 'use the contact details on our <a href="' . e(url('contact.php')) . '">Contact page</a> (the operator’s privacy contact will be published there before launch)';
    }
    return implode(' or ', $parts);
}

/* ---------- Signed-in application layout ---------- */

function app_nav_items(string $role): array
{
    if ($role === 'p') {
        return [
            'home' => ['Home', 'Home', 'patient/index.php', 'home'],
            'book' => ['Book appointment', 'Book', 'patient/schedule.php', 'calendar-plus'],
            'bookings' => ['My bookings', 'Bookings', 'patient/appointment.php', 'clipboard'],
            'doctors' => ['Doctors', 'Doctors', 'patient/doctors.php', 'stethoscope'],
            'settings' => ['Settings', 'Settings', 'patient/settings.php', 'settings'],
        ];
    }
    if ($role === 'd') {
        return [
            'dashboard' => ['Dashboard', 'Home', 'doctor/index.php', 'grid'],
            'appointments' => ['My appointments', 'Appointments', 'doctor/appointment.php', 'clipboard'],
            'sessions' => ['My sessions', 'Sessions', 'doctor/schedule.php', 'calendar'],
            'patients' => ['My patients', 'Patients', 'doctor/patient.php', 'users'],
            'settings' => ['Settings', 'Settings', 'doctor/settings.php', 'settings'],
        ];
    }
    return [
        'dashboard' => ['Dashboard', 'Home', 'admin/index.php', 'grid'],
        'doctors' => ['Doctors', 'Doctors', 'admin/doctors.php', 'stethoscope'],
        'sessions' => ['Sessions', 'Sessions', 'admin/schedule.php', 'calendar'],
        'appointments' => ['Appointments', 'Bookings', 'admin/appointment.php', 'clipboard'],
        'patients' => ['Patients', 'Patients', 'admin/patient.php', 'users'],
    ];
}

/**
 * Start a signed-in page. Keys: title, nav, heading, lead, eyebrow, actions (HTML), back ([label, path]).
 */
function app_start(array $page): void
{
    $user = current_user();
    $page += ['title' => '', 'nav' => '', 'heading' => null, 'lead' => '', 'eyebrow' => '', 'actions' => '', 'back' => null,
        'description' => 'Uhai Bora account area.', 'robots' => 'noindex, nofollow'];
    $role = $user['role'] ?? 'p';
    $info = role_info($role);
    page_head($page, ['app']);
    ?>
<body class="app-body">
    <a class="skip-link" href="#main">Skip to main content</a>
    <header class="app-topbar">
        <a class="brand brand--app" href="<?= e(url($info['home'])) ?>"><?= logo() ?><span class="visually-hidden">Uhai Bora — <?= e($info['label']) ?> home</span></a>
        <span class="role-chip"><?= e($info['label']) ?></span>
        <div class="app-topbar__actions">
            <?php account_menu('app-account-menu', 'app'); ?>
        </div>
    </header>
    <div class="app-layout">
        <nav class="app-nav" aria-label="<?= e($info['label']) ?> menu">
            <ul class="app-nav__list">
                <?php foreach (app_nav_items($role) as $key => $item): ?>
                    <li>
                        <a class="app-nav__link" href="<?= e(url($item[2])) ?>"<?= $page['nav'] === $key ? ' aria-current="page"' : '' ?>>
                            <?= icon($item[3]) ?><span class="app-nav__label"><?= e($item[0]) ?></span><span class="app-nav__short" aria-hidden="true"><?= e($item[1]) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="app-nav__note">
                <?= icon('heart-pulse') ?>
                <p><strong>Not for emergencies.</strong> For urgent help, call your local emergency number or go to the nearest emergency department.</p>
            </div>
        </nav>
        <main id="main" class="app-main" tabindex="-1">
            <div class="app-main__inner">
                <?php if ($page['back']): ?>
                    <a class="back-link" href="<?= e(url($page['back'][1])) ?>"><?= icon('arrow-left') ?><?= e($page['back'][0]) ?></a>
                <?php endif; ?>
                <?= render_flashes() ?>
                <div class="page-header">
                    <div class="page-header__text">
                        <?php if ($page['eyebrow'] !== ''): ?><p class="eyebrow"><?= e($page['eyebrow']) ?></p><?php endif; ?>
                        <h1><?= e($page['heading'] ?? $page['title']) ?></h1>
                        <?php if ($page['lead'] !== ''): ?><p class="page-header__lead"><?= e($page['lead']) ?></p><?php endif; ?>
                    </div>
                    <?php if ($page['actions'] !== ''): ?><div class="page-header__actions"><?= $page['actions'] ?></div><?php endif; ?>
                </div>
    <?php
}

function app_end(): void
{
    ?>
            </div>
            <footer class="app-footer">
                <p>© <?= e(date('Y')) ?> Uhai Bora</p>
                <ul>
                    <li><a href="<?= e(url('privacy.php')) ?>">Privacy</a></li>
                    <li><a href="<?= e(url('terms.php')) ?>">Terms</a></li>
                    <li><a href="<?= e(url('accessibility.php')) ?>">Accessibility</a></li>
                    <li><a href="<?= e(url('contact.php')) ?>">Help</a></li>
                </ul>
            </footer>
        </main>
    </div>
</body>
</html>
    <?php
}

/* ---------- Reusable fragments ---------- */

/**
 * Responsive photo: WebP at several widths with a JPEG fallback.
 * Files must exist as assets/photos/{name}-{width}.webp and {name}-{fallback}.jpg.
 */
function picture(string $name, string $alt, array $widths, int $fallbackWidth, int $width, int $height, string $sizes, bool $lazy = true): string
{
    $srcset = [];
    foreach ($widths as $w) {
        $srcset[] = asset('assets/photos/' . $name . '-' . $w . '.webp') . ' ' . $w . 'w';
    }
    return '<picture>'
        . '<source type="image/webp" srcset="' . e(implode(', ', $srcset)) . '" sizes="' . e($sizes) . '">'
        . '<img src="' . e(asset('assets/photos/' . $name . '-' . $fallbackWidth . '.jpg')) . '" alt="' . e($alt) . '"'
        . ' width="' . $width . '" height="' . $height . '"' . ($lazy ? ' loading="lazy"' : '') . ' decoding="async">'
        . '</picture>';
}

function empty_state(string $title, string $text, string $actionHtml = '', string $illustration = 'empty-appointments'): string
{
    return '<div class="empty-state">'
        . '<img src="' . e(asset('assets/illustrations/' . $illustration . '.svg')) . '" alt="" width="160" height="130" loading="lazy">'
        . '<h2>' . e($title) . '</h2><p>' . e($text) . '</p>'
        . ($actionHtml !== '' ? '<div class="empty-state__actions">' . $actionHtml . '</div>' : '')
        . '</div>';
}

function button_link(string $label, string $path, string $variant = 'primary', array $query = [], string $iconName = ''): string
{
    return '<a class="button button--' . e($variant) . '" href="' . e(url($path, $query)) . '">'
        . ($iconName !== '' ? icon($iconName) : '') . e($label) . '</a>';
}

/**
 * A confirmation step for destructive actions that works without JavaScript:
 * the first button reveals the explanation and the real (POST) action.
 */
function confirm_action(array $o): string
{
    $o += ['label' => 'Delete', 'question' => 'Are you sure?', 'detail' => '', 'confirm' => 'Yes, delete', 'action' => '',
        'fields' => [], 'variant' => 'danger', 'icon' => 'trash', 'extra' => '', 'open' => false];
    $html = '<details class="confirm" data-confirm' . ($o['open'] ? ' open' : '') . '><summary class="button button--' . e($o['variant'] === 'danger' ? 'danger-quiet' : 'secondary') . ' button--small">'
        . icon($o['icon']) . e($o['label']) . '</summary>'
        . '<div class="confirm__panel" role="group" aria-label="' . e($o['question']) . '">'
        . '<p class="confirm__question">' . e($o['question']) . '</p>'
        . ($o['detail'] !== '' ? '<p class="confirm__detail">' . e($o['detail']) . '</p>' : '')
        . '<form method="post" action="' . e($o['action'] !== '' ? url($o['action']) : '') . '">' . csrf_field();
    foreach ($o['fields'] as $name => $value) {
        $html .= '<input type="hidden" name="' . e($name) . '" value="' . e($value) . '">';
    }
    $html .= $o['extra']
        . '<button type="submit" class="button button--' . e($o['variant']) . ' button--small">' . e($o['confirm']) . '</button>'
        . '</form></div></details>';
    return $html;
}

function stat_card(string $label, $value, string $iconName, string $hint = ''): string
{
    return '<div class="stat-card">' . icon($iconName, 'stat-card__icon')
        . '<p class="stat-card__value">' . e($value) . '</p><p class="stat-card__label">' . e($label) . '</p>'
        . ($hint !== '' ? '<p class="stat-card__hint">' . e($hint) . '</p>' : '') . '</div>';
}

function pagination(int $page, bool $hasMore, array $query, string $path): string
{
    if ($page <= 1 && !$hasMore) {
        return '';
    }
    $html = '<nav class="pagination" aria-label="Pages">';
    if ($page > 1) {
        $html .= '<a class="button button--secondary button--small" href="' . e(url($path, array_merge($query, ['page' => $page - 1]))) . '">' . icon('arrow-left') . 'Previous</a>';
    }
    $html .= '<span class="pagination__current">Page ' . $page . '</span>';
    if ($hasMore) {
        $html .= '<a class="button button--secondary button--small" href="' . e(url($path, array_merge($query, ['page' => $page + 1]))) . '">Next' . icon('arrow-right') . '</a>';
    }
    return $html . '</nav>';
}
