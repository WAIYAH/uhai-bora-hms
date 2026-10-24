<?php
/*
 * Sessions, sign-in, role-based access and the audit log.
 */

const LOGIN_MAX_ACCOUNT_ATTEMPTS = 5;
const LOGIN_MAX_IP_ATTEMPTS = 30;
const LOGIN_WINDOW_MINUTES = 15;
const PASSWORD_MIN_LENGTH = 10;
// Hash of a random value, verified when an account does not exist so that
// response times do not reveal which email addresses are registered.
const TIMING_DUMMY_HASH = '$2y$10$dg7H8/9GXyDKsBk.n.79IeCUgUGzIBEBDc1ojdNy3KVZ7wvYmwrq2';

function roles(): array
{
    return [
        'a' => [
            'label' => 'Administrator', 'home' => 'admin/index.php', 'settings' => 'admin/settings.php',
            'area' => 'admin', 'table' => 'admin', 'email' => 'aemail', 'password' => 'apassword',
        ],
        'd' => [
            'label' => 'Doctor', 'home' => 'doctor/index.php', 'settings' => 'doctor/settings.php',
            'area' => 'doctor', 'table' => 'doctor', 'email' => 'docemail', 'password' => 'docpassword',
        ],
        'p' => [
            'label' => 'Patient', 'home' => 'patient/index.php', 'settings' => 'patient/settings.php',
            'area' => 'patient', 'table' => 'patient', 'email' => 'pemail', 'password' => 'ppassword',
        ],
    ];
}

function role_info(string $role): array
{
    $roles = roles();
    if (!isset($roles[$role])) {
        throw new InvalidArgumentException('Unknown role.');
    }
    return $roles[$role];
}

/* ---------- Session lifecycle ---------- */

/**
 * Resume an existing session only. Visitors who are just reading public pages
 * never receive a cookie; session_ensure() starts one when it is needed
 * (logging in, submitting a form, showing a one-time message).
 */
function session_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    if (!empty($_COOKIE['uhaibora_session']) || is_post()) {
        session_ensure();
    }
}

function session_ensure(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    session_name('uhaibora_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => base_path() . '/',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function current_user(): ?array
{
    $user = $_SESSION['auth'] ?? null;
    return is_array($user) && isset($user['email'], $user['role']) ? $user : null;
}

/** Sign people out after a period of inactivity, which matters on shared phones and computers. */
function session_idle_check(): void
{
    if (!current_user()) {
        return;
    }
    $last = (int) ($_SESSION['auth']['last_activity'] ?? 0);
    if ($last > 0 && time() - $last > SESSION_IDLE_SECONDS) {
        end_user_session();
        flash('info', 'For your privacy, you were signed out after 30 minutes without activity. Please log in again.');
        return;
    }
    $_SESSION['auth']['last_activity'] = time();
}

function start_user_session(string $email, string $role): void
{
    session_ensure();
    session_regenerate_id(true);
    unset($_SESSION['_csrf']);
    $_SESSION['auth'] = ['email' => $email, 'role' => $role, 'name' => '', 'id' => null, 'last_activity' => time()];
    refresh_session_user();
}

/** Reload the signed-in person's name and id, e.g. after they edit their profile. */
function refresh_session_user(?string $email = null): void
{
    $user = current_user();
    if (!$user) {
        return;
    }
    $email = $email ?? $user['email'];
    $name = 'Administrator';
    $id = null;
    if ($user['role'] === 'd') {
        $row = db_one('SELECT docid, docname FROM doctor WHERE docemail = ?', [$email]);
        $name = $row['docname'] ?? '';
        $id = isset($row['docid']) ? (int) $row['docid'] : null;
    } elseif ($user['role'] === 'p') {
        $row = db_one('SELECT pid, pname FROM patient WHERE pemail = ?', [$email]);
        $name = $row['pname'] ?? '';
        $id = isset($row['pid']) ? (int) $row['pid'] : null;
    }
    $_SESSION['auth']['email'] = $email;
    $_SESSION['auth']['name'] = $name;
    $_SESSION['auth']['id'] = $id;
}

function end_user_session(): void
{
    unset($_SESSION['auth'], $_SESSION['_csrf']);
    session_regenerate_id(true);
}

/** Fully sign out and start a fresh, empty session (so a confirmation message can be shown). */
function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'secure' => $params['secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_destroy();
    session_ensure();
    session_regenerate_id(true);
}

/* ---------- Access control ---------- */

/** Allow only signed-in people with the given role; returns the current user. */
function require_role(string $role): array
{
    header('Cache-Control: no-store, private');
    $user = current_user();
    if (!$user) {
        $next = request_path();
        $query = $_SERVER['QUERY_STRING'] ?? '';
        if ($query !== '' && !is_post()) {
            $next .= '?' . $query;
        }
        flash('info', 'Please log in to continue.');
        redirect('login.php', ['next' => $next]);
    }
    if ($user['role'] !== $role) {
        flash('warning', 'That page is for ' . strtolower(role_info($role)['label']) . ' accounts, so we brought you to your own dashboard.');
        redirect(role_info($user['role'])['home']);
    }
    // The account may have been deleted or changed elsewhere (for example by an administrator).
    if (db_value('SELECT usertype FROM webuser WHERE email = ?', [$user['email']]) !== $role
        || ($role !== 'a' && empty($user['id']))) {
        end_user_session();
        flash('warning', 'We could not find your account. Please log in again.');
        redirect('login.php');
    }
    return $user;
}

/**
 * Validate a post-login destination. Only relative paths inside the person's
 * own area are accepted, which prevents open redirects.
 */
function safe_next(string $next, string $role): ?string
{
    if ($next === '' || preg_match('#^[a-z]+/[a-z0-9-]+\.php(\?[A-Za-z0-9_=&%.\-]*)?$#', ltrim($next, '/')) !== 1) {
        return null;
    }
    $next = ltrim($next, '/');
    $area = role_info($role)['area'] . '/';
    return strpos($next, $area) === 0 ? $next : null;
}

/* ---------- Sign-in ---------- */

/**
 * Check credentials with throttling. Returns ['ok' => true, 'role' => ...] or
 * ['ok' => false, 'locked_minutes' => int|null].
 */
function attempt_login(string $email, string $password): array
{
    $emailHash = hash('sha256', mb_strtolower($email));
    $ip = client_ip();

    db_exec('DELETE FROM login_attempts WHERE attempted_at < NOW() - INTERVAL 1 DAY');
    $window = LOGIN_WINDOW_MINUTES;
    $accountFailures = (int) db_value(
        "SELECT COUNT(*) FROM login_attempts WHERE email_hash = ? AND attempted_at > NOW() - INTERVAL {$window} MINUTE",
        [$emailHash]
    );
    $ipFailures = (int) db_value(
        "SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > NOW() - INTERVAL {$window} MINUTE",
        [$ip]
    );
    if ($accountFailures >= LOGIN_MAX_ACCOUNT_ATTEMPTS || $ipFailures >= LOGIN_MAX_IP_ATTEMPTS) {
        $oldest = (string) db_value(
            "SELECT MIN(attempted_at) FROM login_attempts
             WHERE (email_hash = ? OR ip = ?) AND attempted_at > NOW() - INTERVAL {$window} MINUTE",
            [$emailHash, $ip]
        );
        $unlockAt = strtotime($oldest) + $window * 60;
        return ['ok' => false, 'locked_minutes' => max(1, (int) ceil(($unlockAt - time()) / 60))];
    }

    $account = db_one('SELECT email, usertype FROM webuser WHERE email = ?', [$email]);
    $role = $account['usertype'] ?? '';
    $stored = '';
    if ($account && isset(roles()[$role])) {
        $info = role_info($role);
        $row = db_one(
            "SELECT {$info['password']} AS password FROM {$info['table']} WHERE {$info['email']} = ?",
            [$account['email']]
        );
        $stored = (string) ($row['password'] ?? '');
    }

    $valid = false;
    $upgrade = false;
    if ($stored !== '') {
        $hashInfo = password_get_info($stored);
        if (!empty($hashInfo['algo'])) {
            $valid = password_verify($password, $stored);
            $upgrade = $valid && password_needs_rehash($stored, PASSWORD_DEFAULT);
        } else {
            // Legacy plain-text value from the original template: accept once, then hash it.
            $valid = hash_equals($stored, $password);
            $upgrade = $valid;
        }
    } else {
        password_verify($password, TIMING_DUMMY_HASH);
    }

    if (!$valid) {
        db_exec('INSERT INTO login_attempts (email_hash, ip, attempted_at) VALUES (?, ?, NOW())', [$emailHash, $ip]);
        return ['ok' => false, 'locked_minutes' => null];
    }

    if ($upgrade) {
        set_account_password($role, $account['email'], $password);
        log_message('info', 'Upgraded a stored password to a secure hash (' . strtolower(role_info($role)['label']) . ' account).');
    }
    db_exec('DELETE FROM login_attempts WHERE email_hash = ?', [$emailHash]);
    start_user_session($account['email'], $role);
    audit('login');
    return ['ok' => true, 'role' => $role];
}

function set_account_password(string $role, string $email, string $password): void
{
    $info = role_info($role);
    db_exec(
        "UPDATE {$info['table']} SET {$info['password']} = ? WHERE {$info['email']} = ?",
        [password_hash($password, PASSWORD_DEFAULT), $email]
    );
}

/** Check a signed-in person's current password (for sensitive changes). */
function verify_current_password(array $user, string $password): bool
{
    $info = role_info($user['role']);
    $stored = (string) db_value(
        "SELECT {$info['password']} FROM {$info['table']} WHERE {$info['email']} = ?",
        [$user['email']]
    );
    if ($stored === '') {
        return false;
    }
    $hashInfo = password_get_info($stored);
    return !empty($hashInfo['algo']) ? password_verify($password, $stored) : hash_equals($stored, $password);
}

/** Returns an error message, or '' when the new password is acceptable. */
function new_password_error(string $password, string $confirmation, string $email = ''): string
{
    if ($password === '') {
        return 'Enter a password.';
    }
    if (mb_strlen($password) < PASSWORD_MIN_LENGTH) {
        return 'Use at least ' . PASSWORD_MIN_LENGTH . ' characters for your password.';
    }
    if (mb_strlen($password) > 200) {
        return 'Use 200 characters or fewer for your password.';
    }
    $common = ['password12', 'password123', '1234567890', '12345678910', 'qwertyuiop', 'uhaibora123', 'iloveyou12'];
    if (in_array(mb_strtolower($password), $common, true) || ($email !== '' && mb_strtolower($password) === mb_strtolower($email))) {
        return 'Choose a password that is harder to guess.';
    }
    if (!hash_equals($password, $confirmation)) {
        return 'The passwords do not match.';
    }
    return '';
}

/* ---------- Audit log ---------- */

/** Record who did what to which record (no health details are stored here). */
function audit(string $action, ?string $subjectType = null, ?int $subjectId = null): void
{
    $user = current_user();
    try {
        db_exec(
            'INSERT INTO audit_log (actor_email, actor_role, action, subject_type, subject_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())',
            [$user['email'] ?? null, $user['role'] ?? null, $action, $subjectType, $subjectId]
        );
    } catch (mysqli_sql_exception $exception) {
        // Never block the person's task because of logging, but keep a trace of the failure.
        log_message('error', 'Could not write audit entry "' . $action . '": ' . $exception->getMessage());
    }
}
