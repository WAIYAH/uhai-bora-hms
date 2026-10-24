<?php
/*
 * Apply Uhai Bora's additive schema upgrades from the command line:
 *     php database/migrate.php
 * (Docker: docker compose exec web php database/migrate.php)
 *
 * The app also applies these upgrades automatically the first time it connects
 * to an older database. Nothing is dropped or rewritten.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('APP_ROOT', dirname(__DIR__));
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', false);
date_default_timezone_set('Africa/Nairobi');

require APP_ROOT . '/includes/errors.php';
require APP_ROOT . '/includes/helpers.php';
require APP_ROOT . '/includes/db.php';
require APP_ROOT . '/includes/schema.php';

try {
    db();
    echo 'Uhai Bora database schema is at version ' . SCHEMA_VERSION . '.' . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'Migration failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
