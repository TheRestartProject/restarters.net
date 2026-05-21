<?php
$password = getenv('BASIC_AUTH_PASSWORD') ?: 'project';
$secret   = getenv('APP_KEY') ?: 'fallback';
$token    = hash_hmac('sha256', $password, $secret);
$next     = preg_replace('/[^\w\-\/.?=&%]/', '', $_GET['next'] ?? '/');
$error    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (hash_equals($password, $_POST['password'] ?? '')) {
        setcookie('site_auth', $token, [
            'expires'  => time() + 86400 * 30,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        header('Location: ' . $next);
        exit;
    }
    $error = 'Wrong password.';
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(getenv('APP_NAME') ?: 'Restarters') ?></title>
    <style>
        body { font-family: sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f0f0f0; }
        .box { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,.15); width: 300px; }
        h1 { margin: 0 0 1.25rem; font-size: 1.1rem; color: #333; }
        .err { color: #c00; margin-bottom: .75rem; font-size: .9rem; }
        input { width: 100%; padding: .5rem; margin-bottom: 1rem; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 1rem; }
        button { width: 100%; padding: .5rem; background: #c0392b; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
        button:hover { background: #a93226; }
    </style>
</head>
<body>
<div class="box">
    <h1><?= htmlspecialchars(getenv('APP_NAME') ?: 'Restarters') ?></h1>
    <?php if ($error): ?><p class="err"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="POST">
        <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">
        <input type="password" name="password" placeholder="Password" autofocus>
        <button type="submit">Enter</button>
    </form>
</div>
</body>
</html>
