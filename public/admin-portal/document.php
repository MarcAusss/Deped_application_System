<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

admin_require_login();

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

// Admin can view any application document or job-position attachment —
// confirm the path corresponds to a real tracked file (either an
// application_documents row, or a job_positions attachment/CSC publication
// path) before streaming it, closing the "any arbitrary file under
// storage/app/public" gap.
$stmt = portal_pdo()->prepare('SELECT id FROM application_documents WHERE file_path = ? LIMIT 1');
$stmt->execute([$path]);
$known = (bool) $stmt->fetch();

if (!$known) {
    $stmt = portal_pdo()->query('SELECT attachment_paths, csc_publication_paths FROM job_positions');
    foreach ($stmt->fetchAll() as $row) {
        $attachments = array_merge(
            json_decode($row->attachment_paths ?? '[]', true) ?: [],
            json_decode($row->csc_publication_paths ?? '[]', true) ?: []
        );
        if (in_array($path, $attachments, true)) {
            $known = true;
            break;
        }
    }
}

if (!$known) {
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
