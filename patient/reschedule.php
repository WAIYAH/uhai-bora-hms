<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_role('p');
$patientId = (int) $user['id'];

$appointmentId = is_post() ? post_int('id') : query_int('id');
if (!$appointmentId) {
    not_found();
}
$booking = db_one(appointment_select_sql() . ' WHERE a.appoid = ? AND a.pid = ?', [$appointmentId, $patientId]);
if (!$booking) {
    not_found();
}

if (is_post()) {
    $newSession = post_int('scheduleid');
    if (!$newSession) {
        fail_validation(['scheduleid' => 'Choose a new session.'], 'patient/reschedule.php', ['id' => $appointmentId]);
    }
    $result = reschedule_appointment($patientId, $appointmentId, $newSession);
    if ($result['ok']) {
        audit('appointment.rescheduled', 'appointment', $appointmentId);
        flash('success', 'Your booking ' . appointment_reference($appointmentId) . ' has been moved. Its status is “Pending confirmation” until the clinic confirms the new time.');
        redirect('patient/appointment.php', ['id' => $appointmentId]);
    }
    fail_validation(['scheduleid' => booking_error_message($result['error'])], 'patient/reschedule.php', ['id' => $appointmentId]);
}

$canChange = (int) $booking['is_upcoming'] === 1 && in_array($booking['status'], ['pending', 'confirmed'], true);
$scope = query_string('scope') === 'all' ? 'all' : 'doctor';
$sessions = [];
if ($canChange) {
    $sessions = upcoming_sessions(
        ['patient' => $patientId, 'doctor' => $scope === 'doctor' ? (int) $booking['docid'] : null],
        200,
        0
    );
    $sessions = array_values(array_filter($sessions, function ($row) use ($booking) {
        return (int) $row['scheduleid'] !== (int) $booking['scheduleid']
            && $row['my_appointment'] === null
            && spaces_left($row) > 0;
    }));
}
$groups = [];
foreach ($sessions as $row) {
    $groups[$row['scheduledate']][] = $row;
}

app_start([
    'title' => 'Reschedule booking',
    'nav' => 'bookings',
    'back' => ['Back to booking', 'patient/appointment.php?id=' . $appointmentId],
    'lead' => 'Choose a new session. Your current place is kept until the move is confirmed.',
]);
?>
<section class="panel notice" aria-labelledby="current-title">
    <h2 id="current-title">Current booking</h2>
    <p><strong><?= e($booking['title']) ?></strong> with <?= e($booking['docname']) ?> — <?= session_when($booking['scheduledate'], $booking['scheduletime']) ?> <?= status_badge($booking['status']) ?></p>
</section>

<?php if (!$canChange): ?>
    <div class="alert alert--warning"><?= icon('alert') ?><p>This booking can no longer be changed because the session has started, finished or been cancelled. <a href="<?= e(url('patient/schedule.php')) ?>">Book a new session</a></p></div>
<?php else: ?>
    <ul class="tabs">
        <li><a href="<?= e(url('patient/reschedule.php', ['id' => $appointmentId])) ?>"<?= $scope === 'doctor' ? ' aria-current="page"' : '' ?>>Same doctor</a></li>
        <li><a href="<?= e(url('patient/reschedule.php', ['id' => $appointmentId, 'scope' => 'all'])) ?>"<?= $scope === 'all' ? ' aria-current="page"' : '' ?>>All doctors</a></li>
    </ul>
    <?php if (!$sessions): ?>
        <?= empty_state(
            'No other sessions with places',
            $scope === 'doctor' ? 'There are no other upcoming sessions with places for this doctor. Try sessions with all doctors.' : 'There are no other upcoming sessions with places right now.',
            $scope === 'doctor' ? button_link('Show all doctors', 'patient/reschedule.php', 'secondary', ['id' => $appointmentId, 'scope' => 'all']) : ''
        ) ?>
    <?php else: ?>
        <?= error_summary(null, ['scheduleid' => 'new-session-first']) ?>
        <form class="form" method="post" action="<?= e(url('patient/reschedule.php')) ?>" data-submit-once>
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $appointmentId ?>">
            <fieldset>
                <legend>Choose a new session</legend>
                <?php if (field_error('scheduleid') !== ''): ?>
                    <p class="field__error" id="scheduleid-error"><span class="visually-hidden">Error: </span><?= e(field_error('scheduleid')) ?></p>
                <?php endif; ?>
                <?php $first = true; foreach ($groups as $date => $rows): ?>
                    <div class="date-group">
                        <h3 class="date-group__title"><?= e(format_date_long($date)) ?></h3>
                        <ul class="record-list">
                            <?php foreach ($rows as $row): $radioId = 'session-' . (int) $row['scheduleid']; ?>
                                <li class="record">
                                    <div class="checkbox">
                                        <input class="checkbox__input" type="radio" name="scheduleid" id="<?= $first ? 'new-session-first' : e($radioId) ?>" value="<?= (int) $row['scheduleid'] ?>" required<?= field_error('scheduleid') !== '' ? ' aria-describedby="scheduleid-error"' : '' ?>>
                                        <label class="checkbox__label" for="<?= $first ? 'new-session-first' : e($radioId) ?>">
                                            <strong><?= e(format_time($row['scheduletime'])) ?> · <?= e($row['title']) ?></strong><br>
                                            <span class="muted"><?= e($row['docname']) ?><?= $row['sname'] ? ' · ' . e($row['sname']) : '' ?> · <?= e(plural(spaces_left($row), 'place')) ?> left</span>
                                        </label>
                                    </div>
                                </li>
                            <?php $first = false; endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </fieldset>
            <div class="form-actions">
                <button class="button button--primary" type="submit" data-loading-label="Moving your booking…">Move my booking</button>
                <a class="button button--ghost" href="<?= e(url('patient/appointment.php', ['id' => $appointmentId])) ?>">Keep current booking</a>
            </div>
        </form>
    <?php endif; ?>
<?php endif; ?>
<?php
app_end();
