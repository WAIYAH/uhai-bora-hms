<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_role('p');
$patientId = (int) $user['id'];

if (is_post()) {
    $appointmentId = post_int('id');
    if (post_string('action') === 'cancel' && $appointmentId) {
        if (cancel_patient_appointment($patientId, $appointmentId)) {
            audit('appointment.cancelled_by_patient', 'appointment', $appointmentId);
            flash('success', 'Your booking ' . appointment_reference($appointmentId) . ' has been cancelled and the place released.');
        } else {
            flash('error', 'That booking could not be cancelled. It may have already started, been completed or been cancelled.');
        }
    }
    redirect('patient/appointment.php');
}

/* ---------- Single booking ---------- */
$viewId = query_int('id');
if ($viewId) {
    $booking = db_one(appointment_select_sql() . ' WHERE a.appoid = ? AND a.pid = ?', [$viewId, $patientId]);
    if (!$booking) {
        not_found();
    }
    $canChange = (int) $booking['is_upcoming'] === 1 && in_array($booking['status'], ['pending', 'confirmed'], true);
    app_start([
        'title' => 'Booking ' . appointment_reference($viewId),
        'nav' => 'bookings',
        'back' => ['Back to my bookings', 'patient/appointment.php'],
        'heading' => $booking['title'],
        'eyebrow' => 'Booking ' . appointment_reference($viewId),
    ]);
    ?>
    <div class="split">
        <section class="panel" aria-labelledby="booking-details">
            <h2 id="booking-details">Booking details</h2>
            <dl class="details-list">
                <div><dt>Status</dt><dd><?= status_badge($booking['status']) ?></dd></div>
                <div><dt>Date</dt><dd><?= e(format_date_long($booking['scheduledate'])) ?></dd></div>
                <div><dt>Time</dt><dd><?= e(format_time($booking['scheduletime'])) ?> (East Africa Time)</dd></div>
                <div><dt>Doctor</dt><dd><?= e($booking['docname']) ?></dd></div>
                <div><dt>Specialty</dt><dd><?= e($booking['sname'] ?? 'Not specified') ?></dd></div>
                <div><dt>Booking reference</dt><dd><?= e(appointment_reference($viewId)) ?></dd></div>
                <div><dt>Appointment number</dt><dd><?= (int) $booking['apponum'] ?></dd></div>
                <div><dt>Booked on</dt><dd><?= e(format_date($booking['appodate'])) ?></dd></div>
            </dl>
            <?php if ($canChange): ?>
                <div class="record__actions">
                    <a class="button button--secondary button--small" href="<?= e(url('patient/reschedule.php', ['id' => $viewId])) ?>"><?= icon('refresh') ?>Reschedule</a>
                    <?= confirm_action([
                        'label' => 'Cancel booking',
                        'icon' => 'x',
                        'question' => 'Cancel this booking?',
                        'detail' => 'Your place will be released so someone else can use it.',
                        'confirm' => 'Yes, cancel booking',
                        'action' => 'patient/appointment.php',
                        'fields' => ['action' => 'cancel', 'id' => $viewId],
                    ]) ?>
                </div>
            <?php endif; ?>
        </section>
        <aside class="panel" aria-labelledby="booking-help">
            <h2 id="booking-help">Good to know</h2>
            <ul class="next-steps">
                <?php if ($booking['status'] === 'pending'): ?>
                    <li><?= icon('clock') ?><span>This booking is waiting for the clinic to confirm it. Check back here for updates.</span></li>
                <?php elseif ($booking['status'] === 'confirmed'): ?>
                    <li><?= icon('check-circle') ?><span>The clinic has confirmed your place. Please arrive in good time.</span></li>
                <?php endif; ?>
                <li><?= icon('info') ?><span>Quote your booking reference if you contact the clinic about this appointment.</span></li>
                <li><?= icon('heart-pulse') ?><span>If your condition gets worse or you need urgent help, don’t wait for this appointment — call your local emergency number.</span></li>
            </ul>
        </aside>
    </div>
    <?php
    app_end();
    exit;
}

/* ---------- List of bookings ---------- */
$view = query_string('view') === 'past' ? 'past' : 'upcoming';
$date = query_date('date');
$bookings = patient_appointments($patientId, $view, 200);
if ($date !== '') {
    $bookings = array_values(array_filter($bookings, function ($row) use ($date) {
        return $row['scheduledate'] === $date;
    }));
}
$bookedId = query_int('booked');
$justBooked = null;
if ($bookedId) {
    $justBooked = db_one(appointment_select_sql() . ' WHERE a.appoid = ? AND a.pid = ?', [$bookedId, $patientId]);
}

app_start([
    'title' => 'My bookings',
    'nav' => 'bookings',
    'lead' => 'Follow the status of your appointments, and reschedule or cancel if your plans change.',
    'actions' => button_link('Book an appointment', 'patient/schedule.php', 'primary', [], 'calendar-plus'),
]);
?>
<?php if ($justBooked): ?>
    <div class="alert alert--success notice" role="status">
        <?= icon('check-circle') ?>
        <p><strong>Booking received — reference <?= e(appointment_reference((int) $justBooked['appoid'])) ?>.</strong>
            <?= e($justBooked['title']) ?> with <?= e($justBooked['docname']) ?> on <?= e(format_date_long($justBooked['scheduledate'])) ?> at <?= e(format_time($justBooked['scheduletime'])) ?>.
            Your appointment number is <?= (int) $justBooked['apponum'] ?>. The status is “Pending confirmation” until the clinic confirms.</p>
    </div>
<?php endif; ?>

<ul class="tabs">
    <li><a href="<?= e(url('patient/appointment.php')) ?>"<?= $view === 'upcoming' ? ' aria-current="page"' : '' ?>>Upcoming</a></li>
    <li><a href="<?= e(url('patient/appointment.php', ['view' => 'past'])) ?>"<?= $view === 'past' ? ' aria-current="page"' : '' ?>>Past and cancelled</a></li>
</ul>

<form class="filters" method="get" action="<?= e(url('patient/appointment.php')) ?>" aria-label="Filter bookings by date">
    <?php if ($view === 'past'): ?><input type="hidden" name="view" value="past"><?php endif; ?>
    <?= input_field(['name' => 'date', 'label' => 'Session date', 'type' => 'date', 'required' => false, 'value' => $date]) ?>
    <div class="filters__actions">
        <button class="button button--secondary" type="submit">Filter</button>
        <?php if ($date !== ''): ?><a class="button button--ghost" href="<?= e(url('patient/appointment.php', $view === 'past' ? ['view' => 'past'] : [])) ?>">Clear</a><?php endif; ?>
    </div>
</form>

<?php if ($bookings): ?>
    <h2 class="visually-hidden"><?= $view === 'upcoming' ? 'Upcoming bookings' : 'Past and cancelled bookings' ?></h2>
    <ul class="record-list">
        <?php foreach ($bookings as $booking): ?>
            <?= patient_booking_record($booking) ?>
        <?php endforeach; ?>
    </ul>
<?php elseif ($date !== ''): ?>
    <?= empty_state('No bookings on this date', 'Try another date or clear the filter.', button_link('Clear filter', 'patient/appointment.php', 'secondary', $view === 'past' ? ['view' => 'past'] : [])) ?>
<?php elseif ($view === 'upcoming'): ?>
    <?= empty_state('No upcoming bookings', 'When you book a session it will appear here with its status.', button_link('Book an appointment', 'patient/schedule.php')) ?>
<?php else: ?>
    <?= empty_state('No past or cancelled bookings', 'Appointments that have taken place or were cancelled will appear here.') ?>
<?php endif; ?>
<?php
app_end();
