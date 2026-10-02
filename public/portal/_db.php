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
    $database = portal_env('DB_DATABASE', 'laravel');
    $username = portal_env('DB_USERNAME', 'root');
    $password = portal_env('DB_PASSWORD', '');

    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
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
