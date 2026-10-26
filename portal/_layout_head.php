<?php
if (!defined('PORTAL_BOOTED')) { http_response_code(404); exit; }
$pageTitle = $pageTitle ?? 'DepEd Recruitment Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= portal_e($pageTitle) ?></title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        government: {
                            navy: '#1E6B42',
                            blue: '#278253',
                            light: '#EAF4EE',
                            gold: '#D4A017',
                            dark: '#16402B',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Edge adds its own eye button to password fields; these pages have their own show-password toggle. */
        input[type="password"]::-ms-reveal, input[type="password"]::-ms-clear { display: none; }
    </style>
</head>
<body class="flex min-h-screen flex-col bg-slate-50 text-slate-800">
