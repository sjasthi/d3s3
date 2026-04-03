<?php
/**
 * app/views/analytics.php
 *
 * Analytics & Reporting page shell.
 *
 * Tab content is loaded lazily via AJAX on first activation and cached
 * in JS — switching back to a tab does not re-fetch.
 * Changing the date range clears the cache and re-fetches the active tab.
 *
 * Variables injected by AnalyticsController::index():
 *   bool   $isAdmin
 *   bool   $canSeeCaseload
 *   bool   $canSeeOutcomes
 *   bool   $canSeeSatisfaction
 *   string $defaultFrom      e.g. '2026-04-01'
 *   string $defaultTo        e.g. '2026-04-03'
 *   string $spinnerHTML      shared loading spinner markup
 */

require_once __DIR__ . '/../config/lang.php';
load_language($_SESSION['language'] ?? 'en');
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Analytics | CareSystem</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
	<link rel="stylesheet" href="assets/icons/css/all.min.css" />
	<link rel="stylesheet" href="assets/css/adminlte.min.css" />
	<link rel="stylesheet" href="assets/css/theme.css" />
	<style>
	/* ── Date range bar ──────────────────────────────────────────────────────── */
	.analytics-date-bar {
		background: var(--surface-card);
		border: 1px solid var(--border-soft);
		border-radius: 8px;
		padding: .55rem 1rem;
		display: flex;
		align-items: center;
		flex-wrap: wrap;
		gap: .5rem;
	}
	.analytics-date-bar label {
		font-size: .82rem;
		font-weight: 600;
		color: var(--text-muted);
		margin: 0;
		white-space: nowrap;
	}
	.analytics-date-bar input[type="date"] {
		font-size: .85rem;
		padding: .25rem .5rem;
		border: 1px solid var(--border-soft);
		border-radius: 5px;
		background: var(--surface);
		color: var(--text-strong);
		height: 32px;
	}

	/* ── Tab navigation ──────────────────────────────────────────────────────── */
	.analytics-tabs {
		border-bottom: 2px solid var(--border-soft);
		margin-bottom: 0;
		flex-wrap: nowrap;
		overflow-x: auto;
		-webkit-overflow-scrolling: touch;
	}
	.analytics-tabs .nav-link {
		font-size: .88rem;
		font-weight: 600;
		color: var(--text-muted);
		border: none;
		border-bottom: 3px solid transparent;
		border-radius: 0;
		padding: .65rem 1.1rem;
		white-space: nowrap;
		margin-bottom: -2px;
		transition: color .15s, border-color .15s;
		background: transparent;
	}
	.analytics-tabs .nav-link:hover {
		color: var(--brand-primary);
		border-bottom-color: var(--border-soft);
	}
	.analytics-tabs .nav-link.active {
		color: var(--brand-primary);
		border-bottom-color: var(--brand-primary);
	}
	.analytics-tabs .nav-link i { margin-right: .35rem; }

	/* ── Tab pane ────────────────────────────────────────────────────────────── */
	.analytics-tab-pane { min-height: 320px; padding-top: 1.25rem; }

	/* ── Loading spinner ─────────────────────────────────────────────────────── */
	.analytics-spinner {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		min-height: 260px;
		color: var(--text-muted);
		gap: .75rem;
	}
	.analytics-spinner .spinner-border {
		width: 2.2rem;
		height: 2.2rem;
		border-width: .22em;
		color: var(--brand-primary);
	}
	.analytics-spinner span { font-size: .85rem; }

	/* ── Error state ─────────────────────────────────────────────────────────── */
	.analytics-error {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		min-height: 260px;
		color: var(--text-muted);
		gap: .5rem;
	}
	</style>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed<?= ($_SESSION['font_size'] ?? 'normal') === 'large' ? ' font-size-large' : '' ?>"
      data-theme-server="<?= htmlspecialchars($_SESSION['theme'] ?? 'system') ?>">
<div class="wrapper">

	<!-- ── Top navbar ───────────────────────────────────────────────────────── -->
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
				<a class="btn btn-sm btn-outline-secondary"
				   href="<?= $isAdmin ? 'admin.php' : 'dashboard.php' ?>"
				   role="button">
					<i class="fas fa-arrow-left mr-1"></i>
					<?= $isAdmin ? 'Admin Dashboard' : 'Dashboard' ?>
				</a>
			</li>
		</ul>
	</nav>

	<!-- ── Display settings panel ───────────────────────────────────────────── -->
	<div id="settingsPanel" role="dialog" aria-label="Display settings">
		<span class="panel-label">Display settings</span>
		<div class="custom-control custom-switch mb-3">
			<input type="checkbox" class="custom-control-input" id="themeTogglePanel" data-theme-toggle />
			<label class="custom-control-label" for="themeTogglePanel">Dark mode</label>
		</div>
		<div>
			<span class="panel-label">Language</span>
			<div class="btn-group lang-btn-group" role="group" aria-label="Language">
				<button type="button"
				        class="btn btn-sm <?= ($_SESSION['language'] ?? 'en') === 'en' ? 'btn-primary' : 'btn-outline-secondary' ?>"
				        data-lang="en">English</button>
				<button type="button"
				        class="btn btn-sm <?= ($_SESSION['language'] ?? 'en') === 'te' ? 'btn-primary' : 'btn-outline-secondary' ?>"
				        data-lang="te">తెలుగు</button>
			</div>
		</div>
		<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
	</div>

	<?php require __DIR__ . '/_sidebar.php'; ?>

	<!-- ── Main content ─────────────────────────────────────────────────────── -->
	<div class="content-wrapper">
		<div class="content-header">
			<div class="container-fluid">
				<div class="row align-items-center">
					<div class="col-sm-8">
						<h1 class="m-0 text-dark">Analytics</h1>
						<p class="text-muted mb-0">
							<?= $isAdmin
								? 'Clinic-wide reporting and performance metrics.'
								: 'Your personal performance metrics and clinic trends.' ?>
						</p>
					</div>
					<?php if (!$isAdmin): ?>
					<div class="col-sm-4 text-sm-right mt-2 mt-sm-0">
						<span class="badge badge-info" style="font-size:.78rem;padding:.35rem .65rem;">
							<i class="fas fa-user mr-1"></i>Showing your data only
						</span>
					</div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<section class="content">
			<div class="container-fluid">

				<!-- ── Date range bar ──────────────────────────────────────── -->
				<div class="analytics-date-bar mb-3">
					<label for="dateFrom">Date range</label>
					<input type="date" id="dateFrom"
					       value="<?= htmlspecialchars($defaultFrom) ?>"
					       max="<?= date('Y-m-d') ?>" />
					<span class="text-muted small">to</span>
					<input type="date" id="dateTo"
					       value="<?= htmlspecialchars($defaultTo) ?>"
					       max="<?= date('Y-m-d') ?>" />
					<button id="btnApplyRange" class="btn btn-sm btn-primary">
						<i class="fas fa-sync-alt mr-1"></i>Apply
					</button>
					<button id="btnThisMonth" class="btn btn-sm btn-outline-secondary">This month</button>
					<button id="btnLast30"    class="btn btn-sm btn-outline-secondary">Last 30 days</button>
					<button id="btnThisYear"  class="btn btn-sm btn-outline-secondary">This year</button>
				</div>

				<!-- ── Tab card ────────────────────────────────────────────── -->
				<div class="card shadow-sm">

					<div class="card-header border-0 p-0">
						<ul class="nav analytics-tabs" id="analyticsTabs" role="tablist">

							<!-- Overview – always visible -->
							<li class="nav-item">
								<a class="nav-link active"
								   id="tab-overview-lnk"
								   data-toggle="tab"
								   href="#pane-overview"
								   data-tab="overview"
								   role="tab"
								   aria-controls="pane-overview"
								   aria-selected="true">
									<i class="fas fa-tachometer-alt"></i>Overview
								</a>
							</li>

							<!-- Caseload – admin + all clinical roles -->
							<?php if ($canSeeCaseload): ?>
							<li class="nav-item">
								<a class="nav-link"
								   id="tab-caseload-lnk"
								   data-toggle="tab"
								   href="#pane-caseload"
								   data-tab="caseload"
								   role="tab"
								   aria-controls="pane-caseload"
								   aria-selected="false">
									<i class="fas fa-users"></i>Caseload
								</a>
							</li>
							<?php endif; ?>

							<!-- Outcomes – admin + doctor only -->
							<?php if ($canSeeOutcomes): ?>
							<li class="nav-item">
								<a class="nav-link"
								   id="tab-outcomes-lnk"
								   data-toggle="tab"
								   href="#pane-outcomes"
								   data-tab="outcomes"
								   role="tab"
								   aria-controls="pane-outcomes"
								   aria-selected="false">
									<i class="fas fa-chart-bar"></i>Outcomes
								</a>
							</li>
							<?php endif; ?>

							<!-- Satisfaction – admin + grievance officer + doctor -->
							<?php if ($canSeeSatisfaction): ?>
							<li class="nav-item">
								<a class="nav-link"
								   id="tab-satisfaction-lnk"
								   data-toggle="tab"
								   href="#pane-satisfaction"
								   data-tab="satisfaction"
								   role="tab"
								   aria-controls="pane-satisfaction"
								   aria-selected="false">
									<i class="fas fa-smile"></i>Satisfaction
								</a>
							</li>
							<?php endif; ?>

							<!-- Patient Trends – all roles -->
							<li class="nav-item">
								<a class="nav-link"
								   id="tab-trends-lnk"
								   data-toggle="tab"
								   href="#pane-trends"
								   data-tab="trends"
								   role="tab"
								   aria-controls="pane-trends"
								   aria-selected="false">
									<i class="fas fa-chart-line"></i>Patient Trends
								</a>
							</li>

						</ul>
					</div><!-- /card-header -->

					<div class="card-body">
						<div class="tab-content" id="analyticsTabContent">

							<!-- Overview pane – pre-populated with spinner; JS loads immediately -->
							<div class="tab-pane fade show active analytics-tab-pane"
							     id="pane-overview"
							     role="tabpanel"
							     aria-labelledby="tab-overview-lnk">
								<?= $spinnerHTML ?>
							</div>

							<!-- All other panes start empty; JS populates on first activation -->
							<?php if ($canSeeCaseload): ?>
							<div class="tab-pane fade analytics-tab-pane"
							     id="pane-caseload"
							     role="tabpanel"
							     aria-labelledby="tab-caseload-lnk"></div>
							<?php endif; ?>

							<?php if ($canSeeOutcomes): ?>
							<div class="tab-pane fade analytics-tab-pane"
							     id="pane-outcomes"
							     role="tabpanel"
							     aria-labelledby="tab-outcomes-lnk"></div>
							<?php endif; ?>

							<?php if ($canSeeSatisfaction): ?>
							<div class="tab-pane fade analytics-tab-pane"
							     id="pane-satisfaction"
							     role="tabpanel"
							     aria-labelledby="tab-satisfaction-lnk"></div>
							<?php endif; ?>

							<div class="tab-pane fade analytics-tab-pane"
							     id="pane-trends"
							     role="tabpanel"
							     aria-labelledby="tab-trends-lnk"></div>

						</div><!-- /tab-content -->
					</div><!-- /card-body -->

				</div><!-- /card -->

			</div><!-- /container-fluid -->
		</section>
	</div><!-- /content-wrapper -->

	<footer class="main-footer">
		<div class="float-right d-none d-sm-inline">CareSystem</div>
		<strong>Analytics &amp; Reporting</strong>
	</footer>

</div><!-- /wrapper -->

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme-toggle.js"></script>
<script>
(function () {
    'use strict';

    // ── State ────────────────────────────────────────────────────────────────
    var tabCache  = {};           // tab slug → injected HTML (cleared on date change)
    var dateFrom  = document.getElementById('dateFrom').value;
    var dateTo    = document.getElementById('dateTo').value;
    var activeTab = 'overview';   // tracks which tab is currently active

    // Spinner HTML is PHP-rendered once and reused by JS — no duplication.
    var spinnerHTML = <?= json_encode($spinnerHTML) ?>;

    var errorHTML = '<div class="analytics-error">'
                  + '<i class="fas fa-exclamation-circle fa-2x text-danger mb-2"></i>'
                  + '<span>Could not load data. Please try again.</span>'
                  + '<button class="btn btn-sm btn-outline-secondary mt-2" '
                  +         'onclick="window.analyticsReload()">'
                  + '<i class="fas fa-redo mr-1"></i>Retry</button></div>';

    // ── Helpers ──────────────────────────────────────────────────────────────
    function buildUrl(tab) {
        return 'analytics.php?action=data&tab=' + encodeURIComponent(tab)
             + '&from=' + encodeURIComponent(dateFrom)
             + '&to='   + encodeURIComponent(dateTo);
    }

    function getPane(tab) {
        return document.getElementById('pane-' + tab);
    }

    // ── Load a tab ───────────────────────────────────────────────────────────
    function loadTab(tab) {
        var pane = getPane(tab);
        if (!pane) return;

        // Serve from cache if already loaded for this date range.
        if (tabCache[tab]) {
            pane.innerHTML = tabCache[tab];
            return;
        }

        // Show spinner while the request is in flight.
        pane.innerHTML = spinnerHTML;

        fetch(buildUrl(tab))
            .then(function (res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.text();
            })
            .then(function (html) {
                tabCache[tab]  = html;
                pane.innerHTML = html;
            })
            .catch(function () {
                pane.innerHTML = errorHTML;
            });
    }

    // ── Tab activation (Bootstrap 4 event) ───────────────────────────────────
    $('#analyticsTabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        activeTab = $(e.target).data('tab');
        loadTab(activeTab);
    });

    // ── Date range controls ──────────────────────────────────────────────────
    function applyRange(from, to) {
        if (!from || !to || from > to) return;
        dateFrom = from;
        dateTo   = to;
        document.getElementById('dateFrom').value = from;
        document.getElementById('dateTo').value   = to;
        tabCache = {};              // invalidate all cached tab data
        loadTab(activeTab);         // reload only the visible tab
    }

    document.getElementById('btnApplyRange').addEventListener('click', function () {
        applyRange(
            document.getElementById('dateFrom').value,
            document.getElementById('dateTo').value
        );
    });

    // Quick-range presets
    document.getElementById('btnThisMonth').addEventListener('click', function () {
        var now  = new Date();
        var from = now.getFullYear() + '-'
                 + String(now.getMonth() + 1).padStart(2, '0') + '-01';
        applyRange(from, now.toISOString().slice(0, 10));
    });

    document.getElementById('btnLast30').addEventListener('click', function () {
        var to   = new Date();
        var from = new Date(+to - 30 * 864e5);
        applyRange(from.toISOString().slice(0, 10), to.toISOString().slice(0, 10));
    });

    document.getElementById('btnThisYear').addEventListener('click', function () {
        var year = new Date().getFullYear();
        applyRange(year + '-01-01', new Date().toISOString().slice(0, 10));
    });

    // ── Public retry helper (called by the error state Retry button) ─────────
    window.analyticsReload = function () {
        delete tabCache[activeTab];
        loadTab(activeTab);
    };

    // ── Boot: fetch the Overview tab immediately on page load ────────────────
    loadTab('overview');

}());
</script>
</body>
</html>
