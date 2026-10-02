<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

admin_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
csrf_verify();

$pdo = portal_pdo();
$applicationId = (int) ($_POST['application_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$applicationId) {
    http_response_code(400);
    exit;
}

$redirect = admin_url('application-view.php?id='.$applicationId);

if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);

    $stmt = $pdo->prepare('SELECT file_path FROM application_documents WHERE id = ? AND application_id = ?');
    $stmt->execute([$id, $applicationId]);
    $doc = $stmt->fetch();

    $pdo->prepare('DELETE FROM application_documents WHERE id = ? AND application_id = ?')->execute([$id, $applicationId]);

    if ($doc) {
        admin_delete_upload($doc->file_path);
    }

    flash_set('success', 'Document deleted.');
    header('Location: '.$redirect);
    exit;
}

// Documents relation manager only supports create + delete (no edit) in the
// live system — matches DocumentsRelationManager.php having no EditAction.
if ($action !== 'create') {
    http_response_code(400);
    exit;
}

$type = $_POST['type'] ?? '';
if (!array_key_exists($type, admin_document_types())) {
    flash_set('error', 'Please select a valid document type.');
    header('Location: '.$redirect);
    exit;
}

if (empty($_FILES['file']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    flash_set('error', 'Please choose a file to upload.');
    header('Location: '.$redirect);
    exit;
}

// Mirrors DocumentsRelationManager's ->maxSize(5120) (KB).
if (($_FILES['file']['size'] ?? 0) > 5120 * 1024) {
    flash_set('error', 'File is too large. Maximum size is 5MB.');
    header('Location: '.$redirect);
    exit;
}

try {
    $relativePath = admin_store_upload($_FILES['file'], 'applications/documents', ['pdf', 'jpg', 'jpeg', 'png']);
} catch (RuntimeException $e) {
    flash_set('error', $e->getMessage());
    header('Location: '.$redirect);
    exit;
}

if ($relativePath === null) {
    flash_set('error', 'Please choose a file to upload.');
    header('Location: '.$redirect);
    exit;
}

$pdo->prepare(
    'INSERT INTO application_documents (application_id, type, file_path, created_at, updated_at)
     VALUES (?, ?, ?, NOW(), NOW())'
)->execute([$applicationId, $type, $relativePath]);

flash_set('success', 'Document uploaded.');
header('Location: '.$redirect);
exit;
