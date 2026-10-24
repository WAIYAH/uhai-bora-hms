<?php
require __DIR__ . '/includes/bootstrap.php';

$lastAudit = config('accessibility.last_audit');
$feedbackEmail = config('accessibility.email') ?? config('contact.email');

legal_start([
    'title' => 'Accessibility statement',
    'eyebrow' => 'Accessibility',
    'intro' => 'We want everyone to be able to book and manage appointments with Uhai Bora, including people who use assistive technology.',
    'updated' => 'Last reviewed ' . format_date_long((string) config('policy_effective_date')),
    'sections' => [
        'aim' => 'Our aim',
        'features' => 'What we have done',
        'limits' => 'Known limitations',
        'feedback' => 'Feedback and help',
        'status' => 'Compliance status',
    ],
]);
?>
<h2 id="aim">1. Our aim</h2>
<p>We aim to meet the Web Content Accessibility Guidelines (WCAG) 2.2 at level AA. This reflects our commitment to equal access under the Constitution of Kenya and the Persons with Disabilities Act, 2025.</p>

<h2 id="features">2. What we have done</h2>
<ul>
    <li>Every page can be used with a keyboard alone, with a visible focus outline and a “Skip to main content” link.</li>
    <li>Pages use headings, landmarks and labelled form fields so screen readers can navigate them.</li>
    <li>Form errors are listed at the top of the form and next to each field, in words as well as colour.</li>
    <li>Text and controls meet WCAG contrast ratios, and body text is at least 16 pixels.</li>
    <li>Layouts adapt to small phones and to zoom up to 400% without scrolling sideways.</li>
    <li>Buttons and links are large enough to tap easily.</li>
    <li>Animations are minimal and are switched off if your device asks for reduced motion. There is no auto-playing video.</li>
    <li>The menus, forms and booking steps work even if JavaScript is unavailable.</li>
</ul>

<h2 id="limits">3. Known limitations</h2>
<ul>
    <li>Uhai Bora is currently available in English only.</li>
    <li><?= $lastAudit !== null ? 'The most recent independent accessibility audit took place on ' . e($lastAudit) . '.' : 'Uhai Bora has not yet had an independent accessibility audit, so some barriers may remain.' ?></li>
    <li>Some administration screens contain large lists; on small screens they are shown as stacked cards.</li>
</ul>

<h2 id="feedback">4. Feedback and help</h2>
<p>If you find something hard to use, or need information in a different format, please tell us. Include the page address, what you were trying to do and, if you’re comfortable, the device or assistive technology you use. Please don’t include health details.</p>
<?php if ($feedbackEmail !== null): ?>
    <p>Email <a href="mailto:<?= e($feedbackEmail) ?>"><?= e($feedbackEmail) ?></a>.</p>
<?php else: ?>
    <p>A monitored contact for accessibility feedback will be published on our <a href="<?= e(url('contact.php')) ?>#accessibility-feedback">Contact page</a> before launch.</p>
<?php endif; ?>

<h2 id="status">5. Compliance status</h2>
<p>This statement describes how the service was designed and built. It is not a certificate of conformance. We will update it after an independent audit and whenever we fix a reported barrier.</p>
<?php
legal_end();
