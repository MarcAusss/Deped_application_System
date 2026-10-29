<?php

if (!defined('PORTAL_BOOTED')) {
    http_response_code(404);
    exit;
}

/**
 * PDO connection + a couple of tiny helpers, built once per request.
 * No ORM by design (Phase 1 of the pure-PHP rewrite) — every query in this
 * portal is a hand-written prepared statement.
 */
function portal_pdo(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $host = portal_env('DB_HOST', '127.0.0.1');
    $port = portal_env('DB_PORT', '3306');
    $database = portal_env('DB_DATABASE', 'deped_application_system');
    $username = portal_env('DB_USERNAME', 'root');
    $password = portal_env('DB_PASSWORD', '');

    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    portal_ensure_schema($pdo, $database);

    return $pdo;
}

/**
 * Self-healing schema check: a database restored from a backup taken before
 * a given migration is missing columns/tables the code now depends on, which
 * used to fatal with a confusing "Unknown column"/"doesn't exist" error on
 * every device that hadn't manually re-run the .sql files in database/. Each
 * migration below applies automatically on first request if still missing,
 * then writes its own marker file so later requests skip it entirely.
 *
 * Deliberately silent on failure (e.g. a DB user without ALTER privileges)
 * — the original error still surfaces normally wherever the query actually
 * runs in that case, so there's no behavior regression, only a fix when it's
 * able to apply. To add a future migration here: add a new marker filename
 * and its own check+apply block — never reuse or edit an existing one.
 */
function portal_ensure_schema(PDO $pdo, string $database): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $storageDir = (defined('PORTAL_ROOT') ? PORTAL_ROOT : dirname(__DIR__)).'/storage';

    portal_apply_migration_once($pdo, $database, $storageDir.'/.schema_migrated_2026_10_26', function (PDO $pdo, string $database) {
        $existing = $pdo->prepare(
            "SELECT table_name, column_name FROM information_schema.columns
             WHERE table_schema = ? AND table_name IN ('applications', 'job_positions')
               AND column_name IN ('jp_number', 'posting_date', 'evaluated_by')"
        );
        $existing->execute([$database]);
        $have = [];
        foreach ($existing->fetchAll(PDO::FETCH_NUM) as [$table, $column]) {
            $have[$table][$column] = true;
        }

        if (empty($have['applications']['jp_number'])) {
            $pdo->exec("ALTER TABLE `applications`
                ADD COLUMN `jp_number` VARCHAR(255) NULL DEFAULT NULL AFTER `job_position_id`,
                ADD COLUMN `posting_date` DATE NULL DEFAULT NULL AFTER `jp_number`");
            $pdo->exec("UPDATE `applications` a
                JOIN `job_positions` jp ON jp.id = a.job_position_id
                SET a.jp_number = jp.jp_number, a.posting_date = jp.posted_at");
        }

        if (empty($have['job_positions']['evaluated_by'])) {
            $pdo->exec("ALTER TABLE `job_positions`
                ADD COLUMN `evaluated_by` ENUM('evaluator','admin') NOT NULL DEFAULT 'evaluator' AFTER `csc_publication_paths`");
        }
    });

    portal_apply_migration_once($pdo, $database, $storageDir.'/.schema_migrated_2026_10_29_v2', function (PDO $pdo, string $database) {
        $hasTable = $pdo->prepare(
            "SELECT 1 FROM information_schema.tables WHERE table_schema = ? AND table_name = 'job_position_assigned_evaluators'"
        );
        $hasTable->execute([$database]);

        if (!$hasTable->fetchColumn()) {
            $pdo->exec("CREATE TABLE `job_position_assigned_evaluators` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `job_position_id` BIGINT UNSIGNED NOT NULL,
                `evaluator_user_id` BIGINT UNSIGNED NOT NULL,
                `created_at` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `job_position_evaluator_unique` (`job_position_id`, `evaluator_user_id`),
                KEY `job_position_assigned_evaluators_evaluator_user_id_foreign` (`evaluator_user_id`),
                CONSTRAINT `job_position_assigned_evaluators_job_position_id_foreign` FOREIGN KEY (`job_position_id`) REFERENCES `job_positions` (`id`) ON DELETE CASCADE,
                CONSTRAINT `job_position_assigned_evaluators_evaluator_user_id_foreign` FOREIGN KEY (`evaluator_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
    });
}

/** Runs $apply once per $marker file, swallowing failures (see portal_ensure_schema()). */
function portal_apply_migration_once(PDO $pdo, string $database, string $marker, callable $apply): void
{
    if (file_exists($marker)) {
        return;
    }

    try {
        $apply($pdo, $database);
        @file_put_contents($marker, date('c'));
    } catch (Throwable $e) {
        // Swallow — the original error (if the fix didn't apply) still
        // surfaces normally wherever the query actually runs.
    }
}

/**
 * Run an INSERT and return the new row's auto-increment id.
 *
 * @param array<string, mixed> $data
 */
function portal_insert(string $table, array $data): int
{
    $columns = array_keys($data);
    $placeholders = array_map(fn ($c) => ':'.$c, $columns);

    $sql = sprintf(
        'INSERT INTO %s (%s) VALUES (%s)',
        $table,
        implode(', ', $columns),
        implode(', ', $placeholders)
    );

    $stmt = portal_pdo()->prepare($sql);
    $stmt->execute($data);

    return (int) portal_pdo()->lastInsertId();
}
