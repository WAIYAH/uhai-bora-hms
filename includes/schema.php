<?php
/*
 * Additive schema upgrades. A fresh install from SQL_Database_uhaibora.sql is
 * already at the current version. Older databases are upgraded automatically on
 * first use: only new tables and nullable columns are added, nothing is dropped
 * or rewritten. Run `php database/migrate.php` to apply upgrades from the CLI.
 */

const SCHEMA_VERSION = 1;

function ensure_schema(mysqli $db): void
{
    try {
        $row = $db->query("SELECT meta_value FROM app_meta WHERE meta_key = 'schema_version'")->fetch_assoc();
        if ($row && (int) $row['meta_value'] >= SCHEMA_VERSION) {
            return;
        }
    } catch (mysqli_sql_exception $exception) {
        // 1146: table does not exist yet (database created before app_meta).
        if ((int) $exception->getCode() !== 1146) {
            throw $exception;
        }
    }
    migrate_schema($db);
}

function migrate_schema(mysqli $db): void
{
    $core = $db->query(
        "SELECT COUNT(*) AS total FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME IN ('webuser', 'admin', 'doctor', 'patient', 'specialties', 'schedule', 'appointment')"
    )->fetch_assoc();
    if ((int) $core['total'] < 7) {
        throw new RuntimeException('Uhai Bora tables are missing. Import SQL_Database_uhaibora.sql to install the database.');
    }

    $db->query("SELECT GET_LOCK('uhaibora_schema', 15)");
    try {
        $db->query(
            "CREATE TABLE IF NOT EXISTS app_meta (
                meta_key VARCHAR(64) NOT NULL,
                meta_value VARCHAR(255) NOT NULL,
                PRIMARY KEY (meta_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        add_column_if_missing($db, 'patient', 'pguardian_name', 'VARCHAR(255) NULL DEFAULT NULL');
        add_column_if_missing($db, 'patient', 'pconsent_version', 'VARCHAR(20) NULL DEFAULT NULL');
        add_column_if_missing($db, 'patient', 'pconsented_at', 'DATETIME NULL DEFAULT NULL');
        add_column_if_missing($db, 'patient', 'pcreated_at', 'DATETIME NULL DEFAULT NULL');
        add_column_if_missing($db, 'appointment', 'created_at', 'DATETIME NULL DEFAULT NULL');

        $db->query(
            "CREATE TABLE IF NOT EXISTS login_attempts (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                email_hash CHAR(64) NOT NULL,
                ip VARCHAR(45) NOT NULL,
                attempted_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY idx_login_attempts_email (email_hash, attempted_at),
                KEY idx_login_attempts_ip (ip, attempted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $db->query(
            "CREATE TABLE IF NOT EXISTS audit_log (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                actor_email VARCHAR(255) NULL,
                actor_role CHAR(1) NULL,
                action VARCHAR(64) NOT NULL,
                subject_type VARCHAR(32) NULL,
                subject_id INT UNSIGNED NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY idx_audit_created (created_at),
                KEY idx_audit_subject (subject_type, subject_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $db->query(
            "INSERT INTO app_meta (meta_key, meta_value) VALUES ('schema_version', '" . (int) SCHEMA_VERSION . "')
             ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)"
        );
        log_message('info', 'Database schema upgraded to version ' . SCHEMA_VERSION . '.');
    } finally {
        $db->query("DO RELEASE_LOCK('uhaibora_schema')");
    }
}

function add_column_if_missing(mysqli $db, string $table, string $column, string $definition): void
{
    $check = $db->prepare(
        'SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $check->bind_param('ss', $table, $column);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        // Table and column names are fixed strings from this file, never user input.
        $db->query("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }
}
