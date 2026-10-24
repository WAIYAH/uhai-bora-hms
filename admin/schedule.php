<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_role('a');

if (is_post()) {
    $action = post_string('action');
    if (handle_appointment_action(null)) {
        redirect_back('admin/schedule.php');
    }
    if ($action === 'create') {
        list($values, $errors) = validate_session_input(true);
        if ($errors) {
            fail_validation($errors, 'admin/schedule.php', ['action' => 'add']);
        }
        db_exec('INSERT INTO schedule (docid, title, scheduledate, scheduletime, nop) VALUES (?, ?, ?, ?, ?)',
            [$values['docid'], $values['title'], $values['date'], $values['time'], $values['nop']]);
        $newId = db_insert_id();
        audit('session.created', 'session', $newId);
        flash('success', 'Session added. Patients can now book it.');
        redirect('admin/schedule.php', ['id' => $newId]);
    }
    if ($action === 'update') {
        $id = post_int('scheduleid');
        $session = $id ? find_session($id) : null;
        if (!$session) {
            not_found();
        }
        list($values, $errors) = validate_session_input(true, (int) $session['booked']);
        if ($errors) {
            fail_validation($errors, 'admin/schedule.php', ['action' => 'edit', 'id' => $id]);
        }
        db_exec('UPDATE schedule SET docid = ?, title = ?, scheduledate = ?, scheduletime = ?, nop = ? WHERE scheduleid = ?',
            [$values['docid'], $values['title'], $values['date'], $values['time'], $values['nop'], $id]);
        audit('session.updated', 'session', $id);
        flash('success', 'Session updated.');
        redirect('admin/schedule.php', ['id' => $id]);
    }
    if ($action === 'delete_session') {
        $id = post_int('scheduleid');
        if ($id && db_exec('DELETE FROM schedule WHERE scheduleid = ?', [$id]) === 1) {
            audit('session.deleted', 'session', $id);
            flash('success', 'Session deleted, together with its bookings.');
        } else {
            flash('error', 'That session could not be deleted. It may already have been removed.');
        }
        redirect('admin/schedule.php');
    }
    redirect('admin/schedule.php');
}

$mode = query_string('action');
$id = query_int('id');

/* ---------- Add or edit ---------- */
if ($mode === 'add' || ($mode === 'edit' && $id)) {
    $session = [];
    if ($mode === 'edit') {
        $session = find_session($id);
        if (!$session) {
            not_found();
        }
    } elseif (query_int('doctor')) {
        $session['docid'] = query_int('doctor');
    }
    app_start([
        'title' => $mode === 'add' ? 'Add a session' : 'Edit session',
        'nav' => 'sessions',
        'back' => $mode === 'add' ? ['Back to sessions', 'admin/schedule.php'] : ['Back to session', 'admin/schedule.php?id=' . $id],
        'lead' => 'Patients can book a session as soon as it is saved.',
    ]);
    ?>
    <section class="panel form-panel">
        <?= error_summary() ?>
        <form class="form" method="post" action="<?= e(url('admin/schedule.php')) ?>" novalidate data-submit-once>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $mode === 'add' ? 'create' : 'update' ?>">
            <?php if ($mode === 'edit'): ?><input type="hidden" name="scheduleid" value="<?= (int) $id ?>"><?php endif; ?>
            <?= session_form_fields($session, true) ?>
            <?php if ($mode === 'edit' && (int) $session['booked'] > 0): ?>
                <div class="alert alert--info"><?= icon('info') ?><p>This session has <?= e(plural((int) $session['booked'], 'active booking')) ?>. If you change the doctor, date or time, make sure those patients are told.</p></div>
            <?php endif; ?>
            <div class="form-actions">
                <button class="button button--primary" type="submit"><?= $mode === 'add' ? 'Add session' : 'Save changes' ?></button>
                <a class="button button--ghost" href="<?= e(url('admin/schedule.php')) ?>">Cancel</a>
            </div>
        </form>
    </section>
    <?php
    app_end();
    exit;
}

/* ---------- View ---------- */
if ($id) {
    $session = find_session($id);
    if (!$session) {
        not_found();
    }
    $bookings = staff_appointments(['session' => $id, 'period' => 'all'], 1000);
    $upcoming = is_future_slot($session['scheduledate'], $session['scheduletime']);
    app_start([
        'title' => $session['title'],
        'nav' => 'sessions',
        'back' => ['Back to sessions', 'admin/schedule.php'],
        'eyebrow' => $session['docname'] . ' · ' . format_date_long($session['scheduledate']) . ' · ' . format_time($session['scheduletime']),
        'actions' => $upcoming ? button_link('Edit session', 'admin/schedule.php', 'secondary', ['action' => 'edit', 'id' => $id], 'edit') : '',
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
        <?= staff_appointment_table($bookings, 'admin/schedule.php?id=' . $id, 'admin/patient.php', false) ?>
    <?php else: ?>
        <?= empty_state('No bookings yet', 'Patients who book this session will appear here.') ?>
    <?php endif; ?>
    <section class="panel panel--danger section-title" aria-labelledby="delete-session">
        <div>
            <h2 id="delete-session">Delete this session</h2>
            <p>Deleting the session also deletes its <?= e(plural(count($bookings), 'booking')) ?>. Tell booked patients first.</p>
            <?= confirm_action([
                'label' => 'Delete session',
                'question' => 'Delete this session and its bookings?',
                'detail' => $session['title'] . ' with ' . $session['docname'] . ' on ' . format_date($session['scheduledate']) . '. This cannot be undone.',
                'confirm' => 'Yes, delete session',
                'action' => 'admin/schedule.php',
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
$doctor = query_int('doctor');
$sessions = staff_sessions(['doctor' => $doctor, 'period' => $period, 'date' => $date], 500);
$doctorOptions = [];
foreach (db_all('SELECT docid, docname FROM doctor ORDER BY docname') as $row) {
    $doctorOptions[(int) $row['docid']] = $row['docname'];
}

app_start([
    'title' => 'Sessions',
    'nav' => 'sessions',
    'lead' => 'All appointment sessions across doctors.',
    'actions' => button_link('Add a session', 'admin/schedule.php', 'primary', ['action' => 'add'], 'plus'),
]);
?>
<?= period_tabs($period, 'admin/schedule.php', array_filter(['date' => $date, 'doctor' => $doctor])) ?>
<form class="filters" method="get" action="<?= e(url('admin/schedule.php')) ?>" aria-label="Filter sessions">
    <?php if ($period !== 'upcoming'): ?><input type="hidden" name="period" value="<?= e($period) ?>"><?php endif; ?>
    <?= input_field(['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => false, 'value' => $date]) ?>
    <?= select_field(['name' => 'doctor', 'label' => 'Doctor', 'required' => false, 'value' => (string) ($doctor ?? ''), 'placeholder' => 'All doctors', 'options' => $doctorOptions]) ?>
    <div class="filters__actions">
        <button class="button button--secondary" type="submit">Filter</button>
        <?php if ($date !== '' || $doctor): ?><a class="button button--ghost" href="<?= e(url('admin/schedule.php', $period === 'upcoming' ? [] : ['period' => $period])) ?>">Clear</a><?php endif; ?>
    </div>
</form>
<?php if ($sessions): ?>
    <p class="result-count" role="status"><?= e(plural(count($sessions), 'session')) ?></p>
    <?= staff_session_table($sessions, 'admin/schedule.php', true) ?>
<?php else: ?>
    <?= empty_state(
        'No sessions to show',
        $date !== '' || $doctor ? 'No sessions match these filters.' : 'Add a session so patients can book.',
        button_link('Add a session', 'admin/schedule.php', 'primary', ['action' => 'add'], 'plus'),
        'booking'
    ) ?>
<?php endif; ?>
<?php
app_end();
