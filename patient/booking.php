<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_role('p');
$patientId = (int) $user['id'];

$scheduleId = is_post() ? post_int('scheduleid') : query_int('id');
if (!$scheduleId) {
    not_found();
}

if (is_post()) {
    $result = book_session($patientId, $scheduleId);
    if ($result['ok']) {
        audit('appointment.booked', 'appointment', (int) $result['appointment_id']);
        redirect('patient/appointment.php', ['booked' => (int) $result['appointment_id']]);
    }
    if ($result['error'] === 'duplicate' && !empty($result['appointment_id'])) {
        flash('info', booking_error_message('duplicate'));
        redirect('patient/appointment.php', ['id' => (int) $result['appointment_id']]);
    }
    flash('error', booking_error_message($result['error']));
    redirect('patient/booking.php', ['id' => $scheduleId]);
}

$session = find_session($scheduleId);
if (!$session) {
    not_found();
}
$existing = db_value(
    'SELECT appoid FROM appointment WHERE scheduleid = ? AND pid = ? AND status IN ' . ACTIVE_STATUSES_SQL . ' LIMIT 1',
    [$scheduleId, $patientId]
);
$isFuture = is_future_slot($session['scheduledate'], $session['scheduletime']);
$left = spaces_left($session);
$canBook = $isFuture && $left > 0 && $existing === null;

app_start([
    'title' => 'Check and confirm',
    'nav' => 'book',
    'back' => ['Back to sessions', 'patient/schedule.php'],
    'lead' => 'Check the details below, then confirm your booking.',
]);
?>
<div class="split">
    <section class="panel summary-card" aria-labelledby="session-title">
        <p class="eyebrow">Session</p>
        <h2 class="summary-card__title" id="session-title"><?= e($session['title']) ?></h2>
        <dl class="details-list">
            <div><dt>Doctor</dt><dd><?= e($session['docname']) ?></dd></div>
            <div><dt>Specialty</dt><dd><?= e($session['sname'] ?? 'Not specified') ?></dd></div>
            <div><dt>Date</dt><dd><?= e(format_date_long($session['scheduledate'])) ?></dd></div>
            <div><dt>Time</dt><dd><?= e(format_time($session['scheduletime'])) ?> (East Africa Time)</dd></div>
            <div><dt>Places left</dt><dd><?= e(plural($left, 'place')) ?> of <?= (int) $session['nop'] ?></dd></div>
        </dl>

        <?php if ($existing !== null): ?>
            <div class="alert alert--info"><?= icon('info') ?><p>You already have a booking for this session. <a href="<?= e(url('patient/appointment.php', ['id' => (int) $existing])) ?>">View your booking</a></p></div>
        <?php elseif (!$isFuture): ?>
            <div class="alert alert--warning"><?= icon('alert') ?><p>This session has already started or finished, so it can’t be booked. <a href="<?= e(url('patient/schedule.php')) ?>">Choose another session</a></p></div>
        <?php elseif ($left === 0): ?>
            <div class="alert alert--warning"><?= icon('alert') ?><p>This session is full. <a href="<?= e(url('patient/schedule.php', ['doctor' => (int) $session['docid']])) ?>">See other sessions with <?= e($session['docname']) ?></a></p></div>
        <?php endif; ?>

        <?php if ($canBook): ?>
            <form method="post" action="<?= e(url('patient/booking.php')) ?>" data-submit-once>
                <?= csrf_field() ?>
                <input type="hidden" name="scheduleid" value="<?= (int) $scheduleId ?>">
                <div class="button-row">
                    <button class="button button--primary" type="submit" data-loading-label="Confirming…"><?= icon('check') ?>Confirm booking</button>
                    <a class="button button--ghost" href="<?= e(url('patient/schedule.php')) ?>">Choose a different session</a>
                </div>
            </form>
        <?php endif; ?>
    </section>

    <aside class="panel" aria-labelledby="next-steps-title">
        <h2 id="next-steps-title">What happens next</h2>
        <ul class="next-steps">
            <li><?= icon('check-circle') ?><span>You get a booking reference straight away. The status starts as “Pending confirmation”.</span></li>
            <li><?= icon('clock') ?><span>The status changes in My bookings when the clinic confirms.</span></li>
            <li><?= icon('refresh') ?><span>You can reschedule or cancel until the session starts.</span></li>
            <li><?= icon('info') ?><span>Uhai Bora does not take payments. Ask the clinic about any fees.</span></li>
        </ul>
        <div class="callout callout--urgent">
            <?= icon('heart-pulse') ?>
            <p><strong>Not for emergencies.</strong> If you need urgent help, call your local emergency number or go to the nearest emergency department.</p>
        </div>
    </aside>
</div>
<?php
app_end();
