<?php
require __DIR__ . '/config.php';
require_login();
$pdo = db();
$period = $_GET['period'] ?? 'day';
$allowed = ['day','week','month','year','all'];
if (!in_array($period, $allowed, true)) $period = 'day';
$where = match($period) {
    'day' => 'r.created_at >= CURDATE() AND r.created_at < CURDATE() + INTERVAL 1 DAY',
    'week' => 'YEARWEEK(r.created_at, 1) = YEARWEEK(CURDATE(), 1)',
    'month' => 'YEAR(r.created_at)=YEAR(CURDATE()) AND MONTH(r.created_at)=MONTH(CURDATE())',
    'year' => 'YEAR(r.created_at)=YEAR(CURDATE())',
    default => '1=1'
};
// Optional filters: customer type and color mode (validated against whitelists).
$typeFilter  = isset(USER_TYPES[$_GET['user_type'] ?? '']) ? $_GET['user_type'] : '';
$colorFilter = isset(COLOR_MODES[$_GET['color'] ?? '']) ? $_GET['color'] : '';
$params = [];
if ($typeFilter !== '')  { $where .= ' AND r.user_type = ?';  $params[] = $typeFilter; }
if ($colorFilter !== '') { $where .= ' AND r.color_mode = ?'; $params[] = $colorFilter; }
$sql = "SELECT u.username, r.user_type, p.printer_name, r.job_type, r.color_mode, r.copy_count, r.notes, r.created_at, a.original_name, a.id AS attachment_id FROM print_records r JOIN users u ON u.id=r.user_id JOIN printers p ON p.id=r.printer_id LEFT JOIN attachments a ON a.id=r.attachment_id WHERE $where ORDER BY r.created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll();
$topSql = "SELECT u.username, u.user_type, SUM(r.copy_count) AS total FROM print_records r JOIN users u ON u.id=r.user_id WHERE $where GROUP BY u.id,u.username,u.user_type ORDER BY total DESC LIMIT 10";
$stmt = $pdo->prepare($topSql); $stmt->execute($params); $top = $stmt->fetchAll();
$total = array_sum(array_map(fn($r)=>(int)$r['copy_count'], $rows));
$colorTotal = array_sum(array_map(fn($r)=>$r['color_mode']==='color' ? (int)$r['copy_count'] : 0, $rows));
$bwTotal    = array_sum(array_map(fn($r)=>$r['color_mode']==='bw' ? (int)$r['copy_count'] : 0, $rows));
$typeTotals = array_fill_keys(array_keys(USER_TYPES), 0);
foreach ($rows as $r) { $typeTotals[$r['user_type']] = ($typeTotals[$r['user_type']] ?? 0) + (int)$r['copy_count']; }
$qs = fn(array $o) => '?' . http_build_query(array_filter(array_merge(['period'=>$period,'user_type'=>$typeFilter,'color'=>$colorFilter], $o), fn($v)=>$v !== ''));
$labels = ['day'=>'Daily','week'=>'Weekly','month'=>'Monthly','year'=>'Yearly','all'=>'All time'];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reports</title><link rel="stylesheet" href="style.css"></head><body>
<?php include __DIR__ . '/nav.php'; ?><main class="container"><div class="panel-head"><div><h1><?=h($labels[$period])?> Report</h1><p class="muted">Total recorded pages/copies: <strong><?=number_format($total)?></strong></p></div><button class="button" onclick="window.print()">Print report</button></div>
<div class="filter-row"><?php foreach($labels as $k=>$label): ?><a class="filter <?= $period===$k?'active':'' ?>" href="<?=h($qs(['period'=>$k]))?>"><?=h($label)?></a><?php endforeach; ?></div>
<form class="panel filter-form" method="get"><input type="hidden" name="period" value="<?=h($period)?>">
<label>Customer type<select name="user_type"><option value="">All types</option><?php foreach(USER_TYPES as $k=>$label): ?><option value="<?=h($k)?>" <?=$typeFilter===$k?'selected':''?>><?=h($label)?></option><?php endforeach; ?></select></label>
<label>Color mode<select name="color"><option value="">Color + B/W</option><?php foreach(COLOR_MODES as $k=>$label): ?><option value="<?=h($k)?>" <?=$colorFilter===$k?'selected':''?>><?=h($label)?></option><?php endforeach; ?></select></label>
<button class="primary" type="submit">Apply</button><a class="button ghost" href="<?=h($qs(['user_type'=>'','color'=>'']))?>">Reset</a></form>
<section class="stats">
<div class="stat"><span>Color pages</span><strong><?=number_format($colorTotal)?></strong></div>
<div class="stat"><span>B/W pages</span><strong><?=number_format($bwTotal)?></strong></div>
<?php foreach (['teacher','ta'] as $k): ?><div class="stat"><span><?=h(USER_TYPES[$k])?> total</span><strong><?=number_format($typeTotals[$k])?></strong></div><?php endforeach; ?>
</section>
<p class="muted">Student total: <strong><?=number_format($typeTotals['student'])?></strong> &middot; Scan jobs are counted in totals but have no color mode.</p>
<div class="panel"><h2>Top users by copies</h2><div class="table-wrap"><table><thead><tr><th>Rank</th><th>Username</th><th>Type</th><th>Total pages/copies</th></tr></thead><tbody><?php foreach($top as $i=>$t): ?><tr><td><?=$i+1?></td><td><?=h($t['username'])?></td><td><span class="badge badge-<?=h($t['user_type'])?>"><?=h(user_type_label($t['user_type']))?></span></td><td><?=number_format((int)$t['total'])?></td></tr><?php endforeach; ?><?php if(!$top): ?><tr><td colspan="4">No data for this period.</td></tr><?php endif; ?></tbody></table></div></div>
<div class="panel"><h2>Detailed records</h2><div class="table-wrap"><table><thead><tr><th>Date/time</th><th>User</th><th>Customer type</th><th>Printer</th><th>Job</th><th>Color</th><th>Count</th><th>Attachment</th><th>Notes</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?=h($r['created_at'])?></td><td><?=h($r['username'])?></td><td><span class="badge badge-<?=h($r['user_type'])?>"><?=h(user_type_label($r['user_type']))?></span></td><td><?=h($r['printer_name'])?></td><td><?=h(JOB_TYPES[$r['job_type']] ?? $r['job_type'])?></td><td><?php if($r['color_mode']): ?><span class="badge badge-<?=h($r['color_mode'])?>"><?=h(color_label($r['color_mode']))?></span><?php else: ?>-<?php endif; ?></td><td><?=number_format((int)$r['copy_count'])?></td><td><?=h($r['original_name'] ?? '')?></td><td><?=h($r['notes'])?></td></tr><?php endforeach; ?><?php if(!$rows): ?><tr><td colspan="9">No records for this period.</td></tr><?php endif; ?></tbody></table></div></div>
</main></body></html>
