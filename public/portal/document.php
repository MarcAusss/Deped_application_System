<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

portal_require_login();

$path = $_GET['path'] ?? '';

if ($path === '') {
    http_response_code(404);
    exit;
}

// Reject path traversal before any filesystem access.
$documentsRoot = realpath(PORTAL_ROOT.'/storage/app/public');
$requested = realpath($documentsRoot.'/'.$path);

if ($requested === false || !str_starts_with($requested, $documentsRoot)) {
    http_response_code(404);
    exit;
}

$user = portal_user();

// Ownership check: the /files/{path} Laravel route this mirrors has none at
// all — this closes that gap rather than reproducing it. The file must
// either belong to a document row whose application belongs to the logged-in
// applicant, OR be a job posting's own attachment/CSC publication — those
// are public to any logged-in applicant browsing or applying to that job,
// not ownership-restricted (mirrors admin-portal/document.php's same
// two-case check).
$stmt = portal_pdo()->prepare(
    'SELECT ad.id
     FROM application_documents ad
     INNER JOIN applications a ON a.id = ad.application_id
     WHERE ad.file_path = ? AND a.applicant_id = ?
     LIMIT 1'
);
$stmt->execute([$path, $user->id]);
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

// Hardcoded, not detected via ext-fileinfo/mime_content_type() — that
// extension has proven unreliable on this project's actual hosting (the
// exact "Class finfo not found" bug this whole system was debugged for
// earlier). Every document this portal stores is already PDF-only,
// verified by its magic bytes at upload time in apply.php, so there is
// nothing to detect here.
header('Content-Type: application/pdf');
header('Content-Length: '.filesize($requested));
header('Content-Disposition: inline; filename="'.basename($requested).'"');
readfile($requested);
exit;
