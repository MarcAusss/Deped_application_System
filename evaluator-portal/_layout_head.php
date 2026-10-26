<?php
if (!defined('PORTAL_BOOTED')) { http_response_code(404); exit; }
$pageTitle = $pageTitle ?? 'Evaluation Desk | DepEd Recruitment Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= evaluator_e($pageTitle) ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.1/chart.umd.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        evaluator: {
                            indigo: '#1E6B42',
                            indigoLight: '#EAF4EE',
                            indigoDark: '#16402B',
                            gold: '#D4A017',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        .pill { display: inline-flex; align-items: center; border-radius: 9999px; padding: .25rem .75rem; font-size: .75rem; font-weight: 700; text-transform: uppercase; }
    </style>
    <style>
        /* Edge adds its own eye button to password fields; these pages have their own show-password toggle. */
        input[type="password"]::-ms-reveal, input[type="password"]::-ms-clear { display: none; }
    </style>
</head>
<body class="flex min-h-screen flex-col bg-slate-50 text-slate-800">
