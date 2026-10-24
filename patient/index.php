<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_role('p');
$patientId = (int) $user['id'];

$upcoming = patient_appointments($patientId, 'upcoming', 50);
$next = $upcoming[0] ?? null;
$sessionsThisWeek = (int) db_value(
    'SELECT COUNT(*) FROM schedule s WHERE ' . UPCOMING_SQL . ' AND s.scheduledate <= CURDATE() + INTERVAL 7 DAY'
);
$doctorCount = (int) db_value('SELECT COUNT(*) FROM doctor');
$pastCount = count(patient_appointments($patientId, 'past', 1000));

app_start([
    'title' => 'Home',
    'nav' => 'home',
    'heading' => 'Welcome, ' . first_name($user['name']),
    'lead' => 'Your appointments and the quickest ways to book.',
]);
?>
<?php if ($next): ?>
    <section class="highlight" aria-labelledby="next-title">
        <div>
            <p class="eyebrow">Your next appointment</p>
            <h2 id="next-title"><?= e($next['title']) ?></h2>
            <div class="highlight__meta">
                <p><?= session_when($next['scheduledate'], $next['scheduletime']) ?></p>
                <p><?= e($next['docname']) ?><?= $next['sname'] ? ' · ' . e($next['sname']) : '' ?></p>
                <p>Ref <?= e(appointment_reference((int) $next['appoid'])) ?></p>
            </div>
            <p><?= status_badge($next['status']) ?></p>
        </div>
        <div class="button-row">
            <a class="button button--light" href="<?= e(url('patient/appointment.php', ['id' => (int) $next['appoid']])) ?>">View booking</a>
            <a class="button button--secondary" href="<?= e(url('patient/reschedule.php', ['id' => (int) $next['appoid']])) ?>">Reschedule</a>
        </div>
    </section>
<?php else: ?>
    <section class="highlight" aria-labelledby="next-title">
        <div>
            <p class="eyebrow">No upcoming appointments</p>
            <h2 id="next-title">Ready to book your next visit?</h2>
            <p><?= e(plural($sessionsThisWeek, 'session')) ?> with places in the next 7 days.</p>
        </div>
        <a class="button button--light" href="<?= e(url('patient/schedule.php')) ?>"><?= icon('calendar-plus') ?>Book an appointment</a>
    </section>
<?php endif; ?>

<ul class="quick-actions">
    <li><a class="quick-action" href="<?= e(url('patient/schedule.php')) ?>"><?= icon('calendar-plus') ?><span>Book an appointment</span><?= icon('chevron-right') ?></a></li>
    <li><a class="quick-action" href="<?= e(url('patient/appointment.php')) ?>"><?= icon('clipboard') ?><span>My bookings</span><?= icon('chevron-right') ?></a></li>
    <li><a class="quick-action" href="<?= e(url('patient/doctors.php')) ?>"><?= icon('stethoscope') ?><span>Find a doctor</span><?= icon('chevron-right') ?></a></li>
</ul>

<div class="section-title">
    <h2>Upcoming bookings</h2>
    <a href="<?= e(url('patient/appointment.php')) ?>">See all bookings</a>
</div>
<?php if ($upcoming): ?>
    <ul class="record-list">
        <?php foreach (array_slice($upcoming, 0, 5) as $appointment): ?>
            <?= patient_booking_record($appointment) ?>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <?= empty_state('You have no upcoming bookings', 'When you book a session it will appear here with its status.', button_link('Find a session', 'patient/schedule.php', 'primary')) ?>
<?php endif; ?>

<div class="section-title">
    <h2>At a glance</h2>
</div>
<div class="stats">
    <?= stat_card('Upcoming bookings', count($upcoming), 'clipboard') ?>
    <?= stat_card('Sessions in the next 7 days', $sessionsThisWeek, 'calendar') ?>
    <?= stat_card('Doctors listed', $doctorCount, 'stethoscope') ?>
    <?= stat_card('Past and cancelled', $pastCount, 'check-circle') ?>
</div>
<?php
app_end();
