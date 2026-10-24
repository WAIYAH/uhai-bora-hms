<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_role('d');
$doctorId = (int) $user['id'];

$doctor = db_one('SELECT d.*, sp.sname FROM doctor d LEFT JOIN specialties sp ON sp.id = d.specialties WHERE d.docid = ?', [$doctorId]);
if (!$doctor) {
    not_found();
}

if (is_post()) {
    $action = post_string('action');

    if ($action === 'profile') {
        $name = post_string('name');
        $email = post_string('email');
        $phone = preg_replace('/\s+/', ' ', post_string('phone'));
        $idNumber = post_string('id_number');
        $specialty = post_int('specialty');
        $emailChanged = mb_strtolower($email) !== mb_strtolower($doctor['docemail']);

        $errors = array_filter([
            'name' => validate_full_name($name),
            'email' => validate_email_address($email),
            'phone' => validate_phone($phone),
            'id_number' => validate_id_number($idNumber),
        ]);
        if (!$specialty || !isset(specialties()[$specialty])) {
            $errors['specialty'] = 'Choose your specialty.';
        }
        if ($emailChanged && !isset($errors['email'])) {
            if (email_taken($email, $doctor['docemail'])) {
                $errors['email'] = 'Another account already uses this email address.';
            } elseif (!verify_current_password($user, post_secret('current_password_profile'))) {
                $errors['current_password_profile'] = 'Enter your current password to change your email address.';
            }
        }
        if ($errors) {
            fail_validation($errors, 'doctor/settings.php', ['edit' => 1]);
        }
        db_transaction(function () use ($emailChanged, $email, $doctor, $name, $phone, $idNumber, $specialty, $doctorId) {
            if ($emailChanged) {
                db_exec('UPDATE webuser SET email = ? WHERE email = ?', [$email, $doctor['docemail']]);
            }
            db_exec('UPDATE doctor SET docname = ?, doctel = ?, docnic = ?, specialties = ? WHERE docid = ?',
                [$name, $phone, $idNumber, $specialty, $doctorId]);
        });
        refresh_session_user($email);
        audit('doctor.profile_updated', 'doctor', $doctorId);
        flash('success', 'Your details have been updated.');
        redirect('doctor/settings.php');
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
            fail_validation($errors, 'doctor/settings.php', ['section' => 'password']);
        }
        set_account_password('d', $user['email'], post_secret('password'));
        session_regenerate_id(true);
        audit('doctor.password_changed', 'doctor', $doctorId);
        flash('success', 'Your password has been changed.');
        redirect('doctor/settings.php');
    }

    if ($action === 'delete') {
        if (!verify_current_password($user, post_secret('delete_password'))) {
            fail_validation(['delete_password' => 'Enter your current password to delete your account.'], 'doctor/settings.php', ['section' => 'delete']);
        }
        audit('doctor.deleted', 'doctor', $doctorId);
        db_exec("DELETE FROM webuser WHERE email = ? AND usertype = 'd'", [$user['email']]);
        logout_user();
        flash('success', 'Your doctor account, sessions and their bookings have been deleted.');
        redirect('index.php');
    }

    redirect('doctor/settings.php');
}

$editing = query_string('edit') === '1';
$sessionCount = (int) db_value('SELECT COUNT(*) FROM schedule WHERE docid = ?', [$doctorId]);

app_start([
    'title' => 'Settings',
    'nav' => 'settings',
    'lead' => 'Your profile as patients see it, and your password.',
]);
?>
<?= error_summary() ?>
<div class="split">
    <div>
        <section class="panel" aria-labelledby="profile-title" id="profile">
            <div class="section-title">
                <h2 id="profile-title">Your profile</h2>
                <?php if (!$editing): ?><a class="button button--secondary button--small" href="<?= e(url('doctor/settings.php', ['edit' => 1])) ?>#profile"><?= icon('edit') ?>Edit profile</a><?php endif; ?>
            </div>
            <?php if (!$editing): ?>
                <dl class="details-list">
                    <div><dt>Name shown to patients</dt><dd><?= e($doctor['docname']) ?></dd></div>
                    <div><dt>Specialty</dt><dd><?= e($doctor['sname'] ?? 'Not set') ?></dd></div>
                    <div><dt>Email address</dt><dd><?= e($doctor['docemail']) ?></dd></div>
                    <div><dt>Phone number</dt><dd><?= e($doctor['doctel']) ?></dd></div>
                    <div><dt>ID or passport number</dt><dd><?= e($doctor['docnic']) ?></dd></div>
                </dl>
                <p class="muted"><small>Patients see your name, specialty and sessions only.</small></p>
            <?php else: ?>
                <form class="form" method="post" action="<?= e(url('doctor/settings.php')) ?>" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="profile">
                    <?= input_field(['name' => 'name', 'label' => 'Name shown to patients', 'autocomplete' => 'name', 'default' => $doctor['docname'], 'hint' => 'For example “Dr. Amina Otieno”.', 'attrs' => ['maxlength' => '120']]) ?>
                    <?= select_field(['name' => 'specialty', 'label' => 'Specialty', 'options' => specialties(), 'default' => (string) $doctor['specialties']]) ?>
                    <div class="form-grid form-grid--2">
                        <?= input_field(['name' => 'email', 'label' => 'Email address', 'type' => 'email', 'autocomplete' => 'email', 'default' => $doctor['docemail'], 'attrs' => ['maxlength' => '255']]) ?>
                        <?= input_field(['name' => 'phone', 'label' => 'Phone number', 'type' => 'tel', 'autocomplete' => 'tel', 'default' => $doctor['doctel'], 'attrs' => ['maxlength' => '32']]) ?>
                    </div>
                    <?= input_field(['name' => 'id_number', 'label' => 'ID or passport number', 'autocomplete' => 'off', 'default' => $doctor['docnic'], 'attrs' => ['maxlength' => '32']]) ?>
                    <?= input_field(['name' => 'current_password_profile', 'label' => 'Current password', 'type' => 'password', 'required' => false, 'autocomplete' => 'current-password', 'hint' => 'Only needed if you change your email address.']) ?>
                    <div class="form-actions">
                        <button class="button button--primary" type="submit">Save profile</button>
                        <a class="button button--ghost" href="<?= e(url('doctor/settings.php')) ?>">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </section>

        <section class="panel" aria-labelledby="password-title" id="password">
            <h2 id="password-title">Change password</h2>
            <form class="form" method="post" action="<?= e(url('doctor/settings.php')) ?>#password" novalidate>
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
        <section class="panel panel--danger" aria-labelledby="delete-title" id="delete">
            <h2 id="delete-title">Delete account</h2>
            <p>This permanently deletes your doctor account, your <?= e(plural($sessionCount, 'session')) ?> and all bookings in them. Patients will no longer see those bookings. It can’t be undone.</p>
            <?= confirm_action([
                'label' => 'Delete my account',
                'question' => 'Delete your account, sessions and their bookings?',
                'detail' => 'Enter your password to confirm. Consider telling booked patients first.',
                'confirm' => 'Permanently delete my account',
                'action' => 'doctor/settings.php',
                'fields' => ['action' => 'delete'],
                'extra' => input_field(['name' => 'delete_password', 'label' => 'Current password', 'type' => 'password', 'autocomplete' => 'current-password']),
                'open' => field_error('delete_password') !== '',
            ]) ?>
        </section>
    </div>
</div>
<?php
app_end();
