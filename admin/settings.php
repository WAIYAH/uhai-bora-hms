<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_role('a');

if (is_post()) {
    $errors = [];
    if (!verify_current_password($user, post_secret('current_password'))) {
        $errors['current_password'] = 'Your current password is not correct.';
    }
    $passwordError = new_password_error(post_secret('password'), post_secret('password_confirmation'), $user['email']);
    if ($passwordError !== '') {
        $errors[$passwordError === 'The passwords do not match.' ? 'password_confirmation' : 'password'] = $passwordError;
    }
    if ($errors) {
        fail_validation($errors, 'admin/settings.php');
    }
    set_account_password('a', $user['email'], post_secret('password'));
    session_regenerate_id(true);
    audit('admin.password_changed');
    flash('success', 'Your password has been changed.');
    redirect('admin/settings.php');
}

$recent = db_all('SELECT actor_role, action, subject_type, subject_id, created_at FROM audit_log ORDER BY id DESC LIMIT 25');
$roleLabels = ['a' => 'Administrator', 'd' => 'Doctor', 'p' => 'Patient'];

app_start([
    'title' => 'Settings',
    'heading' => 'Administrator settings',
    'lead' => 'Signed in as ' . $user['email'] . '.',
]);
?>
<?= error_summary() ?>
<div class="split">
    <section class="panel" aria-labelledby="password-title">
        <h2 id="password-title">Change password</h2>
        <form class="form" method="post" action="<?= e(url('admin/settings.php')) ?>" novalidate>
            <?= csrf_field() ?>
            <?= input_field(['name' => 'current_password', 'label' => 'Current password', 'type' => 'password', 'autocomplete' => 'current-password']) ?>
            <?= input_field(['name' => 'password', 'label' => 'New password', 'type' => 'password', 'autocomplete' => 'new-password', 'hint' => 'Use at least ' . PASSWORD_MIN_LENGTH . ' characters.']) ?>
            <?= input_field(['name' => 'password_confirmation', 'label' => 'Confirm new password', 'type' => 'password', 'autocomplete' => 'new-password', 'attrs' => ['data-password-confirm' => 'field-password']]) ?>
            <div class="form-actions"><button class="button button--primary" type="submit">Change password</button></div>
        </form>
    </section>
    <section class="panel" aria-labelledby="audit-title">
        <h2 id="audit-title">Recent activity</h2>
        <p class="muted">The latest 25 entries from the audit log (who did what, without health details).</p>
        <?php if ($recent): ?>
            <ul class="record-list">
                <?php foreach ($recent as $entry): ?>
                    <li>
                        <strong><?= e($entry['action']) ?></strong>
                        <span class="muted"> · <?= e($roleLabels[$entry['actor_role']] ?? 'System') ?><?= $entry['subject_type'] ? ' · ' . e($entry['subject_type']) . ' #' . (int) $entry['subject_id'] : '' ?> · <?= e(format_date(substr($entry['created_at'], 0, 10))) ?> <?= e(format_time(substr($entry['created_at'], 11, 8))) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No activity has been recorded yet.</p>
        <?php endif; ?>
    </section>
</div>
<?php
app_end();
