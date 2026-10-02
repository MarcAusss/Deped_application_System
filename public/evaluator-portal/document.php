<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

evaluator_require_login();

$path = $_GET['path'] ?? '';

if ($path === '') {
    http_response_code(404);
    exit;
}

$documentsRoot = realpath(PORTAL_ROOT.'/storage/app/public');
$requested = realpath($documentsRoot.'/'.$path);

if ($requested === false || !str_starts_with($requested, $documentsRoot)) {
    http_response_code(404);
    exit;
}

// Any logged-in, approved evaluator may view any application's document —
// matches the source system's /files/{path} route having zero ownership
// check at all. Still requires the path to correspond to a real, tracked
// document row (closes the "any arbitrary file under storage/app/public"
// gap the original route left open).
$stmt = portal_pdo()->prepare('SELECT id FROM application_documents WHERE file_path = ? LIMIT 1');
$stmt->execute([$path]);

if (!$stmt->fetch()) {
    http_response_code(404);
    exit;
}

$ext = strtolower(pathinfo($requested, PATHINFO_EXTENSION));
$mime = match ($ext) {
    'pdf' => 'application/pdf',
    'jpg', 'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    default => 'application/octet-stream',
};

header('Content-Type: '.$mime);
header('Content-Length: '.filesize($requested));
header('Content-Disposition: inline; filename="'.basename($requested).'"');
readfile($requested);
exit;
