<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Patient Lookup | CareSystem</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
	<link rel="stylesheet" href="assets/icons/css/all.min.css" />
	<link rel="stylesheet" href="assets/css/adminlte.min.css" />
	<link rel="stylesheet" href="assets/css/theme.css" />
	<style>
		/* ── Sticky search bar ────────────────────────────────────────────────── */
		/* Sticks inside .content-wrapper (the AdminLTE scroll container).        */
		.search-sticky {
			position: sticky;
			top: 0;
			z-index: 900;
			border-radius: 0;
			border-left: none;
			border-right: none;
			border-top: none;
			box-shadow: 0 3px 10px rgba(0, 0, 0, .12);
			margin-left: -15px;   /* bleed to container edge */
			margin-right: -15px;
			padding-left: 15px;
			padding-right: 15px;
		}

		/* ── Result rows ─────────────────────────────────────────────────────── */
		.result-row { cursor: pointer; transition: background .1s; }
		.result-row:focus { outline: none; }
		.result-row.kb-active td { background: rgba(0, 123, 255, .08) !important; }

		/* ── Allergy badge ───────────────────────────────────────────────────── */
		.allergy-flag {
			display: inline-block;
			font-size: .7rem;
			font-weight: 700;
			padding: 1px 5px;
			border-radius: 3px;
			background: #dc3545;
			color: #fff;
			vertical-align: middle;
			cursor: default;
		}

		/* ── Blood group badge ───────────────────────────────────────────────── */
		.blood-badge {
			font-size: .75rem;
			font-weight: 700;
			letter-spacing: .02em;
			color: #c0392b;
		}

		/* ── Result count badge ──────────────────────────────────────────────── */
		.result-meta {
			font-size: .8rem;
			color: #6c757d;
			border-bottom: 1px solid rgba(0,0,0,.06);
			padding: 6px 16px;
		}

		/* ── Empty / loading states ──────────────────────────────────────────── */
		.state-panel { padding: 3rem 1rem; text-align: center; color: #adb5bd; }
		.state-panel i { font-size: 2.5rem; margin-bottom: .75rem; display: block; }
		.state-panel p { margin: 0; }

		/* ── Responsive: hide less-critical columns on small screens ─────────── */
		@media (max-width: 767px) {
			.col-phone, .col-city { display: none; }
		}
	</style>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed<?= ($_SESSION['font_size'] ?? 'normal') === 'large' ? ' font-size-large' : '' ?>"
      data-theme-server="<?= htmlspecialchars($_SESSION['theme'] ?? 'system') ?>">
<div class="wrapper">

	<nav class="main-header navbar navbar-expand navbar-white navbar-light">
		<ul class="navbar-nav">
			<li class="nav-item">
				<a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="Toggle sidebar">
					<i class="fas fa-bars"></i>
				</a>
			</li>
			<li class="nav-item d-none d-sm-inline-block">
				<span class="navbar-brand mb-0 h6 text-primary">CareSystem</span>
			</li>
		</ul>
		<ul class="navbar-nav ml-auto">
			<li class="nav-item d-flex align-items-center">
				<button id="gearBtn" aria-label="Display settings" title="Display settings">
					<i class="fas fa-cog fa-lg"></i>
				</button>
			</li>
			<li class="nav-item">
				<a class="btn btn-sm btn-outline-secondary" href="dashboard.php" role="button">
					<i class="fas fa-arrow-left mr-1"></i>Dashboard
				</a>
			</li>
		</ul>
	</nav>

	<!-- Slide-down display settings panel -->
	<div id="settingsPanel" role="dialog" aria-label="Display settings">
		<span class="panel-label">Display settings</span>
		<div class="custom-control custom-switch mb-3">
			<input type="checkbox" class="custom-control-input" id="themeTogglePanel" data-theme-toggle />
			<label class="custom-control-label" for="themeTogglePanel">Dark mode</label>
		</div>
		<div>
			<span class="panel-label">Language</span>
			<div class="btn-group lang-btn-group" role="group" aria-label="Language">
				<button type="button" class="btn btn-sm <?= ($_SESSION['language'] ?? 'en') === 'en' ? 'btn-primary' : 'btn-outline-secondary' ?>" data-lang="en">English</button>
				<button type="button" class="btn btn-sm <?= ($_SESSION['language'] ?? 'en') === 'te' ? 'btn-primary' : 'btn-outline-secondary' ?>" data-lang="te">తెలుగు</button>
			</div>
		</div>
		<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
	</div>

	<?php require __DIR__ . '/_sidebar.php'; ?>

	<div class="content-wrapper">

		<!-- ── Sticky search card ──────────────────────────────────────────── -->
		<div class="card mb-0 search-sticky">
			<div class="card-body py-3">

				<?php if ($flashError !== null): ?>
				<div class="alert alert-danger alert-dismissible py-2 mb-3" role="alert">
					<i class="fas fa-exclamation-circle mr-1"></i><?= htmlspecialchars($flashError) ?>
					<button type="button" class="close py-2" data-dismiss="alert" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<?php endif; ?>

				<div class="row align-items-end">
					<!-- Page title -->
					<div class="col-auto d-none d-md-block pr-4" style="border-right:1px solid #dee2e6">
						<h5 class="mb-0 text-dark">
							<i class="fas fa-user-injured mr-1 text-primary"></i>
							Patient Lookup
						</h5>
						<small class="text-muted">Search by name and / or D.O.B.</small>
					</div>

					<!-- Search form -->
					<div class="col">
						<form id="patientSearchForm" autocomplete="off">
							<div class="form-row align-items-end">

								<!-- Name field -->
								<div class="col-md-5 col-sm-6 mb-2 mb-md-0">
									<label for="searchName" class="sr-only">Patient Name</label>
									<div class="input-group">
										<div class="input-group-prepend">
											<span class="input-group-text"><i class="fas fa-user"></i></span>
										</div>
										<input type="text" id="searchName" name="name"
										       class="form-control"
										       placeholder="Patient name&hellip;"
										       autocomplete="off"
										       spellcheck="false" />
									</div>
								</div>

								<!-- DOB field -->
								<div class="col-md-3 col-sm-6 mb-2 mb-md-0">
									<label for="searchDob" class="sr-only">Date of Birth</label>
									<div class="input-group">
										<div class="input-group-prepend">
											<span class="input-group-text"><i class="fas fa-birthday-cake"></i></span>
										</div>
										<input type="date" id="searchDob" name="dob"
										       class="form-control"
										       title="Date of birth" />
									</div>
								</div>

								<!-- Action buttons -->
								<div class="col-auto mb-2 mb-md-0">
									<button type="submit" class="btn btn-primary" id="searchBtn">
										<i class="fas fa-search mr-1"></i>Search
									</button>
									<button type="button" class="btn btn-outline-secondary ml-1" id="clearBtn"
									        title="Clear search">
										<i class="fas fa-times"></i>
									</button>
								</div>

								<!-- Live status indicator -->
								<div class="col-auto mb-2 mb-md-0 pl-1">
									<span id="searchStatus" class="text-muted small" style="white-space:nowrap"></span>
								</div>

							</div><!-- /form-row -->

							<div class="mt-1">
								<small class="text-muted">
									Enter a name (min. 2 characters), a date of birth, or both together.&ensp;
									Results update as you type.&ensp;
									Use <kbd>&darr;</kbd> / <kbd>&uarr;</kbd> to navigate results, <kbd>Enter</kbd> to open.
								</small>
							</div>
						</form>
					</div>
				</div><!-- /row -->

			</div>
		</div><!-- /search card -->

		<!-- ── Results area ────────────────────────────────────────────────── -->
		<section class="content pt-3">
			<div class="container-fluid">
				<div class="card" id="resultsCard">
					<div id="searchResults">

						<!-- Initial idle state -->
						<div class="state-panel" id="stateIdle">
							<i class="fas fa-user-injured"></i>
							<p>Enter a patient name or date of birth above to begin searching.</p>
						</div>

					</div>
				</div>
			</div>
		</section>

	</div><!-- /content-wrapper -->

	<footer class="main-footer">
		<strong>D3S3 CareSystem</strong>
	</footer>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme-toggle.js"></script>
<script>
$(function () {

	/* ── DOM refs ────────────────────────────────────────────────────────── */
	var $nameInput  = $('#searchName');
	var $dobInput   = $('#searchDob');
	var $results    = $('#searchResults');
	var $status     = $('#searchStatus');
	var $clearBtn   = $('#clearBtn');
	var $searchBtn  = $('#searchBtn');

	/* ── State ───────────────────────────────────────────────────────────── */
	var searchTimer  = null;
	var activeRow    = -1;   // keyboard-nav index (-1 = none highlighted)
	var currentXhr   = null; // in-flight AJAX request

	/* ── Pre-fill from URL params and auto-search (restores results after  ── */
	/* ── hitting Back from a patient profile page)                         ── */
	(function () {
		var urlParams = new URLSearchParams(window.location.search);
		var prefilledName = urlParams.get('name') || '';
		var prefilledDob  = urlParams.get('dob')  || '';
		if (prefilledName) $nameInput.val(prefilledName);
		if (prefilledDob)  $dobInput.val(prefilledDob);
		if (prefilledName || prefilledDob) {
			runSearch();
		}
	}());

	/* ── Auto-focus name field ───────────────────────────────────────────── */
	$nameInput.focus();

	/* ── Debounced live search on keystroke ──────────────────────────────── */
	$nameInput.on('input', function () {
		clearTimeout(searchTimer);
		searchTimer = setTimeout(runSearch, 350);
	});

	$dobInput.on('change', function () {
		clearTimeout(searchTimer);
		runSearch();
	});

	/* ── Immediate search on form submit ─────────────────────────────────── */
	$('#patientSearchForm').on('submit', function (e) {
		e.preventDefault();
		clearTimeout(searchTimer);
		runSearch();
	});

	/* ── Clear button ────────────────────────────────────────────────────── */
	$clearBtn.on('click', function () {
		$nameInput.val('');
		$dobInput.val('');
		activeRow = -1;
		$status.text('');
		showIdle();
		$nameInput.focus();
	});

	/* ── Keyboard navigation from input fields ───────────────────────────── */
	$nameInput.add($dobInput).on('keydown', function (e) {
		var $rows = $results.find('.result-row');
		if (!$rows.length) return;

		if (e.key === 'ArrowDown') {
			e.preventDefault();
			activeRow = Math.min(activeRow + 1, $rows.length - 1);
			highlightRow($rows);
		} else if (e.key === 'ArrowUp') {
			e.preventDefault();
			activeRow = Math.max(activeRow - 1, -1);
			if (activeRow === -1) {
				$rows.removeClass('kb-active');
			} else {
				highlightRow($rows);
			}
		} else if (e.key === 'Enter' && activeRow >= 0) {
			e.preventDefault();
			navigateTo($rows.eq(activeRow).data('href'));
		}
	});

	/* ── Click anywhere on a result row to open the record ──────────────── */
	$(document).on('click', '.result-row', function () {
		navigateTo($(this).data('href'));
	});

	/* ── Core search function ────────────────────────────────────────────── */
	function runSearch() {
		var name = $nameInput.val().trim();
		var dob  = $dobInput.val().trim();

		// Need at least one criterion, and name must be ≥2 chars if it's all we have
		if (name === '' && dob === '') { showIdle(); return; }
		if (name !== '' && name.length < 2 && dob === '') {
			$status.text('Type at least 2 characters…');
			return;
		}

		// Cancel any in-flight request
		if (currentXhr) { currentXhr.abort(); }

		activeRow = -1;
		showLoading();

		currentXhr = $.getJSON('patients.php', { action: 'search', name: name, dob: dob })
			.done(function (data) {
				renderResults(data, name, dob);
			})
			.fail(function (xhr) {
				if (xhr.statusText === 'abort') return; // cancelled — not an error
				showError('Search failed. Please try again.');
			})
			.always(function () { currentXhr = null; });
	}

	/* ── Render functions ────────────────────────────────────────────────── */

	function showIdle() {
		$results.html(
			'<div class="state-panel" id="stateIdle">' +
			'<i class="fas fa-user-injured"></i>' +
			'<p>Enter a patient name or date of birth above to begin searching.</p>' +
			'</div>'
		);
		$status.text('');
	}

	function showLoading() {
		$results.html(
			'<div class="state-panel">' +
			'<i class="fas fa-spinner fa-spin"></i>' +
			'<p>Searching&hellip;</p>' +
			'</div>'
		);
		$status.text('');
	}

	function showError(msg) {
		$results.html(
			'<div class="alert alert-danger m-3">' +
			'<i class="fas fa-exclamation-circle mr-1"></i>' + esc(msg) +
			'</div>'
		);
		$status.text('');
	}

	function renderResults(patients, name, dob) {
		var count = patients.length;

		if (count === 0) {
			var hint = '';
			if (name && !dob)  hint = 'Try adding a date of birth to narrow the search.';
			if (dob  && !name) hint = 'Try adding part of the patient\u2019s name.';

			$results.html(
				'<div class="state-panel">' +
				'<i class="fas fa-user-slash"></i>' +
				'<p>No patients found.' + (hint ? '<br><small class="text-muted">' + esc(hint) + '</small>' : '') + '</p>' +
				'</div>'
			);
			$status.text('');
			return;
		}

		/* ── Status line ─────────────────────────────── */
		var capMsg = count >= 50
			? count + ' results (max shown \u2014 add D.O.B. to narrow)'
			: count + ' patient' + (count !== 1 ? 's' : '') + ' found';
		$status.text(capMsg);

		/* ── Result meta bar ─────────────────────────── */
		var html = '<div class="result-meta">' + esc(capMsg) + '</div>';

		/* ── Table ───────────────────────────────────── */
		html += '<div class="table-responsive">';
		html += '<table class="table table-hover table-sm mb-0" id="resultsTable">';
		html += '<thead class="thead-light">';
		html += '<tr>' +
		        '<th class="text-nowrap">Code</th>' +
		        '<th>Name</th>' +
		        '<th class="text-nowrap">Date of Birth</th>' +
		        '<th>Sex</th>' +
		        '<th class="text-nowrap">Blood Gp.</th>' +
		        '<th class="col-phone text-nowrap">Phone</th>' +
		        '<th class="col-city">City</th>' +
		        '<th class="text-nowrap">Last Visit</th>' +
		        '<th class="text-center" title="Total case sheets">Visits</th>' +
		        '<th></th>' +
		        '</tr>';
		html += '</thead><tbody>';

		patients.forEach(function (p, idx) {
			/* Include search params so the profile page Back button restores results. */
			var href = 'patients.php?action=view&id=' + p.patient_id;
			if (name) href += '&name=' + encodeURIComponent(name);
			if (dob)  href += '&dob='  + encodeURIComponent(dob);
			var fullName = esc(p.first_name + (p.last_name ? ' ' + p.last_name : ''));

			/* Allergy warning pill */
			var allergyHtml = '';
			if (p.allergies) {
				allergyHtml = ' <span class="allergy-flag" title="Allergies: ' + esc(p.allergies) + '">ALG</span>';
			}

			/* Inactive badge */
			var inactiveBadge = (String(p.is_active) === '0')
				? ' <span class="badge badge-secondary" style="font-size:.65rem">Inactive</span>'
				: '';

			/* Blood group */
			var bloodHtml = p.blood_group
				? '<span class="blood-badge">' + esc(p.blood_group) + '</span>'
				: '<span class="text-muted">&mdash;</span>';

			/* DOB + age */
			var dobHtml = '&mdash;';
			if (p.date_of_birth) {
				dobHtml = fmtDate(p.date_of_birth);
				if (p.age_years) {
					dobHtml += ' <small class="text-muted">(' + p.age_years + ' yrs)</small>';
				}
			} else if (p.age_years) {
				dobHtml = '<span class="text-muted">~' + p.age_years + ' yrs</span>';
			}

			/* Last visit */
			var lastVisit = p.last_visit
				? fmtDate(p.last_visit.substring(0, 10))
				: '<span class="text-muted">&mdash;</span>';

			/* Sex */
			var sexMap = { MALE: 'M', FEMALE: 'F', OTHER: 'O', UNKNOWN: '?' };
			var sexHtml = esc(sexMap[p.sex] || p.sex || '?');

			html += '<tr class="result-row" tabindex="0"' +
			        '    data-idx="' + idx + '"' +
			        '    data-href="' + href + '">' +
			        '<td class="text-monospace text-muted small text-nowrap">' + esc(p.patient_code) + '</td>' +
			        '<td class="font-weight-semibold">' + fullName + inactiveBadge + allergyHtml + '</td>' +
			        '<td class="text-nowrap">' + dobHtml + '</td>' +
			        '<td>' + sexHtml + '</td>' +
			        '<td>' + bloodHtml + '</td>' +
			        '<td class="col-phone small text-nowrap">' + esc(p.phone_e164 || '\u2014') + '</td>' +
			        '<td class="col-city small">' + esc(p.city || '\u2014') + '</td>' +
			        '<td class="small text-nowrap">' + lastVisit + '</td>' +
			        '<td class="text-center">' + (parseInt(p.visit_count) || 0) + '</td>' +
			        '<td>' +
			        '<a href="' + href + '" class="btn btn-sm btn-outline-primary text-nowrap" tabindex="-1">' +
			        '<i class="fas fa-folder-open mr-1"></i>Open</a>' +
			        '</td>' +
			        '</tr>';
		});

		html += '</tbody></table></div>';
		$results.html(html);

		/* Enable Bootstrap tooltips for allergy pills */
		$results.find('[title]').tooltip({ trigger: 'hover', placement: 'top' });
	}

	/* ── Keyboard helpers ────────────────────────────────────────────────── */

	function highlightRow($rows) {
		$rows.removeClass('kb-active').removeAttr('aria-selected');
		if (activeRow >= 0 && activeRow < $rows.length) {
			var $r = $rows.eq(activeRow);
			$r.addClass('kb-active').attr('aria-selected', 'true');
			// Scroll the row into view within the content-wrapper
			var $cw   = $('.content-wrapper');
			var rTop  = $r.offset().top;
			var cwTop = $cw.offset().top;
			var cwH   = $cw.height();
			if (rTop < cwTop + 10 || rTop > cwTop + cwH - 60) {
				$cw.scrollTop($cw.scrollTop() + (rTop - cwTop) - 120);
			}
		}
	}

	/* ── Navigation helper ───────────────────────────────────────────────── */
	function navigateTo(href) {
		if (href) window.location.href = href;
	}

	/* ── Utility: HTML-escape a value ────────────────────────────────────── */
	function esc(str) {
		return String(str)
			.replace(/&/g,  '&amp;')
			.replace(/</g,  '&lt;')
			.replace(/>/g,  '&gt;')
			.replace(/"/g,  '&quot;')
			.replace(/'/g,  '&#039;');
	}

	/* ── Utility: format YYYY-MM-DD → DD Mon YYYY ────────────────────────── */
	function fmtDate(str) {
		if (!str) return '\u2014';
		var months = ['Jan','Feb','Mar','Apr','May','Jun',
		              'Jul','Aug','Sep','Oct','Nov','Dec'];
		var parts = str.split('-');
		if (parts.length !== 3) return esc(str);
		var d = parseInt(parts[2], 10);
		var m = months[parseInt(parts[1], 10) - 1] || parts[1];
		return d + '&nbsp;' + m + '&nbsp;' + parts[0];
	}

});
</script>
</body>
</html>
