<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';
require PORTAL_ROOT.'/evaluator-portal/_ier.php';

admin_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
csrf_verify();

$ids = $_POST['ids'] ?? [];
$ids = array_values(array_unique(array_map('intval', array_filter($ids, fn ($v) => ctype_digit((string) $v)))));
$groups = eval_ier_load_groups($ids, '1=1');

eval_ier_send_xlsx($groups, count($ids) > 0 && ($_POST['source'] ?? '') !== 'filtered');
