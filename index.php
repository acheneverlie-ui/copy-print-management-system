<?php
require __DIR__ . '/config.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username !== '' && $password !== '') {
        $stmt = db()->prepare('SELECT id, username, password_hash, display_name, role, user_type FROM users WHERE username = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            unset($user['password_hash']);
            $_SESSION['user'] = $user;
            header('Location: dashboard.php');
            exit;
        }
    }
    $error = 'Incorrect username or password.';
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Copy & Print Login</title><link rel="stylesheet" href="style.css"></head><body class="login-bg">
<main class="login-card"><h1>Copy & Print</h1><p class="muted">Management System</p>
<?php if ($error): ?><div class="alert"><?=h($error)?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>">
<label>Username<input name="username" required autocomplete="username"></label>
<label>Password<input type="password" name="password" required autocomplete="current-password"></label>
<button class="primary" type="submit">Log in</button></form>
<p class="muted small">Ask your system administrator for an account.</p></main></body></html>
