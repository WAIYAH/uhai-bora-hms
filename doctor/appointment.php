<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_role('d');
$doctorId = (int) $user['id'];

if (is_post()) {
    handle_appointment_action($doctorId);
    redirect_back('doctor/appointment.php');
}

$period = in_array(query_string('period'), ['past', 'all'], true) ? query_string('period') : 'upcoming';
$date = query_date('date');
$status = isset(appointment_statuses()[query_string('status')]) ? query_string('status') : '';
$rows = staff_appointments(['doctor' => $doctorId, 'period' => $period, 'date' => $date, 'status' => $status]);
$filterQuery = array_filter(['period' => $period === 'upcoming' ? null : $period, 'date' => $date, 'status' => $status]);

app_start([
    'title' => 'My appointments',
    'nav' => 'appointments',
    'lead' => 'Bookings for your sessions. Confirm new bookings and keep statuses up to date.',
]);
?>
<?= period_tabs($period, 'doctor/appointment.php', array_filter(['date' => $date, 'status' => $status])) ?>
<form class="filters" method="get" action="<?= e(url('doctor/appointment.php')) ?>" aria-label="Filter appointments">
    <?php if ($period !== 'upcoming'): ?><input type="hidden" name="period" value="<?= e($period) ?>"><?php endif; ?>
    <?= input_field(['name' => 'date', 'label' => 'Session date', 'type' => 'date', 'required' => false, 'value' => $date]) ?>
    <?= select_field(['name' => 'status', 'label' => 'Status', 'required' => false, 'value' => $status, 'placeholder' => 'Any status', 'options' => appointment_statuses()]) ?>
    <div class="filters__actions">
        <button class="button button--secondary" type="submit">Filter</button>
        <?php if ($date !== '' || $status !== ''): ?><a class="button button--ghost" href="<?= e(url('doctor/appointment.php', $period === 'upcoming' ? [] : ['period' => $period])) ?>">Clear</a><?php endif; ?>
    </div>
</form>

<?php if ($rows): ?>
    <p class="result-count" role="status"><?= e(plural(count($rows), 'booking')) ?></p>
    <?= staff_appointment_table($rows, 'doctor/appointment.php' . ($filterQuery ? '?' . http_build_query($filterQuery) : ''), 'doctor/patient.php', false) ?>
<?php else: ?>
    <?= empty_state(
        'No bookings to show',
        $date !== '' || $status !== '' ? 'No bookings match these filters.' : 'When patients book your sessions, their bookings appear here.',
        button_link('Manage my sessions', 'doctor/schedule.php', 'secondary')
    ) ?>
<?php endif; ?>
<?php
app_end();
