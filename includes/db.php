<?php
/*
 * Database access. Every query goes through a prepared statement; mysqli is
 * configured to throw exceptions so failures are never silently ignored.
 */

class DatabaseUnavailable extends RuntimeException
{
}

function db(): mysqli
{
    static $connection = null;
    if ($connection instanceof mysqli) {
        return $connection;
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $connection = new mysqli(
            getenv('DB_HOST') ?: 'localhost',
            getenv('DB_USER') ?: 'root',
            getenv('DB_PASSWORD') ?: '',
            getenv('DB_NAME') ?: 'uhaibora',
            (int) (getenv('DB_PORT') ?: 3306)
        );
        $connection->set_charset('utf8mb4');
        // Keep CURDATE()/NOW() in East Africa Time, matching PHP.
        $connection->query("SET time_zone = '+03:00'");
    } catch (mysqli_sql_exception $exception) {
        $connection = null;
        log_message('error', 'Database connection failed: ' . $exception->getMessage());
        throw new DatabaseUnavailable('The database is not available.', 0, $exception);
    }

    ensure_schema($connection);
    return $connection;
}

function db_run(string $sql, array $params = []): mysqli_stmt
{
    $statement = db()->prepare($sql);
    if ($params) {
        $types = '';
        $values = [];
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $param;
        }
        $statement->bind_param($types, ...$values);
    }
    $statement->execute();
    return $statement;
}

function db_all(string $sql, array $params = []): array
{
    $result = db_run($sql, $params)->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function db_one(string $sql, array $params = []): ?array
{
    $result = db_run($sql, $params)->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    return $row ?: null;
}

function db_value(string $sql, array $params = [])
{
    $row = db_one($sql, $params);
    return $row ? reset($row) : null;
}

/** Run an INSERT/UPDATE/DELETE and return the number of affected rows. */
function db_exec(string $sql, array $params = []): int
{
    return db_run($sql, $params)->affected_rows;
}

function db_insert_id(): int
{
    return (int) db()->insert_id;
}

/** Run $callback inside a transaction; roll back and rethrow on any failure. */
function db_transaction(callable $callback)
{
    $connection = db();
    $connection->begin_transaction();
    try {
        $result = $callback();
        $connection->commit();
        return $result;
    } catch (Throwable $exception) {
        $connection->rollback();
        throw $exception;
    }
}

/**
 * For optional page sections on public pages: run $callback and return
 * [result, failed]. A database problem is logged and reported to the page
 * (which shows a visible "temporarily unavailable" message) instead of
 * taking the whole page down.
 */
function db_optional(callable $callback, $fallback): array
{
    try {
        return [$callback(), false];
    } catch (DatabaseUnavailable $exception) {
        return [$fallback, true];
    } catch (mysqli_sql_exception $exception) {
        log_message('error', 'Optional query failed: ' . $exception->getMessage());
        return [$fallback, true];
    }
}

/** Escape LIKE wildcards in user search text. */
function like_pattern(string $text): string
{
    return '%' . addcslashes($text, '%_\\') . '%';
}
