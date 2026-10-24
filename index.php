<?php
require __DIR__ . '/includes/bootstrap.php';

list($specialtyList, $specialtiesUnavailable) = db_optional(function () {
    return array_slice(listed_specialties(), 0, 6);
}, []);

page_start([
    'nav' => 'home',
    'description' => 'Browse listed doctors and specialties, choose an available session, and manage your appointments with Uhai Bora.',
]);
?>
<section class="hero" aria-labelledby="hero-title">
    <div class="container hero__grid">
        <div>
            <p class="eyebrow">Appointments with listed doctors</p>
            <h1 id="hero-title">Your next doctor’s appointment, <em>a few calm steps away.</em></h1>
            <p class="lead">Browse doctors and specialties, choose a session that suits you, and keep track of your bookings from any phone or computer.</p>
            <div class="button-row">
                <a class="button button--primary" href="<?= e(url('book.php')) ?>">Book an appointment<?= icon('arrow-right') ?></a>
                <a class="button button--secondary" href="<?= e(url('how-it-works.php')) ?>">How booking works</a>
            </div>
            <ul class="hero__points">
                <li><?= icon('check') ?><span>See session times and how many places are left.</span></li>
                <li><?= icon('check') ?><span>Reschedule or cancel from your account.</span></li>
                <li><?= icon('check') ?><span>Not for emergencies — <a href="<?= e(url('contact.php')) ?>#urgent-help">get urgent help</a>.</span></li>
            </ul>
        </div>
        <div class="hero__art">
            <img src="<?= e(asset('assets/illustrations/hero-care.svg')) ?>" alt="" width="760" height="620" fetchpriority="high">
            <div class="hero__card">
                <?= icon('calendar') ?>
                <p><strong>Your bookings, in one place.</strong><br>Upcoming visits, status and changes.</p>
            </div>
        </div>
    </div>
</section>

<section class="section section--tight" aria-labelledby="quick-title">
    <div class="container">
        <h2 id="quick-title">What would you like to do?</h2>
        <ul class="chips">
            <li><a class="chip" href="<?= e(url('book.php')) ?>"><?= icon('calendar-plus') ?>Book an appointment</a></li>
            <li><a class="chip" href="<?= e(url('patient/appointment.php')) ?>"><?= icon('clipboard') ?>Manage my bookings</a></li>
            <li><a class="chip" href="<?= e(url('services.php')) ?>"><?= icon('search') ?>Browse specialties</a></li>
            <li><a class="chip" href="<?= e(url('contact.php')) ?>#account-help"><?= icon('user') ?>Help with my account</a></li>
            <li><a class="chip chip--urgent" href="<?= e(url('contact.php')) ?>#urgent-help"><?= icon('heart-pulse') ?>Urgent help</a></li>
        </ul>
    </div>
</section>

<section class="section" aria-labelledby="steps-title">
    <div class="container">
        <div class="section-heading">
            <p class="eyebrow">How it works</p>
            <h2 id="steps-title">Three steps from search to <em>booked.</em></h2>
            <p class="lead">Create a patient account once, then book whenever you need to.</p>
        </div>
        <ol class="steps">
            <li class="step">
                <img src="<?= e(asset('assets/illustrations/discover.svg')) ?>" alt="" width="360" height="240" loading="lazy">
                <span class="step__number" aria-hidden="true">1</span>
                <h3>Find a doctor</h3>
                <p>Browse listed doctors by name or specialty.</p>
            </li>
            <li class="step">
                <img src="<?= e(asset('assets/illustrations/booking.svg')) ?>" alt="" width="360" height="240" loading="lazy">
                <span class="step__number" aria-hidden="true">2</span>
                <h3>Choose a session</h3>
                <p>Pick a date and time with places available, check the details and confirm.</p>
            </li>
            <li class="step">
                <img src="<?= e(asset('assets/illustrations/support.svg')) ?>" alt="" width="360" height="240" loading="lazy">
                <span class="step__number" aria-hidden="true">3</span>
                <h3>Manage your visit</h3>
                <p>Follow the booking status and reschedule or cancel if your plans change.</p>
            </li>
        </ol>
        <p class="section-footer"><a href="<?= e(url('how-it-works.php')) ?>">Read the full booking guide</a></p>
    </div>
</section>

<section class="section section--sand" aria-labelledby="specialties-title">
    <div class="container">
        <div class="section-heading">
            <p class="eyebrow">Specialties</p>
            <h2 id="specialties-title">Specialties with <em>listed doctors.</em></h2>
            <p>Only specialties with at least one doctor listed in Uhai Bora appear here.</p>
        </div>
        <?php if ($specialtiesUnavailable): ?>
            <div class="alert alert--warning" role="status"><?= icon('alert') ?><p>The specialty list is temporarily unavailable. Please try again shortly, or <a href="<?= e(url('book.php')) ?>">go straight to booking</a>.</p></div>
        <?php elseif (!$specialtyList): ?>
            <?= empty_state('No specialties listed yet', 'Specialties will appear here as soon as doctors are added to Uhai Bora.', '', 'discover') ?>
        <?php else: ?>
            <ul class="specialty-grid">
                <?php foreach ($specialtyList as $specialty): ?>
                    <li class="specialty-card">
                        <h3><?= e($specialty['sname']) ?></h3>
                        <?php $description = specialty_description($specialty['sname']); ?>
                        <?php if ($description !== ''): ?><p><?= e($description) ?></p><?php endif; ?>
                        <ul class="specialty-card__meta">
                            <li><?= e(plural((int) $specialty['doctors'], 'doctor')) ?></li>
                            <li><?= $specialty['next_session'] ? 'Next session ' . e(format_date($specialty['next_session'], 'j M')) : 'No upcoming sessions' ?></li>
                        </ul>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="section-footer"><a class="button button--secondary" href="<?= e(url('services.php')) ?>">See all specialties<?= icon('arrow-right') ?></a></p>
        <?php endif; ?>
    </div>
</section>

<section class="section" aria-labelledby="scope-title">
    <div class="container">
        <div class="section-heading">
            <p class="eyebrow">Know what to expect</p>
            <h2 id="scope-title">What Uhai Bora is — <em>and isn’t.</em></h2>
        </div>
        <div class="grid grid--2">
            <div class="compare-card compare-card--yes">
                <h3><?= icon('check-circle') ?>Uhai Bora is</h3>
                <ul>
                    <li><?= icon('check') ?><span>A way to book and manage appointments with doctors listed by the clinic.</span></li>
                    <li><?= icon('check') ?><span>A clear record of your upcoming and past bookings and their status.</span></li>
                    <li><?= icon('check') ?><span>Designed to work on phones, tablets and computers, including with assistive technology.</span></li>
                </ul>
            </div>
            <div class="compare-card compare-card--no">
                <h3><?= icon('x') ?>Uhai Bora is not</h3>
                <ul>
                    <li><?= icon('x') ?><span>An emergency service. Bookings are not monitored for urgent requests.</span></li>
                    <li><?= icon('x') ?><span>A source of medical advice, diagnosis or treatment.</span></li>
                    <li><?= icon('x') ?><span>A replacement for speaking to a qualified health professional.</span></li>
                </ul>
            </div>
        </div>
        <div class="callout callout--urgent section-footer" id="urgent">
            <?= icon('heart-pulse') ?>
            <p><strong>Need urgent help?</strong> Call your local emergency number or go to the nearest emergency department. Don’t wait for an online booking.</p>
        </div>
    </div>
</section>

<section class="section section--forest" aria-labelledby="privacy-title">
    <div class="container">
        <div class="section-heading">
            <p class="eyebrow">Your information</p>
            <h2 id="privacy-title">Treated as sensitive, <em>because it is.</em></h2>
            <p>Booking details can reveal information about your health, so they are handled with extra care.</p>
        </div>
        <ul class="trust-list">
            <li>
                <?= icon('lock') ?>
                <h3>Limited access</h3>
                <p>Doctors see the bookings for their own sessions. Clinic administrators manage the service. Access to patient records is logged.</p>
            </li>
            <li>
                <?= icon('download') ?>
                <h3>In your control</h3>
                <p>Download a copy of your information, correct your details or delete your account from your account settings.</p>
            </li>
            <li>
                <?= icon('shield') ?>
                <h3>No advertising trackers</h3>
                <p>The website uses a single essential cookie to keep you signed in. Read the <a href="<?= e(url('privacy.php')) ?>">Privacy notice</a>.</p>
            </li>
        </ul>
    </div>
</section>

<section class="section" aria-labelledby="faq-title">
    <div class="container faq">
        <div>
            <p class="eyebrow">Questions</p>
            <h2 id="faq-title">Good to <em>know.</em></h2>
            <p class="muted">Can’t find an answer? Visit the <a href="<?= e(url('contact.php')) ?>">Contact page</a>.</p>
        </div>
        <div class="faq__list">
            <details class="faq__item">
                <summary>How do I book an appointment?</summary>
                <div class="faq__answer"><p>Create a patient account or log in, choose a session from the list of available sessions, check the details and confirm. Your booking then appears in My bookings.</p></div>
            </details>
            <details class="faq__item">
                <summary>Can I choose my doctor and time?</summary>
                <div class="faq__answer"><p>Yes. You can filter sessions by doctor, specialty and date, and you’ll see how many places are left before you book.</p></div>
            </details>
            <details class="faq__item">
                <summary>What does “Pending confirmation” mean?</summary>
                <div class="faq__answer"><p>Your booking has been recorded and is waiting for the clinic to confirm it. The status changes in My bookings when the clinic updates it.</p></div>
            </details>
            <details class="faq__item">
                <summary>How do I reschedule or cancel?</summary>
                <div class="faq__answer"><p>Open My bookings, choose the booking and select Reschedule or Cancel booking. You can change bookings for sessions that haven’t started yet.</p></div>
            </details>
            <details class="faq__item">
                <summary>Who can see my information?</summary>
                <div class="faq__answer"><p>Doctors can see bookings for their own sessions and clinic administrators manage the service. The <a href="<?= e(url('privacy.php')) ?>">Privacy notice</a> explains this in full, including your rights.</p></div>
            </details>
            <details class="faq__item">
                <summary>Can I use Uhai Bora in an emergency?</summary>
                <div class="faq__answer"><p>No. Uhai Bora is for booking appointments only. If you may be having an emergency, call your local emergency number or go to the nearest emergency department.</p></div>
            </details>
        </div>
    </div>
</section>

<section class="section section--tight" aria-labelledby="cta-title">
    <div class="container">
        <div class="cta-band">
            <div>
                <h2 id="cta-title">Ready when you are.</h2>
                <p>Register for a patient account, or log in to see your bookings.</p>
            </div>
            <div class="button-row">
                <?php if (current_user()): ?>
                    <a class="button button--light" href="<?= e(url(role_info(current_user()['role'])['home'])) ?>">Go to my dashboard</a>
                <?php else: ?>
                    <a class="button button--light" href="<?= e(url('signup.php')) ?>">Register</a>
                    <a class="button button--secondary" href="<?= e(url('login.php')) ?>">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php
page_end();
