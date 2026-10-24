<?php
/*
 * General helpers: escaping, URLs, configuration, request input, flash
 * messages, remembered form input and date formatting.
 */

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * URL path of the application root, without a trailing slash ('' when the app
 * is served from the web root, '/uhai-bora-hms' in an XAMPP subfolder).
 */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $configured = getenv('APP_BASE_PATH');
    if ($configured !== false && $configured !== '') {
        $base = '/' . trim($configured, '/');
        $base = $base === '/' ? '' : $base;
        return $base;
    }

    $base = '';
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $scriptFile = realpath($_SERVER['SCRIPT_FILENAME'] ?? '');
    $root = realpath(APP_ROOT);
    if ($scriptName !== '' && $scriptFile !== false && $root !== false) {
        $relative = str_replace('\\', '/', substr($scriptFile, strlen($root)));
        if ($relative !== '' && substr($scriptName, -strlen($relative)) === $relative) {
            $base = rtrim(substr($scriptName, 0, -strlen($relative)), '/');
        }
    }
    return $base;
}

function url(string $path = '', array $query = []): string
{
    $url = base_path() . '/' . ltrim($path, '/');
    $query = array_filter($query, function ($value) {
        return $value !== null && $value !== '';
    });
    if ($query) {
        $url .= '?' . http_build_query($query);
    }
    return $url;
}

/** URL for a static file, with a cache-busting version based on its modification time. */
function asset(string $path): string
{
    $file = APP_ROOT . '/' . ltrim($path, '/');
    $version = is_file($file) ? (string) filemtime($file) : '1';
    return url($path) . '?v=' . $version;
}

/** Path of the current request relative to the app root, e.g. "patient/index.php". */
function request_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = base_path();
    if ($base !== '' && strpos($path, $base) === 0) {
        $path = substr($path, strlen($base));
    }
    return ltrim($path, '/');
}

function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    return getenv('APP_FORCE_HTTPS') === '1';
}

function redirect(string $path, array $query = [], int $status = 303): void
{
    header('Location: ' . url($path, $query), true, $status);
    exit;
}

/** Return to the referring page of this site, or to the given fallback path. */
function redirect_back(string $fallback = ''): void
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($referer !== '' && $host !== '' && parse_url($referer, PHP_URL_HOST) === parse_url('//' . $host, PHP_URL_HOST)) {
        $path = parse_url($referer, PHP_URL_PATH) ?: '/';
        $query = parse_url($referer, PHP_URL_QUERY);
        if (strpos($path, base_path() . '/') === 0 || base_path() === '') {
            header('Location: ' . $path . ($query ? '?' . $query : ''), true, 303);
            exit;
        }
    }
    $target = $fallback !== '' ? $fallback : request_path();
    redirect($target);
}

function config(string $key, $default = null)
{
    static $settings = null;
    if ($settings === null) {
        $settings = require APP_ROOT . '/config/site.php';
        $local = APP_ROOT . '/config/site.local.php';
        if (is_file($local)) {
            $settings = array_replace_recursive($settings, require $local);
        }
    }

    $value = $settings;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value === null || $value === '' ? $default : $value;
}

/** True once the operator's legal identity and a contact route have been configured. */
function operator_configured(): bool
{
    return config('operator.legal_name') !== null
        && (config('contact.email') !== null || config('contact.phone') !== null);
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
}

/* ---------- Request input ---------- */

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** Raw POST value for passwords: never trimmed, never remembered. */
function post_secret(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? $value : '';
}

function post_int(string $key): ?int
{
    $value = filter_var($_POST[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $value === false || $value === null ? null : (int) $value;
}

function query_string(string $key): string
{
    $value = $_GET[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function query_int(string $key): ?int
{
    $value = filter_var($_GET[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $value === false || $value === null ? null : (int) $value;
}

/** A YYYY-MM-DD date from the query string, or '' when absent or invalid. */
function query_date(string $key): string
{
    $value = query_string($key);
    return is_valid_date($value) ? $value : '';
}

/* ---------- Flash messages, remembered input and form errors ---------- */

function flash(string $type, string $message): void
{
    session_ensure();
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return is_array($messages) ? $messages : [];
}

/** Keep submitted values (never passwords or tokens) for the next request. */
function remember_input(array $data): void
{
    session_ensure();
    $kept = [];
    foreach ($data as $key => $value) {
        if (!is_string($key) || $key === '_token' || stripos($key, 'password') !== false) {
            continue;
        }
        if (is_string($value)) {
            $kept[$key] = mb_substr($value, 0, 2000);
        }
    }
    $_SESSION['_old'] = $kept;
}

function with_errors(array $errors): void
{
    session_ensure();
    $_SESSION['_errors'] = $errors;
}

/** Called once per request by the bootstrap: moves remembered data into this request. */
function load_request_state(): void
{
    $GLOBALS['_old_input'] = isset($_SESSION['_old']) && is_array($_SESSION['_old']) ? $_SESSION['_old'] : [];
    $GLOBALS['_form_errors'] = isset($_SESSION['_errors']) && is_array($_SESSION['_errors']) ? $_SESSION['_errors'] : [];
    unset($_SESSION['_old'], $_SESSION['_errors']);
}

function old(string $key, $default = ''): string
{
    $old = $GLOBALS['_old_input'] ?? [];
    return array_key_exists($key, $old) ? (string) $old[$key] : (string) $default;
}

function has_old_input(): bool
{
    return !empty($GLOBALS['_old_input']);
}

function form_errors(): array
{
    return $GLOBALS['_form_errors'] ?? [];
}

function field_error(string $name): string
{
    $errors = form_errors();
    return isset($errors[$name]) ? (string) $errors[$name] : '';
}

/** Redirect back to a form with its errors and the values the person entered. */
function fail_validation(array $errors, string $path, array $query = []): void
{
    remember_input($_POST);
    with_errors($errors);
    redirect($path, $query);
}

/* ---------- Dates, times and formatting (East Africa Time) ---------- */

function today(): string
{
    return date('Y-m-d');
}

function is_valid_date(string $value): bool
{
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date instanceof DateTime && $date->format('Y-m-d') === $value;
}

function is_valid_time(string $value): bool
{
    return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $value) === 1;
}

function format_date(string $date, string $format = 'D, j M Y'): string
{
    $value = DateTime::createFromFormat('!Y-m-d', substr($date, 0, 10));
    return $value ? $value->format($format) : $date;
}

function format_date_long(string $date): string
{
    return format_date($date, 'l, j F Y');
}

function format_time(string $time): string
{
    $value = DateTime::createFromFormat('H:i:s', strlen($time) === 5 ? $time . ':00' : $time);
    return $value ? $value->format('g:i a') : $time;
}

/** True when a session date and time is still in the future. */
function is_future_slot(string $date, string $time): bool
{
    $slot = DateTime::createFromFormat('Y-m-d H:i:s', $date . ' ' . (strlen($time) === 5 ? $time . ':00' : $time));
    return $slot instanceof DateTime && $slot > new DateTime();
}

function appointment_reference(int $id): string
{
    return 'UB-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
}

function first_name(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    return $parts && $parts[0] !== '' ? $parts[0] : $name;
}

function plural(int $count, string $singular, string $plural = ''): string
{
    $word = $count === 1 ? $singular : ($plural !== '' ? $plural : $singular . 's');
    return $count . ' ' . $word;
}

function age_on(string $dateOfBirth, string $onDate = ''): int
{
    $birth = new DateTime($dateOfBirth);
    $on = new DateTime($onDate !== '' ? $onDate : today());
    return (int) $birth->diff($on)->y;
}
