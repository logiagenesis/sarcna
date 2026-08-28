<?php /** @var int $status @var string $message */ ?><!DOCTYPE html>
<html lang="en-ZA">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= (int) $status ?> — SARCNA 2027 Convention</title>
<meta name="robots" content="noindex">
<style>
  body { margin:0; min-height:100vh; display:grid; place-items:center; padding:2rem;
         background:#173D2F; color:#FFF6E7; font:16px/1.6 -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; }
  .box { max-width:520px; text-align:center; }
  .code { font-size:4.5rem; font-weight:700; line-height:1; color:#D9A441; margin-bottom:.5rem; }
  h1 { font-size:1.5rem; margin:0 0 .75rem; }
  a { display:inline-block; margin-top:1.5rem; padding:.8rem 1.6rem; border:2px solid #D9A441;
      border-radius:999px; color:#FFF6E7; text-decoration:none; font-weight:700; }
  a:hover { background:#D9A441; color:#0E241C; }
</style>
</head>
<body>
  <div class="box">
    <div class="code"><?= (int) $status ?></div>
    <h1><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></h1>
    <p>If this keeps happening, please contact the convention committee.</p>
    <a href="/">Back to the home page</a>
  </div>
</body>
</html>
