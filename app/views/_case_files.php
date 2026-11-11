<?php
/**
 * One case-file panel. Set $caseFileKind, $caseFileCaseId, $caseFilePatientId,
 * $caseFileTitle, and $caseFileMode (image, file, or record) before including.
 */
$caseFileKind = $caseFileKind ?? '';
$caseFileCaseId = (int)($caseFileCaseId ?? 0);
$caseFilePatientId = (int)($caseFilePatientId ?? 0);
$caseFileTitle = $caseFileTitle ?? 'File';
$caseFileMode = $caseFileMode ?? 'file';
$caseFileNote = $caseFileNote ?? '';
$caseFileCsrf = $_SESSION['csrf_token'] ?? '';

$caseFileRows = [];
try {
	$cfPdo = getDBConnection();
	if ($caseFileCaseId > 0) {
		$cfStmt = $cfPdo->prepare('SELECT * FROM case_attachments WHERE case_sheet_id = ? AND kind = ? ORDER BY attachment_id DESC');
		$cfStmt->execute([$caseFileCaseId, $caseFileKind]);
	} else {
		$cfStmt = $cfPdo->prepare('SELECT * FROM case_attachments WHERE patient_id = ? AND kind = ? ORDER BY attachment_id DESC');
		$cfStmt->execute([$caseFilePatientId, $caseFileKind]);
	}
	$caseFileRows = $cfStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
	$caseFileRows = [];
}
$caseFileLocked = false;
foreach ($caseFileRows as $cfLocked) {
	if ((int)$cfLocked['locked'] === 1) {
		$caseFileLocked = true;
		break;
	}
}
$panelId = 'cf_' . preg_replace('/[^a-z0-9_]/', '', $caseFileKind) . '_' . $caseFileCaseId;
?>
<div class="card card-outline card-secondary mb-3" id="<?= htmlspecialchars($panelId) ?>">
	<div class="card-header">
		<h5 class="card-title mb-0"><?= htmlspecialchars($caseFileTitle) ?></h5>
	</div>
	<div class="card-body">
		<?php if ($caseFileNote !== ''): ?>
		<p class="text-muted small"><?= htmlspecialchars($caseFileNote) ?></p>
		<?php endif; ?>
		<div class="case-file-list mb-2">
			<?php if (empty($caseFileRows)): ?>
			<p class="text-muted mb-2 case-file-empty">None saved yet.</p>
			<?php endif; ?>
			<?php foreach ($caseFileRows as $cf): ?>
			<div class="mb-2">
				<a href="case_file.php?action=view&amp;id=<?= (int)$cf['attachment_id'] ?>" target="_blank" rel="noopener">
					<?= htmlspecialchars($cf['original_name']) ?>
				</a>
				<small class="text-muted ml-2"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($cf['created_at']))) ?></small>
				<?php if ((int)$cf['locked'] === 1): ?>
				<span class="badge badge-dark ml-1">Locked</span>
				<?php endif; ?>
				<?php if (str_starts_with((string)$cf['mime_type'], 'audio/')): ?>
				<audio class="d-block mt-1" controls preload="none" src="case_file.php?action=view&amp;id=<?= (int)$cf['attachment_id'] ?>"></audio>
				<?php elseif (str_starts_with((string)$cf['mime_type'], 'image/')): ?>
				<img class="img-thumbnail d-block mt-1" style="max-height:160px" alt="" src="case_file.php?action=view&amp;id=<?= (int)$cf['attachment_id'] ?>" />
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>
		<div class="case-file-alert alert alert-danger d-none py-2"></div>
		<?php if ($caseFileMode === 'record' && $caseFileLocked): ?>
		<p class="text-muted small mb-0">This recording is locked and cannot be replaced.</p>
		<?php elseif ($caseFileMode === 'record'): ?>
		<button type="button" class="btn btn-sm btn-outline-danger case-file-record">
			<i class="fas fa-microphone mr-1"></i>Record
		</button>
		<button type="button" class="btn btn-sm btn-danger d-none case-file-stop">
			<i class="fas fa-stop mr-1"></i>Stop and save
		</button>
		<p class="text-muted small mt-2 mb-0">A saved recording is locked. It cannot be edited or replaced.</p>
		<?php else: ?>
		<div class="form-inline">
			<input type="file" class="form-control-file case-file-input" accept="<?= $caseFileMode === 'image' ? 'image/*,.pdf' : '.pdf,image/*' ?>" />
			<button type="button" class="btn btn-sm btn-primary ml-2 case-file-upload">Upload</button>
		</div>
		<?php endif; ?>
	</div>
</div>
<script>
(function () {
	var panel = document.getElementById(<?= json_encode($panelId) ?>);
	if (!panel || panel.dataset.wired === '1') return;
	panel.dataset.wired = '1';
	var alertBox = panel.querySelector('.case-file-alert');
	function showError(msg) {
		alertBox.textContent = msg;
		alertBox.classList.remove('d-none');
	}
	function postFile(file) {
		var data = new FormData();
		data.append('csrf_token', <?= json_encode($caseFileCsrf) ?>);
		data.append('kind', <?= json_encode($caseFileKind) ?>);
		data.append('case_sheet_id', <?= json_encode((string)$caseFileCaseId) ?>);
		data.append('patient_id', <?= json_encode((string)$caseFilePatientId) ?>);
		data.append('file', file, file.name || 'recording.webm');
		alertBox.classList.add('d-none');
		fetch('case_file.php?action=upload', { method: 'POST', body: data, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (body) {
				if (!body.success) { showError(body.message || 'Save failed.'); return; }
				window.location.reload();
			})
			.catch(function () { showError('Save failed.'); });
	}
	var uploadBtn = panel.querySelector('.case-file-upload');
	if (uploadBtn) {
		uploadBtn.addEventListener('click', function () {
			var input = panel.querySelector('.case-file-input');
			if (!input.files || !input.files[0]) { showError('Choose a file first.'); return; }
			postFile(input.files[0]);
		});
	}
	var recBtn = panel.querySelector('.case-file-record');
	var stopBtn = panel.querySelector('.case-file-stop');
	if (recBtn && navigator.mediaDevices) {
		var recorder = null;
		var chunks = [];
		recBtn.addEventListener('click', function () {
			navigator.mediaDevices.getUserMedia({ audio: true }).then(function (stream) {
				chunks = [];
				recorder = new MediaRecorder(stream);
				recorder.ondataavailable = function (e) { if (e.data.size) chunks.push(e.data); };
				recorder.onstop = function () {
					stream.getTracks().forEach(function (t) { t.stop(); });
					postFile(new File(chunks, 'voice-recording.webm', { type: recorder.mimeType || 'audio/webm' }));
				};
				recorder.start();
				recBtn.classList.add('d-none');
				stopBtn.classList.remove('d-none');
			}).catch(function () { showError('The microphone is not available.'); });
		});
		stopBtn.addEventListener('click', function () {
			if (recorder && recorder.state !== 'inactive') recorder.stop();
		});
	}
})();
</script>
