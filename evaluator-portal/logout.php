<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
}

evaluator_logout();

header('Location: '.BASE_PATH.'/');
exit;
