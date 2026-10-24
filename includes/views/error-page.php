<?php
/*
 * Branded error page for 404, 500 and 503 responses. It must not depend on the
 * database, so it can be shown when the database itself is unavailable.
 */
$status = (int) ($GLOBALS['_error_status'] ?? 500);
$exception = $GLOBALS['_error_exception'] ?? null;
$user = function_exists('current_user') && session_status() === PHP_SESSION_ACTIVE ? current_user() : null;

$copy = [
    404 => [
        'title' => 'Page not found',
        'heading' => 'We can’t find that page',
        'text' => 'The page may have moved, or the address may be incomplete. These links will help you get back on track.',
        'image' => 'discover',
    ],
    500 => [
        'title' => 'Something went wrong',
        'heading' => 'Something went wrong on our side',
        'text' => 'We couldn’t finish this request. If you were booking or changing details, check My bookings or Settings before trying again so nothing happens twice.',
        'image' => 'support',
    ],
    503 => [
        'title' => 'Temporarily unavailable',
        'heading' => 'Uhai Bora is temporarily unavailable',
        'text' => 'We can’t reach the booking records right now, so nothing new has been saved. Please try again in a few minutes.',
        'image' => 'support',
    ],
];
$page = $copy[$status] ?? $copy[500];

page_start(['title' => $page['title'], 'robots' => 'noindex', 'body_class' => 'page-error']);
?>
<section class="container error-page" aria-labelledby="error-heading">
    <div class="error-page__art">
        <img src="<?= e(asset('assets/illustrations/' . $page['image'] . '.svg')) ?>" alt="" width="320" height="260">
    </div>
    <div class="error-page__text">
        <p class="eyebrow">Error <?= e($status) ?></p>
        <h1 id="error-heading"><?= e($page['heading']) ?></h1>
        <p class="lead"><?= e($page['text']) ?></p>
        <?php if ($status === 404): ?>
            <ul class="link-list">
                <li><a href="<?= e(url('index.php')) ?>">Go to the homepage</a></li>
                <li><a href="<?= e(url('services.php')) ?>">Browse specialties</a></li>
                <li><a href="<?= e(url('book.php')) ?>">Book an appointment</a></li>
                <?php if ($user): ?>
                    <li><a href="<?= e(url(role_info($user['role'])['home'])) ?>">Go to my dashboard</a></li>
                <?php else: ?>
                    <li><a href="<?= e(url('login.php')) ?>">Log in to your account</a></li>
                <?php endif; ?>
            </ul>
            <p class="muted">If a link on our website brought you here, please tell us through the <a href="<?= e(url('contact.php')) ?>">Contact page</a>.</p>
        <?php else: ?>
            <div class="button-row">
                <a class="button button--primary" href="<?= e($_SERVER['REQUEST_METHOD'] === 'GET' ? ($_SERVER['REQUEST_URI'] ?? url('index.php')) : url('index.php')) ?>"><?= icon('refresh') ?>Try again</a>
                <a class="button button--secondary" href="<?= e(url('index.php')) ?>">Go to the homepage</a>
            </div>
            <p class="muted">Reference: <strong><?= e(request_id()) ?></strong>. Quote this if you contact us about the problem.</p>
        <?php endif; ?>
        <?php if ($exception instanceof Throwable): ?>
            <details class="debug-details">
                <summary>Developer details (shown only when APP_DEBUG=1)</summary>
                <pre><?= e(get_class($exception) . ': ' . $exception->getMessage() . "\n" . $exception->getFile() . ':' . $exception->getLine() . "\n\n" . $exception->getTraceAsString()) ?></pre>
            </details>
        <?php endif; ?>
    </div>
</section>
<?php
page_end();
