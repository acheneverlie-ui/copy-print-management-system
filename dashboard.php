<?php
require __DIR__ . '/config.php';
require_login();
$pdo = db();
$total = (int)$pdo->query('SELECT COALESCE(SUM(copy_count),0) FROM print_records')->fetchColumn();
$records = (int)$pdo->query('SELECT COUNT(*) FROM print_records')->fetchColumn();
$printers = (int)$pdo->query('SELECT COUNT(*) FROM printers WHERE is_active = 1')->fetchColumn();
$users = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE is_active = 1')->fetchColumn();
$colorTotals = $pdo->query("SELECT COALESCE(SUM(CASE WHEN color_mode='color' THEN copy_count END),0) AS color_total, COALESCE(SUM(CASE WHEN color_mode='bw' THEN copy_count END),0) AS bw_total FROM print_records")->fetch();
$byType = $pdo->query("SELECT user_type,
    COALESCE(SUM(CASE WHEN color_mode='color' THEN copy_count END),0) AS color_total,
    COALESCE(SUM(CASE WHEN color_mode='bw' THEN copy_count END),0) AS bw_total,
    COALESCE(SUM(CASE WHEN job_type='scan' THEN copy_count END),0) AS scan_total,
    COALESCE(SUM(copy_count),0) AS total
  FROM print_records GROUP BY user_type")->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
$recent = $pdo->query('SELECT r.created_at, r.copy_count, r.job_type, r.color_mode, r.user_type, u.username, p.printer_name, r.notes FROM print_records r JOIN users u ON u.id=r.user_id JOIN printers p ON p.id=r.printer_id ORDER BY r.created_at DESC LIMIT 10')->fetchAll();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Dashboard</title><link rel="stylesheet" href="style.css"></head><body>
<?php include __DIR__ . '/nav.php'; ?><main class="container"><h1>Dashboard</h1><p class="muted">Welcome, <?=h($_SESSION['user']['display_name'] ?: $_SESSION['user']['username'])?>.</p>
<section class="stats">
<div class="stat"><span>Total copies/prints</span><strong><?=number_format($total)?></strong></div>
<div class="stat"><span>Records</span><strong><?=number_format($records)?></strong></div>
<div class="stat"><span>Active printers</span><strong><?=number_format($printers)?></strong></div>
<div class="stat"><span>Active users</span><strong><?=number_format($users)?></strong></div>
</section>
<section class="stats stats-2">
<div class="stat"><span>Color pages</span><strong><?=number_format((int)$colorTotals['color_total'])?></strong></div>
<div class="stat"><span>B/W pages</span><strong><?=number_format((int)$colorTotals['bw_total'])?></strong></div>
</section>
<div class="panel"><h2>Usage by customer type</h2><div class="table-wrap"><table><thead><tr><th>Type</th><th>Color</th><th>B/W</th><th>Scan</th><th>Total</th></tr></thead><tbody>
<?php foreach (USER_TYPES as $k => $label): $row = $byType[$k] ?? ['color_total'=>0,'bw_total'=>0,'scan_total'=>0,'total'=>0]; ?><tr><td><span class="badge badge-<?=h($k)?>"><?=h($label)?></span></td><td><?=number_format((int)$row['color_total'])?></td><td><?=number_format((int)$row['bw_total'])?></td><td><?=number_format((int)$row['scan_total'])?></td><td><strong><?=number_format((int)$row['total'])?></strong></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<div class="panel"><div class="panel-head"><h2>Recent activity</h2><a class="button" href="record.php">+ Add record</a></div>
<div class="table-wrap"><table><thead><tr><th>Date/time</th><th>User</th><th>Customer type</th><th>Printer</th><th>Job</th><th>Color</th><th>Copies</th><th>Notes</th></tr></thead><tbody>
<?php foreach ($recent as $r): ?><tr><td><?=h($r['created_at'])?></td><td><?=h($r['username'])?></td><td><span class="badge badge-<?=h($r['user_type'])?>"><?=h(user_type_label($r['user_type']))?></span></td><td><?=h($r['printer_name'])?></td><td><?=h(JOB_TYPES[$r['job_type']] ?? $r['job_type'])?></td><td><?php if($r['color_mode']): ?><span class="badge badge-<?=h($r['color_mode'])?>"><?=h(color_label($r['color_mode']))?></span><?php else: ?>-<?php endif; ?></td><td><?=number_format((int)$r['copy_count'])?></td><td><?=h($r['notes'])?></td></tr><?php endforeach; ?>
<?php if (!$recent): ?><tr><td colspan="8" class="muted">No records yet.</td></tr><?php endif; ?>
</tbody></table></div></div></main></body></html>
