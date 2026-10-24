<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_role('a');

if (is_post()) {
    $action = post_string('action');
    if (handle_appointment_action(null)) {
        redirect_back('admin/patient.php');
    }
    $id = post_int('pid');
    $patient = $id ? db_one('SELECT pid, pemail, pname FROM patient WHERE pid = ?', [$id]) : null;
    if (!$patient) {
        not_found();
    }
    if ($action === 'reset_password') {
        $passwordError = new_password_error(post_secret('password'), post_secret('password_confirmation'), $patient['pemail']);
        if ($passwordError !== '') {
            fail_validation([$passwordError === 'The passwords do not match.' ? 'password_confirmation' : 'password' => $passwordError], 'admin/patient.php', ['id' => $id]);
        }
        set_account_password('p', $patient['pemail'], post_secret('password'));
        audit('patient.password_reset_by_admin', 'patient', $id);
        flash('success', 'A new password has been set for ' . $patient['pname'] . '. Share it with them securely and ask them to change it after logging in.');
        redirect('admin/patient.php', ['id' => $id]);
    }
    if ($action === 'delete') {
        if (db_exec("DELETE FROM webuser WHERE email = ? AND usertype = 'p'", [$patient['pemail']]) === 1) {
            audit('patient.deleted_by_admin', 'patient', $id);
            db_exec('UPDATE audit_log SET actor_email = NULL WHERE actor_email = ?', [$patient['pemail']]);
            flash('success', 'The patient account and its bookings have been deleted.');
        } else {
            flash('error', 'That patient could not be deleted. They may already have been removed.');
        }
        redirect('admin/patient.php');
    }
    redirect('admin/patient.php', ['id' => $id]);
}

/* ---------- View ---------- */
$id = query_int('id');
if ($id) {
    $patient = db_one('SELECT * FROM patient WHERE pid = ?', [$id]);
    if (!$patient) {
        not_found();
    }
    audit('patient.viewed', 'patient', $id);
    $bookings = staff_appointments(['patient' => $id, 'period' => 'all'], 500);

    app_start([
        'title' => $patient['pname'],
        'nav' => 'patients',
        'back' => ['Back to patients', 'admin/patient.php'],
        'eyebrow' => 'Patient',
        'lead' => 'Access to this record is logged.',
    ]);
    ?>
    <?= error_summary() ?>
    <div class="split">
        <section class="panel" aria-labelledby="details-title">
            <h2 id="details-title">Details</h2>
            <dl class="details-list">
                <div><dt>Name</dt><dd><?= e($patient['pname']) ?></dd></div>
                <div><dt>Email</dt><dd><?= e($patient['pemail']) ?></dd></div>
                <div><dt>Phone</dt><dd><?= e($patient['ptel']) ?></dd></div>
                <div><dt>Town or address</dt><dd><?= e($patient['paddress']) ?></dd></div>
                <div><dt>Date of birth</dt><dd><?= e(format_date_long($patient['pdob'])) ?> (age <?= e(age_on($patient['pdob'])) ?>)</dd></div>
                <div><dt>ID or passport number</dt><dd><?= e($patient['pnic']) ?></dd></div>
                <?php if (!empty($patient['pguardian_name'])): ?><div><dt>Parent or guardian</dt><dd><?= e($patient['pguardian_name']) ?></dd></div><?php endif; ?>
                <div><dt>Consent</dt><dd><?= !empty($patient['pconsented_at'])
                    ? 'Version ' . e($patient['pconsent_version']) . ' on ' . e(format_date(substr($patient['pconsented_at'], 0, 10)))
                    : 'Not recorded (account created before consent recording)' ?></dd></div>
            </dl>
        </section>
        <div>
            <section class="panel" aria-labelledby="reset-title">
                <h2 id="reset-title">Set a new password</h2>
                <p class="muted">Use this when a patient has forgotten their password and you have confirmed their identity.</p>
                <form class="form" method="post" action="<?= e(url('admin/patient.php')) ?>" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="pid" value="<?= (int) $id ?>">
                    <?= input_field(['name' => 'password', 'label' => 'New password', 'type' => 'password', 'autocomplete' => 'new-password', 'hint' => 'At least ' . PASSWORD_MIN_LENGTH . ' characters.']) ?>
                    <?= input_field(['name' => 'password_confirmation', 'label' => 'Confirm new password', 'type' => 'password', 'autocomplete' => 'new-password', 'attrs' => ['data-password-confirm' => 'field-password']]) ?>
                    <div class="form-actions"><button class="button button--primary" type="submit">Set password</button></div>
                </form>
            </section>
            <section class="panel panel--danger" aria-labelledby="delete-title">
                <h2 id="delete-title">Delete patient</h2>
                <p>Deletes the account and its <?= e(plural(count($bookings), 'booking')) ?>. Do this only when the patient asks, or when the law requires it.</p>
                <?= confirm_action([
                    'label' => 'Delete patient',
                    'question' => 'Delete ' . $patient['pname'] . '?',
                    'detail' => 'The account and all bookings will be permanently removed.',
                    'confirm' => 'Yes, delete patient',
                    'action' => 'admin/patient.php',
                    'fields' => ['action' => 'delete', 'pid' => $id],
                ]) ?>
            </section>
        </div>
    </div>
    <div class="section-title"><h2>Bookings</h2></div>
    <?php if ($bookings): ?>
        <?= staff_appointment_table($bookings, 'admin/patient.php?id=' . $id, 'admin/patient.php', true) ?>
    <?php else: ?>
        <?= empty_state('No bookings', 'This patient has not booked any sessions yet.') ?>
    <?php endif; ?>
    <?php
    app_end();
    exit;
}

/* ---------- List ---------- */
$search = mb_substr(query_string('q'), 0, 80);
$sql = 'SELECT p.pid, p.pname, p.pemail, p.ptel, p.pdob,
               (SELECT COUNT(*) FROM appointment a WHERE a.pid = p.pid) AS bookings
        FROM patient p';
$params = [];
if ($search !== '') {
    $sql .= ' WHERE p.pname LIKE ? OR p.pemail LIKE ? OR p.ptel LIKE ? OR p.pnic LIKE ?';
    $pattern = like_pattern($search);
    $params = [$pattern, $pattern, $pattern, $pattern];
}
$page = query_int('page') ?? 1;
$perPage = 50;
$patients = db_all($sql . ' ORDER BY p.pname LIMIT ' . ($perPage + 1) . ' OFFSET ' . (($page - 1) * $perPage), $params);
$hasMore = count($patients) > $perPage;
$patients = array_slice($patients, 0, $perPage);

app_start([
    'title' => 'Patients',
    'nav' => 'patients',
    'lead' => 'Registered patient accounts. Opening a record is logged.',
]);
?>
<form class="filters" method="get" action="<?= e(url('admin/patient.php')) ?>" role="search" aria-label="Search patients">
    <?= input_field(['name' => 'q', 'label' => 'Name, email, phone or ID number', 'type' => 'search', 'required' => false, 'value' => $search, 'attrs' => ['maxlength' => '80']]) ?>
    <div class="filters__actions">
        <button class="button button--secondary" type="submit"><?= icon('search') ?>Search</button>
        <?php if ($search !== ''): ?><a class="button button--ghost" href="<?= e(url('admin/patient.php')) ?>">Clear</a><?php endif; ?>
    </div>
</form>
<?php if ($patients): ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th scope="col">Patient</th><th scope="col">Email</th><th scope="col">Phone</th><th scope="col">Age</th><th scope="col">Bookings</th></tr></thead>
            <tbody>
            <?php foreach ($patients as $patient): ?>
                <tr>
                    <th scope="row" data-label="Patient"><a href="<?= e(url('admin/patient.php', ['id' => (int) $patient['pid']])) ?>"><?= e($patient['pname']) ?></a></th>
                    <td data-label="Email"><?= e($patient['pemail']) ?></td>
                    <td data-label="Phone"><?= e($patient['ptel']) ?></td>
                    <td data-label="Age"><?= e(age_on($patient['pdob'])) ?></td>
                    <td data-label="Bookings"><?= (int) $patient['bookings'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= pagination($page, $hasMore, array_filter(['q' => $search]), 'admin/patient.php') ?>
<?php else: ?>
    <?= empty_state(
        $search !== '' ? 'No patients match your search' : 'No patients yet',
        $search !== '' ? 'Check the spelling or clear the search.' : 'Patients appear here after they register.',
        $search !== '' ? button_link('Clear search', 'admin/patient.php', 'secondary') : '',
        'community-care'
    ) ?>
<?php endif; ?>
<?php
app_end();
