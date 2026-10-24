<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_role('d');
$doctorId = (int) $user['id'];

function doctor_session(int $id, int $doctorId): ?array
{
    $session = find_session($id);
    return $session && (int) $session['docid'] === $doctorId ? $session : null;
}

if (is_post()) {
    $action = post_string('action');
    if (handle_appointment_action($doctorId)) {
        redirect_back('doctor/schedule.php');
    }
    if ($action === 'create') {
        list($values, $errors) = validate_session_input(false);
        if ($errors) {
            fail_validation($errors, 'doctor/schedule.php', ['action' => 'add']);
        }
        db_exec('INSERT INTO schedule (docid, title, scheduledate, scheduletime, nop) VALUES (?, ?, ?, ?, ?)',
            [$doctorId, $values['title'], $values['date'], $values['time'], $values['nop']]);
        $newId = db_insert_id();
        audit('session.created', 'session', $newId);
        flash('success', 'Session added. Patients can now book it.');
        redirect('doctor/schedule.php', ['id' => $newId]);
    }
    if ($action === 'update') {
        $id = post_int('scheduleid');
        $session = $id ? doctor_session($id, $doctorId) : null;
        if (!$session) {
            not_found();
        }
        list($values, $errors) = validate_session_input(false, (int) $session['booked']);
        if ($errors) {
            fail_validation($errors, 'doctor/schedule.php', ['action' => 'edit', 'id' => $id]);
        }
        db_exec('UPDATE schedule SET title = ?, scheduledate = ?, scheduletime = ?, nop = ? WHERE scheduleid = ? AND docid = ?',
            [$values['title'], $values['date'], $values['time'], $values['nop'], $id, $doctorId]);
        audit('session.updated', 'session', $id);
        flash('success', 'Session updated.');
        redirect('doctor/schedule.php', ['id' => $id]);
    }
    if ($action === 'delete_session') {
        $id = post_int('scheduleid');
        if ($id && db_exec('DELETE FROM schedule WHERE scheduleid = ? AND docid = ?', [$id, $doctorId]) === 1) {
            audit('session.deleted', 'session', $id);
            flash('success', 'Session deleted, together with its bookings.');
        } else {
            flash('error', 'That session could not be deleted. It may already have been removed.');
        }
        redirect('doctor/schedule.php');
    }
    redirect('doctor/schedule.php');
}

$mode = query_string('action');
$id = query_int('id');

/* ---------- Add or edit ---------- */
if ($mode === 'add' || ($mode === 'edit' && $id)) {
    $session = [];
    if ($mode === 'edit') {
        $session = doctor_session($id, $doctorId);
        if (!$session) {
            not_found();
        }
    }
    app_start([
        'title' => $mode === 'add' ? 'Add a session' : 'Edit session',
        'nav' => 'sessions',
        'back' => $mode === 'add' ? ['Back to my sessions', 'doctor/schedule.php'] : ['Back to session', 'doctor/schedule.php?id=' . $id],
        'lead' => $mode === 'add' ? 'Patients can book this session as soon as you save it.' : 'Patients with bookings will see the updated details in their account.',
    ]);
    ?>
    <section class="panel form-panel">
        <?= error_summary() ?>
        <form class="form" method="post" action="<?= e(url('doctor/schedule.php')) ?>" novalidate data-submit-once>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $mode === 'add' ? 'create' : 'update' ?>">
            <?php if ($mode === 'edit'): ?><input type="hidden" name="scheduleid" value="<?= (int) $id ?>"><?php endif; ?>
            <?= session_form_fields($session) ?>
            <?php if ($mode === 'edit' && (int) $session['booked'] > 0): ?>
                <div class="alert alert--info"><?= icon('info') ?><p>This session has <?= e(plural((int) $session['booked'], 'active booking')) ?>. If you change the date or time, please make sure those patients are told.</p></div>
            <?php endif; ?>
            <div class="form-actions">
                <button class="button button--primary" type="submit"><?= $mode === 'add' ? 'Add session' : 'Save changes' ?></button>
                <a class="button button--ghost" href="<?= e(url('doctor/schedule.php')) ?>">Cancel</a>
            </div>
        </form>
    </section>
    <?php
    app_end();
    exit;
}

/* ---------- View one session ---------- */
if ($id) {
    $session = doctor_session($id, $doctorId);
    if (!$session) {
        not_found();
    }
    $bookings = staff_appointments(['doctor' => $doctorId, 'session' => $id, 'period' => 'all'], 1000);
    $upcoming = is_future_slot($session['scheduledate'], $session['scheduletime']);
    $actions = $upcoming ? button_link('Edit session', 'doctor/schedule.php', 'secondary', ['action' => 'edit', 'id' => $id], 'edit') : '';
    app_start([
        'title' => $session['title'],
        'nav' => 'sessions',
        'back' => ['Back to my sessions', 'doctor/schedule.php'],
        'eyebrow' => format_date_long($session['scheduledate']) . ' · ' . format_time($session['scheduletime']),
        'actions' => $actions,
    ]);
    ?>
    <div class="stats">
        <?= stat_card('Places', (int) $session['nop'], 'users') ?>
        <?= stat_card('Active bookings', (int) $session['booked'], 'clipboard') ?>
        <?= stat_card('Places left', spaces_left($session), 'calendar') ?>
        <?= stat_card('Status', $upcoming ? 'Upcoming' : 'Started or past', 'clock') ?>
    </div>
    <div class="section-title"><h2>Patients booked</h2></div>
    <?php if ($bookings): ?>
        <?= staff_appointment_table($bookings, 'doctor/schedule.php?id=' . $id, 'doctor/patient.php', false) ?>
    <?php else: ?>
        <?= empty_state('No bookings yet', 'Patients who book this session will appear here.') ?>
    <?php endif; ?>
    <section class="panel panel--danger section-title" aria-labelledby="delete-session">
        <div>
            <h2 id="delete-session">Delete this session</h2>
            <p>Deleting the session also deletes its <?= e(plural(count($bookings), 'booking')) ?>. If patients are booked, tell them first.</p>
            <?= confirm_action([
                'label' => 'Delete session',
                'question' => 'Delete this session and its bookings?',
                'detail' => $session['title'] . ' on ' . format_date($session['scheduledate']) . '. This cannot be undone.',
                'confirm' => 'Yes, delete session',
                'action' => 'doctor/schedule.php',
                'fields' => ['action' => 'delete_session', 'scheduleid' => $id],
            ]) ?>
        </div>
    </section>
    <?php
    app_end();
    exit;
}

/* ---------- List ---------- */
$period = in_array(query_string('period'), ['past', 'all'], true) ? query_string('period') : 'upcoming';
$date = query_date('date');
$sessions = staff_sessions(['doctor' => $doctorId, 'period' => $period, 'date' => $date]);

app_start([
    'title' => 'My sessions',
    'nav' => 'sessions',
    'lead' => 'Sessions you have published for patients to book.',
    'actions' => button_link('Add a session', 'doctor/schedule.php', 'primary', ['action' => 'add'], 'plus'),
]);
?>
<?= period_tabs($period, 'doctor/schedule.php', array_filter(['date' => $date])) ?>
<form class="filters" method="get" action="<?= e(url('doctor/schedule.php')) ?>" aria-label="Filter sessions">
    <?php if ($period !== 'upcoming'): ?><input type="hidden" name="period" value="<?= e($period) ?>"><?php endif; ?>
    <?= input_field(['name' => 'date', 'label' => 'Session date', 'type' => 'date', 'required' => false, 'value' => $date]) ?>
    <div class="filters__actions">
        <button class="button button--secondary" type="submit">Filter</button>
        <?php if ($date !== ''): ?><a class="button button--ghost" href="<?= e(url('doctor/schedule.php', $period === 'upcoming' ? [] : ['period' => $period])) ?>">Clear</a><?php endif; ?>
    </div>
</form>
<?php if ($sessions): ?>
    <?= staff_session_table($sessions, 'doctor/schedule.php', false) ?>
<?php else: ?>
    <?= empty_state(
        $date !== '' ? 'No sessions on this date' : 'No sessions to show',
        $date !== '' ? 'Try another date or clear the filter.' : 'Add a session so patients can book with you.',
        button_link('Add a session', 'doctor/schedule.php', 'primary', ['action' => 'add'], 'plus'),
        'booking'
    ) ?>
<?php endif; ?>
<?php
app_end();
