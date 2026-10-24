<?php
/*
 * Shared start-up for every PHP page:
 *     require __DIR__ . '/includes/bootstrap.php';            (pages in the app root)
 *     require dirname(__DIR__) . '/includes/bootstrap.php';   (pages in admin/, doctor/, patient/)
 *
 * Works on PHP 7.3 and newer (no 7.4+/8.x-only syntax is used).
 */

define('APP_ROOT', dirname(__DIR__));
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', APP_ENV === 'development' && getenv('APP_DEBUG') === '1');
define('SESSION_IDLE_SECONDS', 30 * 60);

date_default_timezone_set('Africa/Nairobi');

require APP_ROOT . '/includes/errors.php';
require APP_ROOT . '/includes/helpers.php';
require APP_ROOT . '/includes/db.php';
require APP_ROOT . '/includes/schema.php';
require APP_ROOT . '/includes/auth.php';
require APP_ROOT . '/includes/view.php';
require APP_ROOT . '/includes/forms.php';
require APP_ROOT . '/includes/appointments.php';
require APP_ROOT . '/includes/clinic.php';

errors_register();
ob_start();
session_boot();
send_security_headers();
load_request_state();
csrf_guard();
session_idle_check();

function send_security_headers(): void
{
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; "
        . "font-src 'self'; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'");
    header('Cache-Control: no-cache, private');
    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

function csrf_token(): string
{
    session_ensure();
    if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

/** Every POST must carry this session's token; otherwise nothing is changed. */
function csrf_guard(): void
{
    if (!is_post()) {
        return;
    }
    $token = $_POST['_token'] ?? '';
    if (is_string($token) && $token !== '' && hash_equals(csrf_token(), $token)) {
        return;
    }
    remember_input($_POST);
    flash('error', 'Your session expired before the form was sent, so nothing was saved. Please check the details and try again.');
    redirect_back();
}
