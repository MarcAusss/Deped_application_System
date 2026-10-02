<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
}

admin_logout();

header('Location: '.admin_url('login.php'));
exit;
