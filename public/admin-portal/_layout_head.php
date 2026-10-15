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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.1/chart.umd.min.js"></script>

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

        /*
         * Keeps laptops and desktops looking like the 1920px (100%) reference
         * layout: since every size here is in rem, scaling the root font size
         * by viewport width scales the whole page proportionally instead of
         * just reflowing/cramping on narrower screens. Phones/tablets
         * (< 1024px) are left untouched.
         */
        @media (min-width: 1024px) {
            html { font-size: clamp(11.2px, calc(100vw / 120), 16px); }
        }
    </style>
</head>
<body class="flex min-h-screen flex-col bg-slate-50 text-slate-800">
