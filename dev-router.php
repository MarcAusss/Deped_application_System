<?php

/**
 * Router for PHP's built-in dev server only (php -S ... dev-router.php).
 * Apache uses .htaccess for this same blocking; the built-in server
 * ignores .htaccess entirely, so this replicates it for local dev —
 * keeps .env, vendor/, storage/, composer.json/.lock, and .git/ from
 * being served as plain files when testing with `php -S`.
 */

$path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (preg_match('#^/(\.|vendor/|storage/|database/|composer\.(json|lock)$)#', $path) || str_ends_with($path, '.sql')) {
    http_response_code(403);
    echo 'Forbidden';
    return true;
}

return false;
