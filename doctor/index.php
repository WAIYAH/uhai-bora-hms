<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_role('d');
$doctorId = (int) $user['id'];

if (is_post()) {
    handle_appointment_action($doctorId);
    redirect('doctor/index.php');
}

$todaySessions = (int) db_value('SELECT COUNT(*) FROM schedule WHERE docid = ? AND scheduledate = CURDATE()', [$doctorId]);
$upcomingBookings = (int) db_value(
    'SELECT COUNT(*) FROM appointment a INNER JOIN schedule s ON s.scheduleid = a.scheduleid
     WHERE s.docid = ? AND a.status IN ' . ACTIVE_STATUSES_SQL . ' AND ' . UPCOMING_SQL,
    [$doctorId]
);
$pending = staff_appointments(['doctor' => $doctorId, 'status' => 'pending', 'period' => 'upcoming'], 10);
$pendingCount = (int) db_value(
    "SELECT COUNT(*) FROM appointment a INNER JOIN schedule s ON s.scheduleid = a.scheduleid
     WHERE s.docid = ? AND a.status = 'pending' AND " . UPCOMING_SQL,
    [$doctorId]
);
$patientCount = (int) db_value(
    'SELECT COUNT(DISTINCT a.pid) FROM appointment a INNER JOIN schedule s ON s.scheduleid = a.scheduleid WHERE s.docid = ?',
    [$doctorId]
);
$weekSessions = array_values(array_filter(staff_sessions(['doctor' => $doctorId, 'period' => 'upcoming'], 50), function ($row) {
    return $row['scheduledate'] <= date('Y-m-d', strtotime('+7 days'));
}));

app_start([
    'title' => 'Dashboard',
    'nav' => 'dashboard',
    'heading' => 'Welcome, ' . $user['name'],
    'lead' => 'Your sessions and bookings at a glance.',
    'actions' => button_link('Add a session', 'doctor/schedule.php', 'primary', ['action' => 'add'], 'plus'),
]);
?>
<div class="stats">
    <?= stat_card('Sessions today', $todaySessions, 'calendar') ?>
    <?= stat_card('Upcoming bookings', $upcomingBookings, 'clipboard') ?>
    <?= stat_card('Waiting for confirmation', $pendingCount, 'clock') ?>
    <?= stat_card('My patients', $patientCount, 'users') ?>
</div>

<div class="section-title">
    <h2>Waiting for your confirmation</h2>
    <a href="<?= e(url('doctor/appointment.php', ['status' => 'pending'])) ?>">See all pending</a>
</div>
<?php if ($pending): ?>
    <?= staff_appointment_table($pending, 'doctor/index.php', 'doctor/patient.php', false) ?>
<?php else: ?>
    <?= empty_state('Nothing waiting', 'New bookings for your sessions will appear here for you to confirm.', '', 'empty-appointments') ?>
<?php endif; ?>

<div class="section-title">
    <h2>Your sessions in the next 7 days</h2>
    <a href="<?= e(url('doctor/schedule.php')) ?>">All my sessions</a>
</div>
<?php if ($weekSessions): ?>
    <?= staff_session_table($weekSessions, 'doctor/schedule.php', false) ?>
<?php else: ?>
    <?= empty_state('No sessions in the next 7 days', 'Add a session so patients can book with you.', button_link('Add a session', 'doctor/schedule.php', 'primary', ['action' => 'add'], 'plus'), 'booking') ?>
<?php endif; ?>
<?php
app_end();
