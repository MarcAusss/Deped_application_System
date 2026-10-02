<?php
if (!defined('PORTAL_BOOTED')) { http_response_code(404); exit; }
$pageTitle = $pageTitle ?? 'DepEd Recruitment | Administration';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= admin_e($pageTitle) ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        admin: {
                            indigo: '#4338CA',
                            indigoLight: '#EEF2FF',
                            indigoDark: '#1E1B4B',
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
</head>
<body class="flex min-h-screen flex-col bg-slate-50 text-slate-800">
