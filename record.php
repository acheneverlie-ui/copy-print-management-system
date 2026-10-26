<?php
require __DIR__ . '/config.php';
require_login();
$pdo = db();
$error = '';
$success = '';
$printers = $pdo->query('SELECT id, printer_name, printer_code FROM printers WHERE is_active=1 ORDER BY printer_name')->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $printerId = filter_input(INPUT_POST, 'printer_id', FILTER_VALIDATE_INT);
    $count = filter_input(INPUT_POST, 'copy_count', FILTER_VALIDATE_INT);
    $jobType = $_POST['job_type'] ?? 'copy';
    $notes = trim($_POST['notes'] ?? '');
    $colorMode = $_POST['color_mode'] ?? '';
    if (!isset(JOB_TYPES[$jobType])) $jobType = '';
    // Colour / B&W applies to copy and print jobs only; scans store NULL.
    $needsColor = in_array($jobType, ['copy', 'print'], true);
    if (!$needsColor) $colorMode = null;
    $ut = $pdo->prepare('SELECT user_type FROM users WHERE id = ?');
    $ut->execute([$_SESSION['user']['id']]);
    $userType = $ut->fetchColumn() ?: 'student';
    if (!$printerId || !$count || $count < 1 || $count > 1000000 || $jobType === '') {
        $error = 'Please enter a valid printer, job type, and copy count.';
    } elseif ($needsColor && !isset(COLOR_MODES[$colorMode])) {
        $error = 'Please choose Color or B/W for copy and print jobs.';
    } else {
        $attachmentId = null;
        $pdo->beginTransaction();
        try {
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
                $f = $_FILES['attachment'];
                if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed.');
                if ($f['size'] > MAX_UPLOAD_BYTES) throw new RuntimeException('File is larger than 10 MB.');
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($f['tmp_name']);
                $allowed = ['application/pdf'=>'pdf', 'image/png'=>'png', 'image/jpeg'=>'jpg'];
                if (!isset($allowed[$mime])) throw new RuntimeException('Only PDF, PNG, JPG, and JPEG files are allowed.');
                if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0750, true)) throw new RuntimeException('Could not create upload folder.');
                $safeName = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
                if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . $safeName)) throw new RuntimeException('Could not save uploaded file.');
                $stmt = $pdo->prepare('INSERT INTO attachments (original_name, stored_name, mime_type, file_size, uploaded_by) VALUES (?,?,?,?,?)');
                $stmt->execute([basename($f['name']), $safeName, $mime, (int)$f['size'], $_SESSION['user']['id']]);
                $attachmentId = (int)$pdo->lastInsertId();
            }
            $stmt = $pdo->prepare('INSERT INTO print_records (user_id, printer_id, job_type, color_mode, user_type, copy_count, notes, attachment_id) VALUES (?,?,?,?,?,?,?,?)');
            $stmt->execute([$_SESSION['user']['id'], $printerId, $jobType, $colorMode, $userType, $count, $notes, $attachmentId]);
            $pdo->commit();
            $success = 'Record saved successfully.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = $e instanceof RuntimeException ? $e->getMessage() : 'Could not save the record. Check server logs.';
        }
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Add record</title><link rel="stylesheet" href="style.css"></head><body>
<?php include __DIR__ . '/nav.php'; ?><main class="container narrow"><h1>Add Copy/Print Record</h1>
<?php if ($error): ?><div class="alert"><?=h($error)?></div><?php endif; ?><?php if ($success): ?><div class="success"><?=h($success)?></div><?php endif; ?>
<form class="panel form-grid" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>">
<label>Printer / Machine ID<select name="printer_id" required><option value="">Choose printer</option><?php foreach($printers as $p): ?><option value="<?=$p['id']?>"><?=h($p['printer_name'])?> — <?=h($p['printer_code'])?></option><?php endforeach; ?></select></label>
<label>Job type<select name="job_type" id="job_type"><?php foreach(JOB_TYPES as $k=>$label): ?><option value="<?=h($k)?>" <?= (($_POST['job_type'] ?? 'copy')===$k)?'selected':'' ?>><?=h($label)?></option><?php endforeach; ?></select></label>
<label id="color_wrap">Color mode<select name="color_mode" id="color_mode"><?php foreach(COLOR_MODES as $k=>$label): ?><option value="<?=h($k)?>" <?= (($_POST['color_mode'] ?? 'bw')===$k)?'selected':'' ?>><?=h($label)?></option><?php endforeach; ?></select></label>
<label>Number of pages/copies<input type="number" name="copy_count" min="1" max="1000000" required></label>
<label>Attach file (PDF, PNG, JPG/JPEG; max 10 MB)<input type="file" name="attachment" accept=".pdf,.png,.jpg,.jpeg,application/pdf,image/png,image/jpeg"></label>
<label class="full">Notes<textarea name="notes" rows="3" maxlength="1000"></textarea></label>
<div class="full"><button class="primary" type="submit">Save record</button></div></form></main>
<script>
// Color mode only applies to copy/print jobs; hide and disable it for scans.
(function(){
  var job = document.getElementById('job_type'), wrap = document.getElementById('color_wrap'), sel = document.getElementById('color_mode');
  function sync(){ var on = job.value !== 'scan'; wrap.style.display = on ? '' : 'none'; sel.disabled = !on; }
  job.addEventListener('change', sync); sync();
})();
</script></body></html>
