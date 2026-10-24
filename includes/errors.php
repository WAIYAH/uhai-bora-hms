<?php
/*
 * Error handling: problems are logged with a request reference and people see
 * a calm, branded page. Internal details (SQL, paths, stack traces) are never
 * shown unless APP_ENV=development and APP_DEBUG=1.
 */

function request_id(): string
{
    static $id = null;
    if ($id === null) {
        $id = strtoupper(bin2hex(random_bytes(4)));
    }
    return $id;
}

function log_message(string $level, string $message): void
{
    error_log(sprintf('[uhai-bora] [%s] [ref %s] %s', strtoupper($level), request_id(), $message));
}

function errors_register(): void
{
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');

    set_error_handler(function (int $severity, string $message, string $file = '', int $line = 0) {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        if ($severity === E_DEPRECATED || $severity === E_USER_DEPRECATED) {
            log_message('notice', "Deprecated: {$message} in {$file}:{$line}");
            return true;
        }
        throw new ErrorException($message, 0, $severity, $file, $line);
    });

    set_exception_handler('handle_uncaught_exception');

    register_shutdown_function(function () {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            log_message('critical', "{$error['message']} in {$error['file']}:{$error['line']}");
            render_error_page(500);
        }
    });
}

function handle_uncaught_exception(Throwable $exception): void
{
    if ($exception instanceof DatabaseUnavailable) {
        render_error_page(503);
        return;
    }
    log_message('error', get_class($exception) . ': ' . $exception->getMessage()
        . ' in ' . $exception->getFile() . ':' . $exception->getLine() . "\n" . $exception->getTraceAsString());
    render_error_page(500, $exception);
}

/** Show the branded 404 page and stop. */
function not_found(): void
{
    render_error_page(404);
    exit;
}

function render_error_page(int $status, ?Throwable $exception = null): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store');
        if ($status === 503) {
            header('Retry-After: 120');
        }
    }
    $GLOBALS['_error_status'] = $status;
    $GLOBALS['_error_exception'] = APP_DEBUG ? $exception : null;
    try {
        require APP_ROOT . '/includes/views/error-page.php';
    } catch (Throwable $nested) {
        log_message('critical', 'Error page failed: ' . $nested->getMessage());
        echo '<!DOCTYPE html><html lang="en"><meta charset="utf-8"><title>Something went wrong</title>'
            . '<h1>Something went wrong on our side</h1><p>Please try again in a few minutes. Reference: '
            . e(request_id()) . '</p></html>';
    }
}
