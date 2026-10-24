<?php
require __DIR__ . '/includes/bootstrap.php';

page_start([
    'title' => 'How it works',
    'description' => 'A step-by-step guide to booking, rescheduling and cancelling appointments with Uhai Bora.',
]);
?>
<section class="page-hero" aria-labelledby="how-title">
    <div class="container page-hero__grid">
        <div>
            <p class="eyebrow">Booking guide</p>
            <h1 id="how-title">How booking <em>works.</em></h1>
            <p class="lead">From creating your account to the day of your appointment — and what to do if your plans change.</p>
            <a class="button button--primary" href="<?= e(url('book.php')) ?>">Book an appointment<?= icon('arrow-right') ?></a>
        </div>
        <div class="page-hero__art">
            <img src="<?= e(asset('assets/illustrations/booking.svg')) ?>" alt="" width="360" height="240">
        </div>
    </div>
</section>

<section class="section section--white" aria-labelledby="steps-title">
    <div class="container grid grid--2">
        <div>
            <h2 id="steps-title">Step by step</h2>
            <p class="muted">To register you’ll need an email address, a phone number and an ID or passport number.</p>
        </div>
        <ol class="timeline">
            <li>
                <h3>Register or log in</h3>
                <p>Create a patient account with your contact details. A parent or guardian registers for anyone under 18.</p>
            </li>
            <li>
                <h3>Find a session</h3>
                <p>Filter by specialty, doctor or date. Each session shows the time and how many places are left.</p>
            </li>
            <li>
                <h3>Check and confirm</h3>
                <p>Review the doctor, date and time (East Africa Time), then confirm. You get a booking reference straight away.</p>
            </li>
            <li>
                <h3>Wait for the clinic to confirm</h3>
                <p>New bookings start as “Pending confirmation”. The status changes in My bookings when the clinic confirms.</p>
            </li>
            <li>
                <h3>Attend, reschedule or cancel</h3>
                <p>Arrive in good time. If your plans change, reschedule or cancel from My bookings before the session starts.</p>
            </li>
        </ol>
    </div>
</section>

<section class="section" aria-labelledby="status-title">
    <div class="container">
        <div class="section-heading">
            <h2 id="status-title">What each booking status means</h2>
        </div>
        <ul class="status-list card">
            <li><span><?= status_badge('pending') ?></span><p>Your booking is recorded and waiting for the clinic to confirm it.</p></li>
            <li><span><?= status_badge('confirmed') ?></span><p>The clinic has confirmed your place in the session.</p></li>
            <li><span><?= status_badge('completed') ?></span><p>The appointment has taken place.</p></li>
            <li><span><?= status_badge('cancelled') ?></span><p>The booking was cancelled by you or by the clinic. You can book another session at any time.</p></li>
        </ul>
    </div>
</section>

<section class="section section--sand" aria-labelledby="how-faq-title">
    <div class="container faq">
        <div>
            <h2 id="how-faq-title">Common questions</h2>
        </div>
        <div class="faq__list">
            <details class="faq__item">
                <summary>What is the appointment number?</summary>
                <div class="faq__answer"><p>Each booking in a session gets a number that helps the clinic organise the session. Your booking reference (it starts with “UB-”) identifies the booking itself.</p></div>
            </details>
            <details class="faq__item">
                <summary>Can I book for someone else?</summary>
                <div class="faq__answer"><p>Each account is for one patient. A parent or legal guardian registers an account for a child under 18.</p></div>
            </details>
            <details class="faq__item">
                <summary>What if a session is full?</summary>
                <div class="faq__answer"><p>Full sessions are marked “Full” and can’t be booked. Choose another session for the same doctor or specialty.</p></div>
            </details>
            <details class="faq__item">
                <summary>Will I be reminded about my appointment?</summary>
                <div class="faq__answer"><p>Uhai Bora doesn’t send reminders at the moment. Please note your appointment, and check My bookings for any status changes.</p></div>
            </details>
            <details class="faq__item">
                <summary>Is my information private?</summary>
                <div class="faq__answer"><p>Yes. Booking details are treated as sensitive. Read the <a href="<?= e(url('privacy.php')) ?>">Privacy notice</a> and <a href="<?= e(url('your-data.php')) ?>">Your data rights</a>.</p></div>
            </details>
        </div>
    </div>
</section>

<section class="section section--tight" aria-labelledby="how-cta">
    <div class="container">
        <div class="callout callout--urgent">
            <?= icon('heart-pulse') ?>
            <p id="how-cta"><strong>Not for emergencies.</strong> If you may be having a medical emergency, call your local emergency number or go to the nearest emergency department.</p>
        </div>
    </div>
</section>
<?php
page_end();
