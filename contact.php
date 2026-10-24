<?php
require __DIR__ . '/includes/bootstrap.php';

$phone = config('contact.phone');
$email = config('contact.email');
$hours = config('contact.hours');
$address = config('operator.address');
$privacyEmail = config('privacy.email') ?? $email;
$accessibilityEmail = config('accessibility.email') ?? $email;
$emergencyNumbers = (array) config('emergency_numbers', []);

page_start([
    'title' => 'Contact',
    'nav' => 'contact',
    'description' => 'Get help with your Uhai Bora account or bookings, contact the clinic, or find urgent help.',
]);
?>
<section class="page-hero" aria-labelledby="contact-title">
    <div class="container page-hero__grid">
        <div>
            <p class="eyebrow">Contact and help</p>
            <h1 id="contact-title">How can we <em>help?</em></h1>
            <p class="lead">Choose the option that matches what you need. If someone needs urgent medical help, don’t wait for a reply — see urgent help below.</p>
            <a class="button button--secondary" href="#urgent-help"><?= icon('heart-pulse') ?>Urgent help</a>
        </div>
        <div class="page-hero__art">
            <img src="<?= e(asset('assets/illustrations/support.svg')) ?>" alt="" width="360" height="240">
        </div>
    </div>
</section>

<section class="section section--sand" aria-labelledby="options-title">
    <div class="container">
        <h2 id="options-title" class="visually-hidden">Ways to get help</h2>
        <div class="grid grid--2">
            <div class="info-card" id="account-help">
                <?= icon('user') ?>
                <h3>Your account and bookings</h3>
                <p>Log in to see, reschedule or cancel bookings and to update your details in Settings.</p>
                <p><strong>Forgotten your password?</strong> For your security, passwords are reset by the clinic team after checking who you are. Contact the clinic using the details on this page.</p>
                <p><a class="button button--primary button--small" href="<?= e(url('login.php')) ?>">Log in</a></p>
            </div>

            <div class="info-card" id="clinic">
                <?= icon('phone') ?>
                <h3>Contact the clinic</h3>
                <?php if ($phone !== null || $email !== null || $address !== null): ?>
                    <ul class="contact-lines">
                        <?php if ($phone !== null): ?><li><?= icon('phone') ?><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= e($phone) ?></a></li><?php endif; ?>
                        <?php if ($email !== null): ?><li><?= icon('mail') ?><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></li><?php endif; ?>
                        <?php if ($address !== null): ?><li><?= icon('map-pin') ?><span><?= e($address) ?></span></li><?php endif; ?>
                    </ul>
                    <?php if ($hours !== null): ?><p class="muted">Monitored: <?= e($hours) ?>.</p><?php endif; ?>
                    <p class="muted">Please don’t send medical details by email.</p>
                <?php else: ?>
                    <p>Verified clinic phone, email and address details have not been published on this website yet. Until they are, please use the contact details your clinic or doctor has given you directly.</p>
                <?php endif; ?>
            </div>

            <div class="info-card" id="privacy-requests">
                <?= icon('shield') ?>
                <h3>Privacy and your data</h3>
                <p>You can download, correct or delete your information yourself in your account Settings. For anything else — such as objecting to how your information is used — contact us.</p>
                <?php if ($privacyEmail !== null): ?>
                    <p><a href="mailto:<?= e($privacyEmail) ?>"><?= e($privacyEmail) ?></a></p>
                <?php endif; ?>
                <p><a href="<?= e(url('your-data.php')) ?>">Your data rights</a></p>
            </div>

            <div class="info-card" id="accessibility-feedback">
                <?= icon('accessibility') ?>
                <h3>Accessibility feedback</h3>
                <p>Tell us if something on Uhai Bora is hard to use or if you need information in another format. Include the page and what you were trying to do.</p>
                <?php if ($accessibilityEmail !== null): ?>
                    <p><a href="mailto:<?= e($accessibilityEmail) ?>"><?= e($accessibilityEmail) ?></a></p>
                <?php endif; ?>
                <p><a href="<?= e(url('accessibility.php')) ?>">Accessibility statement</a></p>
            </div>
        </div>
    </div>
</section>

<section class="section" aria-labelledby="urgent-help">
    <div class="container">
        <div class="callout callout--urgent">
            <?= icon('heart-pulse') ?>
            <div>
                <h2 id="urgent-help" tabindex="-1">Urgent help</h2>
                <p><strong>Uhai Bora is not an emergency service and is not monitored for urgent requests.</strong></p>
                <p>If you or someone with you may be seriously ill or injured, call your local emergency number or go to the nearest emergency department straight away.</p>
                <?php if ($emergencyNumbers): ?>
                    <ul class="contact-lines">
                        <?php foreach ($emergencyNumbers as $entry): ?>
                            <?php if (!empty($entry['number'])): ?>
                                <li><?= icon('phone') ?><span><?= e($entry['label'] ?? 'Emergency') ?>: <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', (string) $entry['number'])) ?>"><?= e($entry['number']) ?></a></span></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php
page_end();
