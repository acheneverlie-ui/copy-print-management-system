<?php
require __DIR__ . '/config.php';
require_admin();
$pdo = db();
$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (($_POST['action'] ?? '') === 'update_type') {
        $uid = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $newType = $_POST['user_type'] ?? '';
        if ($uid && isset(USER_TYPES[$newType])) {
            $pdo->prepare('UPDATE users SET user_type=? WHERE id=?')->execute([$newType, $uid]);
            $success = 'User type updated. Past records keep the type they were recorded with.';
        } else { $error = 'Invalid user or type.'; }
    } else {
    $username = trim($_POST['username'] ?? '');
    $display = trim($_POST['display_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $userType = $_POST['user_type'] ?? 'student';
    if (!preg_match('/^[a-zA-Z0-9_.-]{3,40}$/', $username) || strlen($password) < 10 || !in_array($role,['admin','user'],true) || !isset(USER_TYPES[$userType])) {
        $error = 'Username must be 3–40 letters/numbers/._- and password must be at least 10 characters.';
    } else {
        try {
            $stmt=$pdo->prepare('INSERT INTO users (username,password_hash,display_name,role,user_type) VALUES (?,?,?,?,?)');
            $stmt->execute([$username,password_hash($password,PASSWORD_DEFAULT),$display,$role,$userType]);
            $success='User created.';
        } catch(Throwable $e) { $error='Could not create user. Username may already exist.'; }
    }
    }
}
$users=$pdo->query('SELECT id,username,display_name,role,user_type,is_active,created_at FROM users ORDER BY created_at DESC')->fetchAll();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>User management</title><link rel="stylesheet" href="style.css"></head><body><?php include __DIR__ . '/nav.php'; ?><main class="container"><h1>User management</h1><?php if($error): ?><div class="alert"><?=h($error)?></div><?php endif; ?><?php if($success): ?><div class="success"><?=h($success)?></div><?php endif; ?>
<form class="panel form-grid" method="post"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><label>Username<input name="username" required minlength="3" maxlength="40"></label><label>Display name<input name="display_name" maxlength="100"></label><label>Password (min 10 characters)<input type="password" name="password" required minlength="10"></label><label>Role<select name="role"><option value="user">User</option><option value="admin">Admin</option></select></label><label>Customer type<select name="user_type"><?php foreach(USER_TYPES as $k=>$label): ?><option value="<?=h($k)?>"><?=h($label)?></option><?php endforeach; ?></select></label><div class="full"><button class="primary">Create user</button></div></form>
<div class="panel"><h2>Existing users</h2><div class="table-wrap"><table><thead><tr><th>Username</th><th>Name</th><th>Role</th><th>Customer type</th><th>Active</th><th>Created</th></tr></thead><tbody><?php foreach($users as $u): ?><tr><td><?=h($u['username'])?></td><td><?=h($u['display_name'])?></td><td><?=h($u['role'])?></td><td><form method="post" class="inline-form"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><input type="hidden" name="action" value="update_type"><input type="hidden" name="user_id" value="<?=(int)$u['id']?>"><select name="user_type"><?php foreach(USER_TYPES as $k=>$label): ?><option value="<?=h($k)?>" <?=$u['user_type']===$k?'selected':''?>><?=h($label)?></option><?php endforeach; ?></select><button class="button small" type="submit">Save</button></form></td><td><?= $u['is_active']?'Yes':'No' ?></td><td><?=h($u['created_at'])?></td></tr><?php endforeach; ?></tbody></table></div></div></main></body></html>
