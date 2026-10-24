<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_role('p');
$patientId = (int) $user['id'];

$patient = db_one('SELECT * FROM patient WHERE pid = ?', [$patientId]);
if (!$patient) {
    not_found();
}

if (is_post()) {
    $action = post_string('action');

    if ($action === 'profile') {
        $name = post_string('name');
        $email = post_string('email');
        $phone = preg_replace('/\s+/', ' ', post_string('phone'));
        $address = post_string('address');
        $dob = post_string('dob');
        $idNumber = post_string('id_number');
        $guardian = post_string('guardian_name');
        $emailChanged = mb_strtolower($email) !== mb_strtolower($patient['pemail']);

        $errors = array_filter([
            'name' => validate_full_name($name),
            'email' => validate_email_address($email),
            'phone' => validate_phone($phone),
            'address' => validate_text($address, 'your town or address', 2, 255),
            'dob' => validate_date_of_birth($dob),
            'id_number' => validate_id_number($idNumber),
        ]);
        if ($guardian !== '' && ($guardianError = validate_full_name($guardian, 'parent or guardian’s full name')) !== '') {
            $errors['guardian_name'] = $guardianError;
        }
        if (!isset($errors['dob']) && age_on($dob) < 18 && $guardian === '') {
            $errors['guardian_name'] = 'Enter the parent or guardian’s full name for a patient under 18.';
        }
        if ($emailChanged && !isset($errors['email'])) {
            if (email_taken($email, $patient['pemail'])) {
                $errors['email'] = 'Another account already uses this email address.';
            } elseif (!verify_current_password($user, post_secret('current_password_profile'))) {
                $errors['current_password_profile'] = 'Enter your current password to change your email address.';
            }
        }
        if ($errors) {
            fail_validation($errors, 'patient/settings.php', ['edit' => 1]);
        }

        db_transaction(function () use ($emailChanged, $email, $patient, $name, $phone, $address, $dob, $idNumber, $guardian, $patientId) {
            if ($emailChanged) {
                // patient.pemail follows automatically (ON UPDATE CASCADE).
                db_exec('UPDATE webuser SET email = ? WHERE email = ?', [$email, $patient['pemail']]);
            }
            db_exec(
                'UPDATE patient SET pname = ?, ptel = ?, paddress = ?, pdob = ?, pnic = ?, pguardian_name = ? WHERE pid = ?',
                [$name, $phone, $address, $dob, $idNumber, $guardian !== '' ? $guardian : null, $patientId]
            );
        });
        refresh_session_user($email);
        audit('patient.profile_updated', 'patient', $patientId);
        flash('success', 'Your details have been updated.');
        redirect('patient/settings.php');
    }

    if ($action === 'password') {
        $errors = [];
        if (!verify_current_password($user, post_secret('current_password'))) {
            $errors['current_password'] = 'Your current password is not correct.';
        }
        $passwordError = new_password_error(post_secret('password'), post_secret('password_confirmation'), $user['email']);
        if ($passwordError !== '') {
            $errors[$passwordError === 'The passwords do not match.' ? 'password_confirmation' : 'password'] = $passwordError;
        }
        if ($errors) {
            fail_validation($errors, 'patient/settings.php', ['section' => 'password']);
        }
        set_account_password('p', $user['email'], post_secret('password'));
        session_regenerate_id(true);
        audit('patient.password_changed', 'patient', $patientId);
        flash('success', 'Your password has been changed.');
        redirect('patient/settings.php');
    }

    if ($action === 'delete') {
        if (!verify_current_password($user, post_secret('delete_password'))) {
            fail_validation(['delete_password' => 'Enter your current password to delete your account.'], 'patient/settings.php', ['section' => 'delete']);
        }
        audit('patient.deleted', 'patient', $patientId);
        db_transaction(function () use ($user) {
            // Appointments and the patient record are removed by ON DELETE CASCADE.
            db_exec("DELETE FROM webuser WHERE email = ? AND usertype = 'p'", [$user['email']]);
            // Keep the audit trail but remove the email address that identified the person.
            db_exec('UPDATE audit_log SET actor_email = NULL WHERE actor_email = ?', [$user['email']]);
        });
        logout_user();
        flash('success', 'Your account and bookings have been deleted. Thank you for using Uhai Bora.');
        redirect('index.php');
    }

    redirect('patient/settings.php');
}

$editing = query_string('edit') === '1' || (form_errors() && query_string('section') === '');
$section = query_string('section');
$bookingCount = (int) db_value('SELECT COUNT(*) FROM appointment WHERE pid = ?', [$patientId]);

app_start([
    'title' => 'Settings',
    'nav' => 'settings',
    'lead' => 'Your details, password and information.',
]);
?>
<?= $editing || $section !== '' ? error_summary() : '' ?>
<div class="split">
    <div>
        <section class="panel" aria-labelledby="profile-title" id="profile">
            <div class="section-title">
                <h2 id="profile-title">Your details</h2>
                <?php if (!$editing): ?><a class="button button--secondary button--small" href="<?= e(url('patient/settings.php', ['edit' => 1])) ?>#profile"><?= icon('edit') ?>Edit details</a><?php endif; ?>
            </div>
            <?php if (!$editing): ?>
                <dl class="details-list">
                    <div><dt>Full name</dt><dd><?= e($patient['pname']) ?></dd></div>
                    <div><dt>Email address</dt><dd><?= e($patient['pemail']) ?></dd></div>
                    <div><dt>Phone number</dt><dd><?= e($patient['ptel']) ?></dd></div>
                    <div><dt>Town or address</dt><dd><?= e($patient['paddress']) ?></dd></div>
                    <div><dt>Date of birth</dt><dd><?= e(format_date_long($patient['pdob'])) ?></dd></div>
                    <div><dt>ID or passport number</dt><dd><?= e($patient['pnic']) ?></dd></div>
                    <?php if (!empty($patient['pguardian_name'])): ?><div><dt>Parent or guardian</dt><dd><?= e($patient['pguardian_name']) ?></dd></div><?php endif; ?>
                </dl>
            <?php else: ?>
                <form class="form" method="post" action="<?= e(url('patient/settings.php')) ?>" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="profile">
                    <div class="form-grid form-grid--2">
                        <?= input_field(['name' => 'name', 'label' => 'Full name', 'autocomplete' => 'name', 'default' => $patient['pname'], 'class' => 'field--wide', 'attrs' => ['maxlength' => '120']]) ?>
                        <?= input_field(['name' => 'email', 'label' => 'Email address', 'type' => 'email', 'autocomplete' => 'email', 'default' => $patient['pemail'], 'class' => 'field--wide', 'attrs' => ['maxlength' => '255']]) ?>
                        <?= input_field(['name' => 'phone', 'label' => 'Phone number', 'type' => 'tel', 'autocomplete' => 'tel', 'default' => $patient['ptel'], 'attrs' => ['maxlength' => '32']]) ?>
                        <?= input_field(['name' => 'address', 'label' => 'Town or address', 'autocomplete' => 'street-address', 'default' => $patient['paddress'], 'attrs' => ['maxlength' => '255']]) ?>
                        <?= input_field(['name' => 'dob', 'label' => 'Date of birth', 'type' => 'date', 'autocomplete' => 'bday', 'default' => $patient['pdob'], 'attrs' => ['max' => today()]]) ?>
                        <?= input_field(['name' => 'id_number', 'label' => 'ID or passport number', 'autocomplete' => 'off', 'default' => $patient['pnic'], 'attrs' => ['maxlength' => '32']]) ?>
                        <?= input_field(['name' => 'guardian_name', 'label' => 'Parent or guardian’s full name', 'required' => false, 'default' => $patient['pguardian_name'] ?? '', 'hint' => 'Needed only for patients under 18.', 'class' => 'field--wide', 'attrs' => ['maxlength' => '120']]) ?>
                        <?= input_field(['name' => 'current_password_profile', 'label' => 'Current password', 'type' => 'password', 'required' => false, 'autocomplete' => 'current-password', 'hint' => 'Only needed if you change your email address.', 'class' => 'field--wide']) ?>
                    </div>
                    <div class="form-actions">
                        <button class="button button--primary" type="submit">Save details</button>
                        <a class="button button--ghost" href="<?= e(url('patient/settings.php')) ?>">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </section>

        <section class="panel" aria-labelledby="password-title" id="password">
            <h2 id="password-title">Change password</h2>
            <form class="form" method="post" action="<?= e(url('patient/settings.php')) ?>#password" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="password">
                <?= input_field(['name' => 'current_password', 'label' => 'Current password', 'type' => 'password', 'autocomplete' => 'current-password']) ?>
                <?= input_field(['name' => 'password', 'label' => 'New password', 'type' => 'password', 'autocomplete' => 'new-password', 'hint' => 'Use at least ' . PASSWORD_MIN_LENGTH . ' characters.']) ?>
                <?= input_field(['name' => 'password_confirmation', 'label' => 'Confirm new password', 'type' => 'password', 'autocomplete' => 'new-password', 'attrs' => ['data-password-confirm' => 'field-password']]) ?>
                <div class="form-actions"><button class="button button--primary" type="submit">Change password</button></div>
            </form>
        </section>
    </div>

    <div>
        <section class="panel" aria-labelledby="data-title" id="data">
            <h2 id="data-title">Your information</h2>
            <p>Download a copy of your account details and <?= e(plural($bookingCount, 'booking')) ?> in a machine-readable file (JSON).</p>
            <p><a class="button button--secondary" href="<?= e(url('patient/export.php')) ?>"><?= icon('download') ?>Download my information</a></p>
            <p class="muted"><a href="<?= e(url('your-data.php')) ?>">Your data rights</a> · <a href="<?= e(url('privacy.php')) ?>">Privacy notice</a></p>
            <?php if (!empty($patient['pconsented_at'])): ?>
                <p class="muted"><small>You agreed to the Privacy notice and Terms (version <?= e($patient['pconsent_version']) ?>) on <?= e(format_date(substr($patient['pconsented_at'], 0, 10))) ?>.</small></p>
            <?php endif; ?>
        </section>

        <section class="panel panel--danger" aria-labelledby="delete-title" id="delete">
            <h2 id="delete-title">Delete account</h2>
            <p>This permanently removes your account and all of your bookings from the live database. It can’t be undone.</p>
            <?= confirm_action([
                'label' => 'Delete my account',
                'question' => 'Delete your account and all bookings?',
                'detail' => 'Enter your password to confirm. Upcoming bookings will be cancelled and you will be logged out.',
                'confirm' => 'Permanently delete my account',
                'action' => 'patient/settings.php',
                'fields' => ['action' => 'delete'],
                'extra' => input_field(['name' => 'delete_password', 'label' => 'Current password', 'type' => 'password', 'autocomplete' => 'current-password']),
                'open' => field_error('delete_password') !== '',
            ]) ?>
        </section>
    </div>
</div>
<?php
app_end();
