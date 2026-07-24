<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Laravel Comment Remover
|--------------------------------------------------------------------------
|
| Usage:
|
| Preview only:
| php remove-comments.php
|
| Remove normal PHP and Blade comments:
| php remove-comments.php --apply
|
| Remove normal comments and PHPDoc comments:
| php remove-comments.php --apply --remove-docblocks
|
| Also remove HTML comments:
| php remove-comments.php --apply --remove-docblocks --remove-html-comments
|
*/

$root = __DIR__;

$applyChanges = in_array('--apply', $argv, true);
$removeDocBlocks = in_array('--remove-docblocks', $argv, true);
$removeHtmlComments = in_array('--remove-html-comments', $argv, true);

$targets = [
    'app',
    'bootstrap',
    'config',
    'database',
    'routes',
    'tests',
    'resources/views',
];

$excludedDirectories = [
    '.git',
    '.idea',
    '.vscode',
    'vendor',
    'node_modules',
    'storage',
    'public/build',
    'bootstrap/cache',
];

$changedFiles = [];
$scannedFiles = 0;

/**
 * Preserve line breaks when removing comments.
 */
function preserveLineBreaks(string $comment): string
{
    $lineBreaks = preg_replace('/[^\r\n]/', '', $comment);

    return $lineBreaks !== '' ? $lineBreaks : ' ';
}

/**
 * Remove PHP comments using PHP's tokenizer.
 *
 * This is safer than regular expressions because it does not remove comment-like
 * text inside strings, URLs, SQL statements, or regular expressions.
 */
function removePhpComments(
    string $content,
    bool $removeDocBlocks
): string {
    $tokens = token_get_all($content);

    $result = '';

    foreach ($tokens as $token) {
        if (! is_array($token)) {
            $result .= $token;
            continue;
        }

        [$tokenId, $tokenText] = $token;

        $isNormalComment = $tokenId === T_COMMENT;
        $isDocBlock = $tokenId === T_DOC_COMMENT;

        if ($isNormalComment || ($removeDocBlocks && $isDocBlock)) {
            $result .= preserveLineBreaks($tokenText);
            continue;
        }

        $result .= $tokenText;
    }

    return $result;
}

/**
 * Remove Blade comments:
 *
 * {{-- Blade comment --}}
 */
function removeBladeComments(string $content): string
{
    return preg_replace_callback(
        '/\{\{--[\s\S]*?--\}\}/',
        static fn (array $match): string => preserveLineBreaks($match[0]),
        $content
    ) ?? $content;
}

/**
 * Remove regular HTML comments.
 *
 * This is optional because some JavaScript libraries may use HTML comments.
 */
function removeHtmlComments(string $content): string
{
    return preg_replace_callback(
        '/<!--(?!\[if)[\s\S]*?-->/i',
        static fn (array $match): string => preserveLineBreaks($match[0]),
        $content
    ) ?? $content;
}

/**
 * Determine whether a path is excluded.
 */
function isExcludedPath(
    string $path,
    string $root,
    array $excludedDirectories
): bool {
    $normalizedPath = str_replace('\\', '/', $path);
    $normalizedRoot = rtrim(str_replace('\\', '/', $root), '/');

    $relativePath = ltrim(
        str_replace($normalizedRoot, '', $normalizedPath),
        '/'
    );

    foreach ($excludedDirectories as $excludedDirectory) {
        $excludedDirectory = trim(
            str_replace('\\', '/', $excludedDirectory),
            '/'
        );

        if (
            $relativePath === $excludedDirectory ||
            str_starts_with($relativePath, $excludedDirectory . '/')
        ) {
            return true;
        }
    }

    return false;
}

/**
 * Recursively collect PHP files.
 */
function collectPhpFiles(
    string $path,
    string $root,
    array $excludedDirectories
): array {
    $files = [];

    if (! file_exists($path)) {
        return $files;
    }

    if (isExcludedPath($path, $root, $excludedDirectories)) {
        return $files;
    }

    if (is_file($path)) {
        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'php') {
            $files[] = $path;
        }

        return $files;
    }

    $iterator = new DirectoryIterator($path);

    foreach ($iterator as $item) {
        if ($item->isDot() || $item->isLink()) {
            continue;
        }

        $itemPath = $item->getPathname();

        if (isExcludedPath($itemPath, $root, $excludedDirectories)) {
            continue;
        }

        if ($item->isDir()) {
            $files = array_merge(
                $files,
                collectPhpFiles(
                    $itemPath,
                    $root,
                    $excludedDirectories
                )
            );

            continue;
        }

        if (
            $item->isFile() &&
            strtolower($item->getExtension()) === 'php'
        ) {
            $files[] = $itemPath;
        }
    }

    return $files;
}

echo PHP_EOL;
echo "Laravel Comment Remover" . PHP_EOL;
echo "=======================" . PHP_EOL;
echo $applyChanges
    ? "Mode: APPLY CHANGES"
    : "Mode: PREVIEW ONLY";
echo PHP_EOL;

echo "PHPDoc comments: "
    . ($removeDocBlocks ? 'REMOVE' : 'PRESERVE')
    . PHP_EOL;

echo "HTML comments: "
    . ($removeHtmlComments ? 'REMOVE' : 'PRESERVE')
    . PHP_EOL;

echo PHP_EOL;

foreach ($targets as $target) {
    $targetPath = $root . DIRECTORY_SEPARATOR . $target;

    $files = collectPhpFiles(
        $targetPath,
        $root,
        $excludedDirectories
    );

    foreach ($files as $file) {
        $scannedFiles++;

        $originalContent = file_get_contents($file);

        if ($originalContent === false) {
            echo "[ERROR] Could not read: {$file}" . PHP_EOL;
            continue;
        }

        $newContent = removePhpComments(
            $originalContent,
            $removeDocBlocks
        );

        $normalizedFile = str_replace('\\', '/', $file);
        $viewsDirectory = str_replace(
            '\\',
            '/',
            $root . '/resources/views/'
        );

        if (str_starts_with($normalizedFile, $viewsDirectory)) {
            $newContent = removeBladeComments($newContent);

            if ($removeHtmlComments) {
                $newContent = removeHtmlComments($newContent);
            }
        }

        if ($newContent === $originalContent) {
            continue;
        }

        $relativePath = ltrim(
            str_replace(
                str_replace('\\', '/', $root),
                '',
                $normalizedFile
            ),
            '/'
        );

        $changedFiles[] = $relativePath;

        if (! $applyChanges) {
            echo "[PREVIEW] {$relativePath}" . PHP_EOL;
            continue;
        }

        $written = file_put_contents($file, $newContent);

        if ($written === false) {
            echo "[ERROR] Could not update: {$relativePath}" . PHP_EOL;
            continue;
        }

        echo "[UPDATED] {$relativePath}" . PHP_EOL;
    }
}

echo PHP_EOL;
echo "Scanned files: {$scannedFiles}" . PHP_EOL;
echo "Files with comments: " . count($changedFiles) . PHP_EOL;

if (! $applyChanges && count($changedFiles) > 0) {
    echo PHP_EOL;
    echo "No files were changed." . PHP_EOL;
    echo "Run this command to apply the changes:" . PHP_EOL;
    echo PHP_EOL;
    echo "php remove-comments.php --apply" . PHP_EOL;
}

if ($applyChanges) {
    echo PHP_EOL;
    echo "Comment removal completed." . PHP_EOL;
    echo "Run your tests and inspect the Git changes before committing." . PHP_EOL;
}

echo PHP_EOL;