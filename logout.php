<?php
require __DIR__ . '/includes/bootstrap.php';

// Signing out changes state, so it requires a POST with the session token
// (checked by the bootstrap). A plain visit shows a confirmation button.
if (is_post()) {
    if (current_user()) {
        audit('logout');
    }
    logout_user();
    flash('success', 'You have logged out. Thank you for using Uhai Bora.');
    redirect('login.php');
}

if (!current_user()) {
    redirect('login.php');
}

page_start(['title' => 'Log out', 'robots' => 'noindex']);
?>
<div class="container auth">
    <section class="auth__card auth__card--narrow" aria-labelledby="logout-title">
        <h1 id="logout-title">Log out of Uhai Bora?</h1>
        <p class="muted">If you are using a shared phone or computer, logging out keeps your bookings private.</p>
        <form method="post" action="<?= e(url('logout.php')) ?>" class="button-row">
            <?= csrf_field() ?>
            <button type="submit" class="button button--primary"><?= icon('logout') ?>Log out</button>
            <a class="button button--secondary" href="<?= e(url(role_info(current_user()['role'])['home'])) ?>">Stay signed in</a>
        </form>
    </section>
</div>
<?php
page_end();
