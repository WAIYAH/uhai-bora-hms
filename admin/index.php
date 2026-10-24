<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_role('a');

if (is_post()) {
    handle_appointment_action(null);
    redirect('admin/index.php');
}

$doctorCount = (int) db_value('SELECT COUNT(*) FROM doctor');
$patientCount = (int) db_value('SELECT COUNT(*) FROM patient');
$upcomingCount = (int) db_value(
    'SELECT COUNT(*) FROM appointment a INNER JOIN schedule s ON s.scheduleid = a.scheduleid WHERE a.status IN ' . ACTIVE_STATUSES_SQL . ' AND ' . UPCOMING_SQL
);
$pendingCount = (int) db_value(
    "SELECT COUNT(*) FROM appointment a INNER JOIN schedule s ON s.scheduleid = a.scheduleid WHERE a.status = 'pending' AND " . UPCOMING_SQL
);
$todaySessions = (int) db_value('SELECT COUNT(*) FROM schedule WHERE scheduledate = CURDATE()');
$weekEnd = date('Y-m-d', strtotime('+7 days'));
$weekAppointments = array_values(array_filter(staff_appointments(['period' => 'upcoming'], 100), function ($row) use ($weekEnd) {
    return $row['scheduledate'] <= $weekEnd;
}));
$weekSessions = array_values(array_filter(staff_sessions(['period' => 'upcoming'], 100), function ($row) use ($weekEnd) {
    return $row['scheduledate'] <= $weekEnd;
}));
$demoAccounts = (int) db_value(
    "SELECT COUNT(*) FROM webuser WHERE email IN ('admin@uhaibora.test', 'doctor@uhaibora.test', 'patient@uhaibora.test',
                                                 'admin@uhaibora.com', 'doctor@uhaibora.com', 'patient@uhaibora.com')"
);

app_start([
    'title' => 'Dashboard',
    'nav' => 'dashboard',
    'heading' => 'Clinic dashboard',
    'lead' => 'Today: ' . format_date_long(today()) . ' · ' . plural($todaySessions, 'session') . ' scheduled.',
    'actions' => button_link('Add a session', 'admin/schedule.php', 'primary', ['action' => 'add'], 'plus')
        . button_link('Add a doctor', 'admin/doctors.php', 'secondary', ['action' => 'add'], 'plus'),
]);
?>
<?php if ($demoAccounts > 0): ?>
    <div class="alert alert--warning notice" role="alert"><?= icon('alert') ?><p><strong>Demo accounts are present.</strong> Their passwords are published in the README. Delete them or change their passwords before this system holds real patient information.</p></div>
<?php endif; ?>

<div class="stats">
    <?= stat_card('Doctors', $doctorCount, 'stethoscope') ?>
    <?= stat_card('Patients', $patientCount, 'users') ?>
    <?= stat_card('Upcoming bookings', $upcomingCount, 'clipboard') ?>
    <?= stat_card('Waiting for confirmation', $pendingCount, 'clock') ?>
</div>

<form class="filters" method="get" action="<?= e(url('admin/doctors.php')) ?>" role="search" aria-label="Find a doctor">
    <?= input_field(['name' => 'q', 'label' => 'Find a doctor by name or email', 'type' => 'search', 'required' => false, 'value' => '', 'attrs' => ['maxlength' => '80']]) ?>
    <div class="filters__actions"><button class="button button--secondary" type="submit"><?= icon('search') ?>Search</button></div>
</form>

<div class="section-title">
    <h2>Bookings in the next 7 days</h2>
    <a href="<?= e(url('admin/appointment.php')) ?>">All appointments</a>
</div>
<?php if ($weekAppointments): ?>
    <?= staff_appointment_table($weekAppointments, 'admin/index.php', 'admin/patient.php', true) ?>
<?php else: ?>
    <?= empty_state('No bookings in the next 7 days', 'New bookings will appear here.') ?>
<?php endif; ?>

<div class="section-title">
    <h2>Sessions in the next 7 days</h2>
    <a href="<?= e(url('admin/schedule.php')) ?>">All sessions</a>
</div>
<?php if ($weekSessions): ?>
    <?= staff_session_table($weekSessions, 'admin/schedule.php', true) ?>
<?php else: ?>
    <?= empty_state('No sessions in the next 7 days', 'Add sessions so patients can book.', button_link('Add a session', 'admin/schedule.php', 'primary', ['action' => 'add'], 'plus'), 'booking') ?>
<?php endif; ?>
<?php
app_end();
