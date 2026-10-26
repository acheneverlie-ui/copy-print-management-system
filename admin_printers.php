<?php
require __DIR__ . '/config.php';
require_admin();
$pdo=db(); $error=''; $success='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    check_csrf();
    $name=trim($_POST['printer_name']??''); $code=trim($_POST['printer_code']??''); $group=trim($_POST['group_name']??'');
    if($name==='' || $code==='' || $group==='') $error='Please complete all fields.';
    else {
        try {
            $stmt=$pdo->prepare('INSERT INTO printers (printer_name,printer_code,group_name) VALUES (?,?,?)');
            $stmt->execute([$name,$code,$group]); $success='Printer added.';
        } catch(Throwable $e) { $error='Could not add printer. Printer ID may already exist.'; }
    }
}
$printers=$pdo->query('SELECT printer_name,printer_code,group_name,is_active,created_at FROM printers ORDER BY group_name,printer_name')->fetchAll();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Printer management</title><link rel="stylesheet" href="style.css"></head><body><?php include __DIR__ . '/nav.php'; ?><main class="container"><h1>Printer management</h1><?php if($error): ?><div class="alert"><?=h($error)?></div><?php endif; ?><?php if($success): ?><div class="success"><?=h($success)?></div><?php endif; ?>
<form class="panel form-grid" method="post"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><label>Printer name<input name="printer_name" required></label><label>Printer ID / Code<input name="printer_code" required></label><label>Printer group<input name="group_name" placeholder="e.g. Office A" required></label><div class="full"><button class="primary">Add printer</button></div></form>
<div class="panel"><h2>Printers</h2><div class="table-wrap"><table><thead><tr><th>Name</th><th>ID</th><th>Group</th><th>Active</th><th>Created</th></tr></thead><tbody><?php foreach($printers as $p): ?><tr><td><?=h($p['printer_name'])?></td><td><?=h($p['printer_code'])?></td><td><?=h($p['group_name'])?></td><td><?=$p['is_active']?'Yes':'No'?></td><td><?=h($p['created_at'])?></td></tr><?php endforeach; ?></tbody></table></div></div></main></body></html>
