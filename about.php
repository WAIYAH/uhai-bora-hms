<?php
require __DIR__ . '/includes/bootstrap.php';

$operator = config('operator.legal_name');
$registration = config('operator.facility_registration');

page_start([
    'title' => 'About',
    'nav' => 'about',
    'description' => 'What Uhai Bora is, who it is for, and the commitments behind it.',
]);
?>
<section class="page-hero" aria-labelledby="about-title">
    <div class="container page-hero__grid">
        <div>
            <p class="eyebrow">About Uhai Bora</p>
            <h1 id="about-title">Less waiting on the phone. <em>More clarity about your care.</em></h1>
            <p class="lead">Uhai Bora brings doctor listings, available sessions and your bookings into one straightforward place, so the next step feels a little easier.</p>
            <p class="muted">The name joins two Kiswahili words: <span lang="sw">uhai</span> (life) and <span lang="sw">bora</span> (best).</p>
        </div>
        <figure class="figure page-hero__art">
            <?= picture('blood-pressure-check', 'A health worker checking a patient’s blood pressure at a desk.', [600, 900, 1200], 900, 900, 1350, '(min-width: 56em) 26rem, 100vw', false) ?>
            <figcaption>Illustrative photo — not Uhai Bora staff or patients.</figcaption>
        </figure>
    </div>
</section>

<section class="section section--white" aria-labelledby="who-title">
    <div class="container grid grid--2">
        <div>
            <p class="eyebrow">Who runs Uhai Bora</p>
            <h2 id="who-title">The organisation behind the service</h2>
            <?php if ($operator !== null): ?>
                <p>Uhai Bora is operated by <strong><?= e($operator) ?></strong><?= $registration !== null ? ' (registration ' . e($registration) . ')' : '' ?>. It is responsible for the doctors and sessions listed and for looking after your information.</p>
            <?php else: ?>
                <p>The organisation that operates this service — and that is responsible for the listed doctors and for your information — will be named here before the service is used with real patient information.</p>
            <?php endif; ?>
            <p>See the <a href="<?= e(url('privacy.php')) ?>">Privacy notice</a> for how your information is handled and the <a href="<?= e(url('contact.php')) ?>">Contact page</a> for how to reach us.</p>
        </div>
        <div>
            <p class="eyebrow">What it does</p>
            <h2>One place for appointment details</h2>
            <p>Uhai Bora handles appointment administration: listing doctors and sessions, taking bookings and keeping their status up to date. It does not provide medical advice, diagnosis or emergency care.</p>
        </div>
    </div>
</section>

<section class="section" aria-labelledby="audiences-title">
    <div class="container">
        <div class="section-heading">
            <p class="eyebrow">Built for everyone involved</p>
            <h2 id="audiences-title">How each person uses it</h2>
        </div>
        <div class="grid grid--3">
            <div class="info-card">
                <?= icon('user') ?>
                <h3>Patients</h3>
                <p>Find a doctor or specialty, book a session with places available, and follow, reschedule or cancel bookings.</p>
            </div>
            <div class="info-card">
                <?= icon('stethoscope') ?>
                <h3>Doctors</h3>
                <p>Publish their own sessions, see who is booked, and confirm or update appointments.</p>
            </div>
            <div class="info-card">
                <?= icon('grid') ?>
                <h3>Clinic administrators</h3>
                <p>Manage doctor accounts, sessions and bookings, and help patients with their records.</p>
            </div>
        </div>
    </div>
</section>

<section class="section section--sand" aria-labelledby="commitments-title">
    <div class="container">
        <div class="section-heading">
            <p class="eyebrow">Our commitments</p>
            <h2 id="commitments-title">What you can <em>expect from us.</em></h2>
        </div>
        <div class="grid grid--2">
            <div class="info-card">
                <?= icon('info') ?>
                <h3>Clear, honest information</h3>
                <p>We show what is actually listed — doctors, session times and places left — and say plainly what Uhai Bora can’t do.</p>
            </div>
            <div class="info-card">
                <?= icon('lock') ?>
                <h3>Care with your information</h3>
                <p>Booking details are treated as sensitive. Access is limited by role and logged, and you can download, correct or delete your information.</p>
            </div>
            <div class="info-card">
                <?= icon('accessibility') ?>
                <h3>Accessible by design</h3>
                <p>We aim to meet WCAG 2.2 AA, so the service works with keyboards, screen readers, zoom and small screens. <a href="<?= e(url('accessibility.php')) ?>">Accessibility statement</a></p>
            </div>
            <div class="info-card">
                <?= icon('heart-pulse') ?>
                <h3>Safety first</h3>
                <p>Uhai Bora is not monitored for urgent requests. In an emergency, call your local emergency number or go to the nearest emergency department.</p>
            </div>
        </div>
    </div>
</section>

<section class="section section--tight" aria-labelledby="about-cta">
    <div class="container">
        <div class="cta-band">
            <div>
                <h2 id="about-cta">See what’s available.</h2>
                <p>Browse specialties with listed doctors, then book when you’re ready.</p>
            </div>
            <div class="button-row">
                <a class="button button--light" href="<?= e(url('services.php')) ?>">Browse specialties</a>
                <a class="button button--secondary" href="<?= e(url('how-it-works.php')) ?>">How it works</a>
            </div>
        </div>
    </div>
</section>
<?php
page_end();
