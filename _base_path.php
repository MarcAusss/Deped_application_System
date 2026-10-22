<?php

if (!defined('PORTAL_BOOTED')) {
    http_response_code(404);
    exit;
}

/**
 * Detects the URL prefix this app is actually installed under — "" when it
 * sits at the web server's true document root, or e.g. "/cares5" when it's
 * been placed in a subfolder instead. Every internal link (portal_url(),
 * admin_url(), evaluator_url(), asset_url()) is built from this, so the
 * exact same codebase works correctly no matter which one is true on a
 * given machine, with no web server configuration required.
 *
 * $depth is how many folder levels the CURRENTLY EXECUTING script sits
 * below the project root: 0 for a file directly in the root (index.php,
 * staff-login.php), 1 for a portal page (admin-portal/*.php,
 * evaluator-portal/*.php, portal/*.php) — none of this project's pages are
 * nested any deeper than that.
 */
function app_base_path(int $depth): string
{
    $path = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    for ($i = 0; $i < $depth; $i++) {
        $path = dirname($path);
    }
    $path = str_replace('\\', '/', $path);

    return $path === '/' || $path === '.' ? '' : rtrim($path, '/');
}
