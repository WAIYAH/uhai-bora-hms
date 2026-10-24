<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_role('a');

if (is_post()) {
    handle_appointment_action(null);
    redirect_back('admin/appointment.php');
}

$period = in_array(query_string('period'), ['past', 'all'], true) ? query_string('period') : 'upcoming';
$date = query_date('date');
$doctor = query_int('doctor');
$status = isset(appointment_statuses()[query_string('status')]) ? query_string('status') : '';
$rows = staff_appointments(['doctor' => $doctor, 'period' => $period, 'date' => $date, 'status' => $status], 500);
$filterQuery = array_filter(['period' => $period === 'upcoming' ? null : $period, 'date' => $date, 'doctor' => $doctor, 'status' => $status]);
$doctorOptions = [];
foreach (db_all('SELECT docid, docname FROM doctor ORDER BY docname') as $row) {
    $doctorOptions[(int) $row['docid']] = $row['docname'];
}

app_start([
    'title' => 'Appointments',
    'nav' => 'appointments',
    'lead' => 'Every booking across doctors. Confirm bookings and keep statuses up to date.',
]);
?>
<?= period_tabs($period, 'admin/appointment.php', array_filter(['date' => $date, 'doctor' => $doctor, 'status' => $status])) ?>
<form class="filters" method="get" action="<?= e(url('admin/appointment.php')) ?>" aria-label="Filter appointments">
    <?php if ($period !== 'upcoming'): ?><input type="hidden" name="period" value="<?= e($period) ?>"><?php endif; ?>
    <?= input_field(['name' => 'date', 'label' => 'Session date', 'type' => 'date', 'required' => false, 'value' => $date]) ?>
    <?= select_field(['name' => 'doctor', 'label' => 'Doctor', 'required' => false, 'value' => (string) ($doctor ?? ''), 'placeholder' => 'All doctors', 'options' => $doctorOptions]) ?>
    <?= select_field(['name' => 'status', 'label' => 'Status', 'required' => false, 'value' => $status, 'placeholder' => 'Any status', 'options' => appointment_statuses()]) ?>
    <div class="filters__actions">
        <button class="button button--secondary" type="submit">Filter</button>
        <?php if ($date !== '' || $doctor || $status !== ''): ?><a class="button button--ghost" href="<?= e(url('admin/appointment.php', $period === 'upcoming' ? [] : ['period' => $period])) ?>">Clear</a><?php endif; ?>
    </div>
</form>
<?php if ($rows): ?>
    <p class="result-count" role="status"><?= e(plural(count($rows), 'booking')) ?></p>
    <?= staff_appointment_table($rows, 'admin/appointment.php' . ($filterQuery ? '?' . http_build_query($filterQuery) : ''), 'admin/patient.php', true) ?>
<?php else: ?>
    <?= empty_state('No bookings to show', $date !== '' || $doctor || $status !== '' ? 'No bookings match these filters.' : 'Bookings appear here when patients book sessions.') ?>
<?php endif; ?>
<?php
app_end();
