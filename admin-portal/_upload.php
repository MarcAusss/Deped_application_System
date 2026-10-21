<?php

if (!defined('PORTAL_BOOTED')) {
    http_response_code(404);
    exit;
}

/**
 * File-upload helpers mirroring Filament's FileUpload component, writing
 * directly to the `public` disk's root (storage/app/public/...) so uploaded
 * files are served the exact same way the Laravel app already serves them
 * (via the public/storage symlink and the existing document.php streaming
 * pattern from Phase 1/2).
 */

function admin_upload_root(): string
{
    return PORTAL_ROOT.'/storage/app/public';
}

/**
 * Store one uploaded file under $directory, generating a unique filename
 * (Filament's default FileUpload behaviour when ->preserveFilenames() is
 * NOT used, e.g. the Documents relation manager). Returns the relative path
 * (as stored in the DB / disk), or null if no file was uploaded.
 */
function admin_store_upload(array $file, string $directory, array $allowedExt): ?string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload failed with error code '.$file['error']);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        throw new RuntimeException('File type not allowed: .'.$ext);
    }

    $root = admin_upload_root().'/'.$directory;
    if (!is_dir($root)) {
        mkdir($root, 0755, true);
    }

    $filename = bin2hex(random_bytes(16)).'.'.$ext;
    $destination = $root.'/'.$filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Failed to move uploaded file.');
    }

    return $directory.'/'.$filename;
}

/**
 * Store one or more uploaded files under $directory, PRESERVING original
 * filenames (Filament's ->preserveFilenames(), used by JobPositionResource's
 * attachment_paths/csc_publication_paths fields). Collisions get a numeric
 * suffix. Accepts a $_FILES-style array for a multi="true" input
 * (name="field[]"). Returns a list of relative paths.
 *
 * @return array<int, string>
 */
function admin_store_uploads_preserve_names(array $filesField, string $directory, array $allowedExt): array
{
    if (empty($filesField['name']) || !is_array($filesField['name'])) {
        return [];
    }

    $root = admin_upload_root().'/'.$directory;
    if (!is_dir($root)) {
        mkdir($root, 0755, true);
    }

    $stored = [];
    $count = count($filesField['name']);

    for ($i = 0; $i < $count; $i++) {
        if (($filesField['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($filesField['error'][$i] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('File upload failed with error code '.$filesField['error'][$i]);
        }

        $originalName = basename($filesField['name'][$i]);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            throw new RuntimeException('File type not allowed: .'.$ext);
        }

        $base = pathinfo($originalName, PATHINFO_FILENAME);
        $base = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $base) ?: 'file';
        $filename = $base.'.'.$ext;
        $suffix = 1;
        while (file_exists($root.'/'.$filename)) {
            $filename = $base.'_'.$suffix.'.'.$ext;
            $suffix++;
        }

        if (!move_uploaded_file($filesField['tmp_name'][$i], $root.'/'.$filename)) {
            throw new RuntimeException('Failed to move uploaded file.');
        }

        $stored[] = $directory.'/'.$filename;
    }

    return $stored;
}

function admin_delete_upload(?string $relativePath): void
{
    if (!$relativePath) {
        return;
    }
    $full = admin_upload_root().'/'.$relativePath;
    if (is_file($full)) {
        @unlink($full);
    }
}
