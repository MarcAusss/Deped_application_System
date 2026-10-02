<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
}

portal_logout();

header('Location: '.portal_url('jobs.php'));
exit;
