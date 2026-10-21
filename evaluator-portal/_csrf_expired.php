<?php

if (!defined('PORTAL_BOOTED')) {
    http_response_code(404);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Session Expired | DepEd Recruitment Evaluator</title>
<style>
  body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:#f8fafc; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; color:#1e293b; }
  .card { max-width:420px; margin:24px; padding:32px; background:#fff; border:1px solid #e2e8f0; border-radius:16px; box-shadow:0 1px 3px rgba(0,0,0,.08); text-align:center; }
  h1 { margin:0 0 12px; font-size:22px; color:#1E1B4B; }
  p { margin:0 0 20px; color:#475569; line-height:1.5; }
  a { display:inline-block; padding:10px 20px; background:#1E1B4B; color:#fff; text-decoration:none; border-radius:10px; font-weight:700; }
  a:hover { background:#3730A3; }
</style>
</head>
<body>
  <div class="card">
    <h1>Session Expired</h1>
    <p>Your session has expired or this page was open for a while. Please try again — you may need to sign in again first.</p>
    <a href="/evaluator-portal/login.php">Back to Login</a>
  </div>
</body>
</html>
