<?php
require __DIR__ . '/includes/bootstrap.php';

$next = query_string('next');
if (is_post()) {
    $next = post_string('next');
}

$user = current_user();
if ($user) {
    $destination = safe_next($next, $user['role']) ?? role_info($user['role'])['home'];
    header('Location: ' . url($destination), true, 303);
    exit;
}

if (is_post()) {
    $email = post_string('email');
    $password = post_secret('password');
    $errors = [];
    if ($email === '') {
        $errors['email'] = 'Enter your email address.';
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Enter an email address in the correct format, like name@example.com.';
    }
    if ($password === '') {
        $errors['password'] = 'Enter your password.';
    }

    if (!$errors) {
        $result = attempt_login($email, $password);
        if ($result['ok']) {
            $role = $result['role'];
            $destination = safe_next($next, $role) ?? role_info($role)['home'];
            header('Location: ' . url($destination), true, 303);
            exit;
        }
        if ($result['locked_minutes'] !== null) {
            $errors['email'] = 'Too many unsuccessful attempts. For your security, please wait '
                . plural((int) $result['locked_minutes'], 'minute') . ' before trying again.';
        } else {
            $errors['email'] = 'The email address or password is not correct. Check them and try again.';
        }
    }
    fail_validation($errors, 'login.php', ['next' => $next]);
}

$registerQuery = $next !== '' ? ['next' => $next] : [];

page_start([
    'title' => 'Login',
    'description' => 'Log in to Uhai Bora to book appointments and manage your bookings.',
    'body_class' => 'page-auth',
]);
?>
<div class="container auth">
    <div class="auth__grid">
        <section class="auth__card" aria-labelledby="login-title">
            <p class="eyebrow">Welcome back</p>
            <h1 id="login-title">Log in to your account</h1>
            <p class="muted">Patients, doctors and clinic administrators all log in here.</p>
            <?= error_summary() ?>
            <form class="form" method="post" action="<?= e(url('login.php')) ?>" novalidate data-submit-once>
                <?= csrf_field() ?>
                <input type="hidden" name="next" value="<?= e($next) ?>">
                <?= input_field([
                    'name' => 'email', 'label' => 'Email address', 'type' => 'email', 'autocomplete' => 'username',
                    'attrs' => ['inputmode' => 'email', 'spellcheck' => 'false', 'autocapitalize' => 'none'],
                ]) ?>
                <?= input_field([
                    'name' => 'password', 'label' => 'Password', 'type' => 'password', 'autocomplete' => 'current-password',
                ]) ?>
                <div class="button-row">
                    <button class="button button--primary" type="submit" data-loading-label="Logging in…">Log in</button>
                    <a href="<?= e(url('contact.php')) ?>#account-help">Forgotten your password?</a>
                </div>
            </form>
            <p class="auth__switch">New to Uhai Bora? <a href="<?= e(url('signup.php', $registerQuery)) ?>">Register for a patient account</a></p>
        </section>
        <aside class="auth__aside" aria-label="About your account">
            <figure class="auth__photo">
                <?= picture('hands-reassurance', 'Two people holding hands in reassurance.', [640, 960, 1400], 960, 960, 640, '(min-width: 56em) 40vw, 100vw') ?>
                <figcaption>Illustrative photo</figcaption>
            </figure>
            <ul class="check-list">
                <li><?= icon('calendar') ?><span>See your upcoming appointments and their status.</span></li>
                <li><?= icon('refresh') ?><span>Reschedule or cancel when your plans change.</span></li>
                <li><?= icon('lock') ?><span>For your privacy you’ll be signed out after 30 minutes without activity.</span></li>
            </ul>
        </aside>
    </div>
</div>
<?php
page_end();
