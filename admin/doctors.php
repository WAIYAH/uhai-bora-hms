<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_role('a');

/** Validate the doctor form. Returns [values, errors]. */
function doctor_input(bool $passwordRequired, string $currentEmail = ''): array
{
    $values = [
        'name' => post_string('name'),
        'email' => post_string('email'),
        'phone' => preg_replace('/\s+/', ' ', post_string('phone')),
        'id_number' => post_string('id_number'),
        'specialty' => post_int('specialty'),
        'password' => post_secret('password'),
    ];
    $errors = array_filter([
        'name' => validate_full_name($values['name'], 'doctor’s name'),
        'email' => validate_email_address($values['email']),
        'phone' => validate_phone($values['phone']),
        'id_number' => validate_id_number($values['id_number']),
    ]);
    if (!$values['specialty'] || !isset(specialties()[$values['specialty']])) {
        $errors['specialty'] = 'Choose a specialty.';
    }
    if (!isset($errors['email']) && email_taken($values['email'], $currentEmail)) {
        $errors['email'] = 'Another account already uses this email address.';
    }
    if ($passwordRequired || $values['password'] !== '' || post_secret('password_confirmation') !== '') {
        $passwordError = new_password_error($values['password'], post_secret('password_confirmation'), $values['email']);
        if ($passwordError !== '') {
            $errors[$passwordError === 'The passwords do not match.' ? 'password_confirmation' : 'password'] = $passwordError;
        }
    }
    return [$values, $errors];
}

if (is_post()) {
    $action = post_string('action');

    if ($action === 'create') {
        list($values, $errors) = doctor_input(true);
        if ($errors) {
            fail_validation($errors, 'admin/doctors.php', ['action' => 'add']);
        }
        $newId = db_transaction(function () use ($values) {
            db_exec("INSERT INTO webuser (email, usertype) VALUES (?, 'd')", [$values['email']]);
            db_exec('INSERT INTO doctor (docemail, docname, docpassword, docnic, doctel, specialties) VALUES (?, ?, ?, ?, ?, ?)', [
                $values['email'], $values['name'], password_hash($values['password'], PASSWORD_DEFAULT),
                $values['id_number'], $values['phone'], $values['specialty'],
            ]);
            return db_insert_id();
        });
        audit('doctor.created', 'doctor', $newId);
        flash('success', $values['name'] . ' has been added. Share the email address and password with them securely.');
        redirect('admin/doctors.php', ['id' => $newId]);
    }

    if ($action === 'update') {
        $id = post_int('docid');
        $doctor = $id ? db_one('SELECT docid, docemail FROM doctor WHERE docid = ?', [$id]) : null;
        if (!$doctor) {
            not_found();
        }
        list($values, $errors) = doctor_input(false, $doctor['docemail']);
        if ($errors) {
            fail_validation($errors, 'admin/doctors.php', ['action' => 'edit', 'id' => $id]);
        }
        db_transaction(function () use ($values, $doctor, $id) {
            if (mb_strtolower($values['email']) !== mb_strtolower($doctor['docemail'])) {
                db_exec('UPDATE webuser SET email = ? WHERE email = ?', [$values['email'], $doctor['docemail']]);
            }
            db_exec('UPDATE doctor SET docname = ?, doctel = ?, docnic = ?, specialties = ? WHERE docid = ?',
                [$values['name'], $values['phone'], $values['id_number'], $values['specialty'], $id]);
            if ($values['password'] !== '') {
                set_account_password('d', $values['email'], $values['password']);
            }
        });
        audit($values['password'] !== '' ? 'doctor.updated_with_password' : 'doctor.updated', 'doctor', $id);
        flash('success', 'The doctor’s details have been updated.');
        redirect('admin/doctors.php', ['id' => $id]);
    }

    if ($action === 'delete') {
        $id = post_int('docid');
        $doctor = $id ? db_one('SELECT docemail, docname FROM doctor WHERE docid = ?', [$id]) : null;
        if ($doctor && db_exec("DELETE FROM webuser WHERE email = ? AND usertype = 'd'", [$doctor['docemail']]) === 1) {
            audit('doctor.deleted', 'doctor', $id);
            flash('success', $doctor['docname'] . ' has been deleted, with their sessions and bookings.');
        } else {
            flash('error', 'That doctor could not be deleted. They may already have been removed.');
        }
        redirect('admin/doctors.php');
    }
    redirect('admin/doctors.php');
}

$mode = query_string('action');
$id = query_int('id');

/* ---------- Add or edit ---------- */
if ($mode === 'add' || ($mode === 'edit' && $id)) {
    $doctor = [];
    if ($mode === 'edit') {
        $doctor = db_one('SELECT * FROM doctor WHERE docid = ?', [$id]);
        if (!$doctor) {
            not_found();
        }
    }
    app_start([
        'title' => $mode === 'add' ? 'Add a doctor' : 'Edit doctor',
        'nav' => 'doctors',
        'back' => $mode === 'add' ? ['Back to doctors', 'admin/doctors.php'] : ['Back to doctor', 'admin/doctors.php?id=' . $id],
        'lead' => $mode === 'add' ? 'Create a doctor account. Patients see the name, specialty and sessions only.' : 'Update the doctor’s details or set a new password.',
    ]);
    ?>
    <section class="panel form-panel">
        <?= error_summary() ?>
        <form class="form" method="post" action="<?= e(url('admin/doctors.php')) ?>" novalidate data-submit-once>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $mode === 'add' ? 'create' : 'update' ?>">
            <?php if ($mode === 'edit'): ?><input type="hidden" name="docid" value="<?= (int) $id ?>"><?php endif; ?>
            <?= input_field(['name' => 'name', 'label' => 'Name shown to patients', 'default' => $doctor['docname'] ?? '', 'hint' => 'For example “Dr. Amina Otieno”.', 'attrs' => ['maxlength' => '120']]) ?>
            <?= select_field(['name' => 'specialty', 'label' => 'Specialty', 'placeholder' => 'Choose a specialty', 'options' => specialties(), 'default' => isset($doctor['specialties']) ? (string) $doctor['specialties'] : '']) ?>
            <div class="form-grid form-grid--2">
                <?= input_field(['name' => 'email', 'label' => 'Email address', 'type' => 'email', 'default' => $doctor['docemail'] ?? '', 'hint' => 'Used to log in.', 'attrs' => ['maxlength' => '255', 'autocomplete' => 'off']]) ?>
                <?= input_field(['name' => 'phone', 'label' => 'Phone number', 'type' => 'tel', 'default' => $doctor['doctel'] ?? '', 'attrs' => ['maxlength' => '32']]) ?>
            </div>
            <?= input_field(['name' => 'id_number', 'label' => 'ID or passport number', 'default' => $doctor['docnic'] ?? '', 'hint' => 'Visible to administrators only.', 'attrs' => ['maxlength' => '32']]) ?>
            <fieldset class="form-section">
                <legend><?= $mode === 'add' ? 'Password' : 'Set a new password' ?></legend>
                <div class="form">
                    <?= input_field(['name' => 'password', 'label' => $mode === 'add' ? 'Password' : 'New password', 'type' => 'password', 'required' => $mode === 'add',
                        'autocomplete' => 'new-password', 'hint' => $mode === 'add' ? 'At least ' . PASSWORD_MIN_LENGTH . ' characters. Ask the doctor to change it after their first login.' : 'Leave blank to keep the current password.']) ?>
                    <?= input_field(['name' => 'password_confirmation', 'label' => 'Confirm password', 'type' => 'password', 'required' => $mode === 'add',
                        'autocomplete' => 'new-password', 'attrs' => ['data-password-confirm' => 'field-password']]) ?>
                </div>
            </fieldset>
            <div class="form-actions">
                <button class="button button--primary" type="submit"><?= $mode === 'add' ? 'Add doctor' : 'Save changes' ?></button>
                <a class="button button--ghost" href="<?= e(url('admin/doctors.php')) ?>">Cancel</a>
            </div>
        </form>
    </section>
    <?php
    app_end();
    exit;
}

/* ---------- View ---------- */
if ($id) {
    $doctor = db_one('SELECT d.*, sp.sname FROM doctor d LEFT JOIN specialties sp ON sp.id = d.specialties WHERE d.docid = ?', [$id]);
    if (!$doctor) {
        not_found();
    }
    $sessions = staff_sessions(['doctor' => $id, 'period' => 'upcoming'], 50);
    $sessionCount = (int) db_value('SELECT COUNT(*) FROM schedule WHERE docid = ?', [$id]);
    $bookingCount = (int) db_value('SELECT COUNT(*) FROM appointment a INNER JOIN schedule s ON s.scheduleid = a.scheduleid WHERE s.docid = ?', [$id]);
    app_start([
        'title' => $doctor['docname'],
        'nav' => 'doctors',
        'back' => ['Back to doctors', 'admin/doctors.php'],
        'eyebrow' => $doctor['sname'] ?? 'Doctor',
        'actions' => button_link('Edit', 'admin/doctors.php', 'secondary', ['action' => 'edit', 'id' => $id], 'edit')
            . button_link('Add a session', 'admin/schedule.php', 'primary', ['action' => 'add', 'doctor' => $id], 'plus'),
    ]);
    ?>
    <div class="split">
        <section class="panel" aria-labelledby="doctor-details">
            <h2 id="doctor-details">Details</h2>
            <dl class="details-list">
                <div><dt>Name</dt><dd><?= e($doctor['docname']) ?></dd></div>
                <div><dt>Specialty</dt><dd><?= e($doctor['sname'] ?? 'Not set') ?></dd></div>
                <div><dt>Email</dt><dd><?= e($doctor['docemail']) ?></dd></div>
                <div><dt>Phone</dt><dd><?= e($doctor['doctel']) ?></dd></div>
                <div><dt>ID or passport number</dt><dd><?= e($doctor['docnic']) ?></dd></div>
            </dl>
        </section>
        <section class="panel panel--danger" aria-labelledby="delete-doctor">
            <h2 id="delete-doctor">Delete doctor</h2>
            <p>Deleting <?= e($doctor['docname']) ?> also deletes their <?= e(plural($sessionCount, 'session')) ?> and <?= e(plural($bookingCount, 'booking')) ?>. Tell booked patients first.</p>
            <?= confirm_action([
                'label' => 'Delete doctor',
                'question' => 'Delete ' . $doctor['docname'] . '?',
                'detail' => 'Their account, sessions and bookings will be permanently removed.',
                'confirm' => 'Yes, delete doctor',
                'action' => 'admin/doctors.php',
                'fields' => ['action' => 'delete', 'docid' => $id],
            ]) ?>
        </section>
    </div>
    <div class="section-title"><h2>Upcoming sessions</h2></div>
    <?php if ($sessions): ?>
        <?= staff_session_table($sessions, 'admin/schedule.php', false) ?>
    <?php else: ?>
        <?= empty_state('No upcoming sessions', 'Add a session so patients can book with this doctor.', button_link('Add a session', 'admin/schedule.php', 'primary', ['action' => 'add', 'doctor' => $id], 'plus'), 'booking') ?>
    <?php endif; ?>
    <?php
    app_end();
    exit;
}

/* ---------- List ---------- */
$search = mb_substr(query_string('q'), 0, 80);
$sql = 'SELECT d.docid, d.docname, d.docemail, d.doctel, sp.sname,
               (SELECT COUNT(*) FROM schedule s WHERE s.docid = d.docid AND ' . UPCOMING_SQL . ') AS upcoming
        FROM doctor d LEFT JOIN specialties sp ON sp.id = d.specialties';
$params = [];
if ($search !== '') {
    $sql .= ' WHERE d.docname LIKE ? OR d.docemail LIKE ? OR sp.sname LIKE ?';
    $pattern = like_pattern($search);
    $params = [$pattern, $pattern, $pattern];
}
$doctors = db_all($sql . ' ORDER BY d.docname LIMIT 500', $params);

app_start([
    'title' => 'Doctors',
    'nav' => 'doctors',
    'lead' => 'Doctor accounts and their specialties.',
    'actions' => button_link('Add a doctor', 'admin/doctors.php', 'primary', ['action' => 'add'], 'plus'),
]);
?>
<form class="filters" method="get" action="<?= e(url('admin/doctors.php')) ?>" role="search" aria-label="Search doctors">
    <?= input_field(['name' => 'q', 'label' => 'Name, email or specialty', 'type' => 'search', 'required' => false, 'value' => $search, 'attrs' => ['maxlength' => '80']]) ?>
    <div class="filters__actions">
        <button class="button button--secondary" type="submit"><?= icon('search') ?>Search</button>
        <?php if ($search !== ''): ?><a class="button button--ghost" href="<?= e(url('admin/doctors.php')) ?>">Clear</a><?php endif; ?>
    </div>
</form>
<?php if ($doctors): ?>
    <p class="result-count" role="status"><?= e(plural(count($doctors), 'doctor')) ?></p>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th scope="col">Doctor</th><th scope="col">Specialty</th><th scope="col">Email</th><th scope="col">Upcoming sessions</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
            <?php foreach ($doctors as $doctor): ?>
                <tr>
                    <th scope="row" data-label="Doctor"><a href="<?= e(url('admin/doctors.php', ['id' => (int) $doctor['docid']])) ?>"><?= e($doctor['docname']) ?></a></th>
                    <td data-label="Specialty"><?= e($doctor['sname'] ?? 'Not set') ?></td>
                    <td data-label="Email"><?= e($doctor['docemail']) ?></td>
                    <td data-label="Upcoming sessions"><?= (int) $doctor['upcoming'] ?></td>
                    <td class="cell-actions"><div class="row-actions">
                        <a class="button button--secondary button--small" href="<?= e(url('admin/doctors.php', ['action' => 'edit', 'id' => (int) $doctor['docid']])) ?>"><?= icon('edit') ?>Edit<span class="visually-hidden"> <?= e($doctor['docname']) ?></span></a>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <?= empty_state(
        $search !== '' ? 'No doctors match your search' : 'No doctors yet',
        $search !== '' ? 'Check the spelling or clear the search.' : 'Add the first doctor so patients can start booking.',
        $search !== '' ? button_link('Clear search', 'admin/doctors.php', 'secondary') : button_link('Add a doctor', 'admin/doctors.php', 'primary', ['action' => 'add'], 'plus'),
        'dashboard-team'
    ) ?>
<?php endif; ?>
<?php
app_end();
