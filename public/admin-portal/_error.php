<?php

if (!defined('PORTAL_BOOTED')) {
    http_response_code(404);
    exit;
}

http_response_code(500);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Something Went Wrong | DepEd Recruitment Admin</title>
<style>
  body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:#f8fafc; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; color:#1e293b; }
  .card { max-width:420px; margin:24px; padding:32px; background:#fff; border:1px solid #e2e8f0; border-radius:16px; box-shadow:0 1px 3px rgba(0,0,0,.08); text-align:center; }
  h1 { margin:0 0 12px; font-size:22px; color:#123B6D; }
  p { margin:0 0 20px; color:#475569; line-height:1.5; }
  a { display:inline-block; padding:10px 20px; background:#123B6D; color:#fff; text-decoration:none; border-radius:10px; font-weight:700; }
  a:hover { background:#1D4E89; }
</style>
</head>
<body>
  <div class="card">
    <h1>Something Went Wrong</h1>
    <p>We hit an unexpected problem on our end. Please try again in a moment. If this keeps happening, check storage/logs/laravel.log for details.</p>
    <a href="/">Back to Home</a>
  </div>
</body>
</html>
