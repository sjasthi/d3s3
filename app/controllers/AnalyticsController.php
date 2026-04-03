<?php
/**
 * app/controllers/AnalyticsController.php
 *
 * Handles the Analytics & Reporting page.
 *
 * Page render:
 *   index()  – computes scope variables and renders the view shell.
 *
 * AJAX data endpoints (called by JS on tab activation):
 *   dataOverview()     – KPI summary
 *   dataCaseload()     – cases handled + workload by employee
 *   dataOutcomes()     – case duration, closure type breakdown, referrals
 *   dataSatisfaction() – patient feedback and complaint summary
 *   dataTrends()       – patient demographics and visit patterns
 *
 * Access model:
 *   All authenticated users can reach this controller.
 *   Scope variables ($isAdmin, $fullAccess, etc.) gate what each role sees.
 *   No entry in role_permissions is required.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/permissions.php';

class AnalyticsController
{
    // ── Shared HTML snippets ──────────────────────────────────────────────────

    /**
     * Loading spinner HTML.
     * Used by the view (initial state of the Overview pane) and serialised
     * into the JS bundle so the client can insert it while fetching other tabs.
     */
    public static function spinnerHTML(): string
    {
        return '<div class="analytics-spinner">'
             . '<div class="spinner-border" role="status">'
             . '<span class="sr-only">Loading\u2026</span>'
             . '</div>'
             . '<span>Loading\u2026</span>'
             . '</div>';
    }

    // ── Page render ───────────────────────────────────────────────────────────

    public function index(): void
    {
        $this->requireLogin();

        // Compute scope — extract() expands into $isAdmin, $canSeeCaseload, etc.
        $scope = $this->buildScope();
        extract($scope);

        // Default date range: first of current month → today.
        [$defaultFrom, $defaultTo] = $this->getDateRange();

        // Pass the spinner to the view so it can embed it and serialise it into JS.
        $spinnerHTML = self::spinnerHTML();

        require __DIR__ . '/../views/analytics.php';
    }

    // ── AJAX: Overview tab ────────────────────────────────────────────────────

    public function dataOverview(): void
    {
        $this->requireLogin();
        header('Content-Type: text/html; charset=utf-8');

        $scope = $this->buildScope();
        extract($scope);
        [$from, $to] = $this->getDateRange();

        $pdo = getDBConnection();

        // Non-admin users see only cases they created or were assigned to.
        $userFilter = $fullAccess
            ? ''
            : ' AND (cs.created_by_user_id = :uid OR cs.assigned_doctor_user_id = :uid)';
        $baseParams = $fullAccess
            ? [':from' => $from, ':to' => $to]
            : [':from' => $from, ':to' => $to, ':uid' => (int) $_SESSION['user_id']];

        // Total cases opened in the range.
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM case_sheets cs
              WHERE DATE(cs.visit_datetime) BETWEEN :from AND :to
              $userFilter"
        );
        $stmt->execute($baseParams);
        $totalCases = (int) $stmt->fetchColumn();

        // Cases closed in the range.
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM case_sheets cs
              WHERE cs.is_closed = 1
                AND DATE(cs.closed_at) BETWEEN :from AND :to
              $userFilter"
        );
        $stmt->execute($baseParams);
        $closedCases = (int) $stmt->fetchColumn();

        // Average case duration in minutes (closed cases with valid timestamps).
        $stmt = $pdo->prepare(
            "SELECT ROUND(AVG(TIMESTAMPDIFF(MINUTE, cs.visit_datetime, cs.closed_at)), 1)
               FROM case_sheets cs
              WHERE cs.is_closed = 1
                AND cs.closed_at IS NOT NULL
                AND DATE(cs.closed_at) BETWEEN :from AND :to
              $userFilter"
        );
        $stmt->execute($baseParams);
        $rawDur      = $stmt->fetchColumn();
        $avgDuration = $rawDur !== null ? self::formatDuration((float) $rawDur) : '—';

        // Stale open cases — not closed, last opened > 7 days ago.
        $staleFilter = $fullAccess
            ? ''
            : ' AND (created_by_user_id = :uid OR assigned_doctor_user_id = :uid)';
        $staleParams = $fullAccess ? [] : [':uid' => (int) $_SESSION['user_id']];
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM case_sheets
              WHERE is_closed = 0
                AND visit_datetime < DATE_SUB(NOW(), INTERVAL 7 DAY)
              $staleFilter"
        );
        $stmt->execute($staleParams);
        $staleCases = (int) $stmt->fetchColumn();

        // Referrals issued in the range.
        $refFilter = $fullAccess ? '' : ' AND cs.assigned_doctor_user_id = :uid';
        $refParams  = array_merge(
            [':from' => $from, ':to' => $to],
            $fullAccess ? [] : [':uid' => (int) $_SESSION['user_id']]
        );
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM case_sheets cs
              WHERE cs.closure_type = 'REFERRAL'
                AND cs.is_closed = 1
                AND DATE(cs.closed_at) BETWEEN :from AND :to
              $refFilter"
        );
        $stmt->execute($refParams);
        $referrals = (int) $stmt->fetchColumn();

        // Patient satisfaction — only for roles with Satisfaction tab access.
        $totalFb        = 0;
        $positiveFb     = 0;
        $openComplaints = 0;
        if ($canSeeSatisfaction) {
            $fbFilter = ($isAdmin || $isGrievance) ? '' : ' AND related_user_id = :uid';
            $fbParams = ($isAdmin || $isGrievance) ? [] : [':uid' => (int) $_SESSION['user_id']];

            $stmt = $pdo->prepare(
                "SELECT feedback_type, COUNT(*) AS cnt
                   FROM patient_feedback
                  WHERE DATE(created_at) BETWEEN :from AND :to
                  $fbFilter
                  GROUP BY feedback_type"
            );
            $stmt->execute(array_merge([':from' => $from, ':to' => $to], $fbParams));
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $totalFb += (int) $row['cnt'];
                if ($row['feedback_type'] === 'POSITIVE') {
                    $positiveFb = (int) $row['cnt'];
                }
            }

            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM patient_feedback
                  WHERE feedback_type = 'COMPLAINT'
                    AND status != 'CLOSED'
                  $fbFilter"
            );
            $stmt->execute($fbParams);
            $openComplaints = (int) $stmt->fetchColumn();
        }

        $satisfactionPct = ($totalFb > 0)
            ? round(($positiveFb / $totalFb) * 100) . '%'
            : '—';

        echo $this->overviewHTML(
            $isAdmin ? 'Clinic-wide' : 'Your cases',
            $from, $to,
            $totalCases, $closedCases, $avgDuration,
            $staleCases, $referrals,
            $canSeeSatisfaction, $satisfactionPct, $openComplaints
        );
    }

    // ── AJAX: Caseload tab ────────────────────────────────────────────────────

    public function dataCaseload(): void
    {
        $this->requireLogin();
        header('Content-Type: text/html; charset=utf-8');
        // TODO: implement in Caseload build step.
        echo $this->stubPanel('Caseload', 'fa-users',
            'Cases handled by employee and workload analytics — coming soon.');
    }

    // ── AJAX: Outcomes tab ────────────────────────────────────────────────────

    public function dataOutcomes(): void
    {
        $this->requireLogin();
        header('Content-Type: text/html; charset=utf-8');
        // TODO: implement in Outcomes build step.
        echo $this->stubPanel('Outcomes', 'fa-chart-bar',
            'Case duration, closure type breakdown, and referrals — coming soon.');
    }

    // ── AJAX: Satisfaction tab ────────────────────────────────────────────────

    public function dataSatisfaction(): void
    {
        $this->requireLogin();
        header('Content-Type: text/html; charset=utf-8');
        // TODO: implement in Satisfaction build step.
        echo $this->stubPanel('Satisfaction', 'fa-smile',
            'Patient feedback and complaint summary — coming soon.');
    }

    // ── AJAX: Patient Trends tab ──────────────────────────────────────────────

    public function dataTrends(): void
    {
        $this->requireLogin();
        header('Content-Type: text/html; charset=utf-8');
        // TODO: implement in Patient Trends build step.
        echo $this->stubPanel('Patient Trends', 'fa-chart-line',
            'Demographics, conditions, and visit pattern analytics — coming soon.');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Compute role-based scope variables.
     * Use extract() on the return value to bring them into local scope.
     *
     * @return array{
     *   isAdmin: bool,
     *   isClinical: bool,
     *   isDoctor: bool,
     *   isGrievance: bool,
     *   fullAccess: bool,
     *   canSeeCaseload: bool,
     *   canSeeOutcomes: bool,
     *   canSeeSatisfaction: bool,
     * }
     */
    private function buildScope(): array
    {
        $role = $_SESSION['user_role'] ?? '';

        $isAdmin     = in_array($role, ['SUPER_ADMIN', 'ADMIN'], true);
        $isClinical  = can($role, 'case_sheets', 'W');
        $isDoctor    = ($role === 'DOCTOR');
        $isGrievance = ($role === 'GRIEVANCE_OFFICER');
        $fullAccess  = $isAdmin;

        return [
            'isAdmin'            => $isAdmin,
            'isClinical'         => $isClinical,
            'isDoctor'           => $isDoctor,
            'isGrievance'        => $isGrievance,
            'fullAccess'         => $fullAccess,
            'canSeeCaseload'     => $isAdmin || $isClinical,
            'canSeeOutcomes'     => $isAdmin || $isDoctor,
            'canSeeSatisfaction' => $isAdmin || $isGrievance || $isDoctor,
        ];
    }

    /**
     * Parse the from/to date range from GET params.
     * Defaults to first day of current month → today.
     *
     * @return array{string, string}  ['YYYY-MM-DD', 'YYYY-MM-DD']
     */
    private function getDateRange(): array
    {
        $today        = date('Y-m-d');
        $firstOfMonth = date('Y-m-01');

        $from = $_GET['from'] ?? $firstOfMonth;
        $to   = $_GET['to']   ?? $today;

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = $firstOfMonth;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = $today;
        if ($from > $to) $from = $firstOfMonth;

        return [$from, $to];
    }

    /**
     * Redirect unauthenticated visitors to the login page.
     */
    private function requireLogin(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
    }

    /**
     * Placeholder card rendered by stub data methods.
     */
    private function stubPanel(string $title, string $icon, string $message): string
    {
        return '<div class="text-center py-5 text-muted">'
             . "<i class=\"fas {$icon} fa-3x mb-3 d-block\"></i>"
             . "<h5>{$title}</h5>"
             . "<p class=\"mb-0 small\">{$message}</p>"
             . '</div>';
    }

    /**
     * Format a duration in minutes to a readable string.
     * 75 → "1h 15m",   45 → "45m",   120 → "2h"
     */
    public static function formatDuration(float $minutes): string
    {
        $mins = (int) round($minutes);
        if ($mins < 60) return "{$mins}m";
        $h = intdiv($mins, 60);
        $m = $mins % 60;
        return $m > 0 ? "{$h}h {$m}m" : "{$h}h";
    }

    /**
     * Render the Overview tab HTML.
     * Kept inline (not a separate partial file) so the scaffold has zero
     * external file dependencies beyond the view shell.
     */
    private function overviewHTML(
        string $scopeLabel,
        string $from,
        string $to,
        int    $totalCases,
        int    $closedCases,
        string $avgDuration,
        int    $staleCases,
        int    $referrals,
        bool   $canSeeSatisfaction,
        string $satisfactionPct,
        int    $openComplaints
    ): string {
        $fromFmt   = date('M j, Y', strtotime($from));
        $toFmt     = date('M j, Y', strtotime($to));
        $staleClass = $staleCases     > 0 ? 'text-warning' : 'text-success';
        $staleIcon  = $staleCases     > 0 ? 'fa-exclamation-triangle' : 'fa-check-circle';
        $compClass  = $openComplaints > 0 ? 'text-danger'  : 'text-success';
        $compIcon   = $openComplaints > 0 ? 'fa-exclamation-circle'  : 'fa-check-circle';

        $satisfactionCards = '';
        if ($canSeeSatisfaction) {
            $satisfactionCards = <<<HTML
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-success"><i class="fas fa-smile"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Satisfaction</span>
                        <span class="info-box-number">{$satisfactionPct}</span>
                        <span class="progress-description text-muted small">Positive feedback rate</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-danger"><i class="fas {$compIcon}"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Open Complaints</span>
                        <span class="info-box-number {$compClass}">{$openComplaints}</span>
                        <span class="progress-description text-muted small">Unresolved complaints</span>
                    </div>
                </div>
            </div>
            HTML;
        }

        return <<<HTML
        <div class="d-flex align-items-center mb-3">
            <span class="text-muted small">
                <i class="fas fa-info-circle mr-1"></i>
                {$scopeLabel} &mdash; {$fromFmt} to {$toFmt}
            </span>
        </div>
        <div class="row">
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-primary"><i class="fas fa-file-medical"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Cases Opened</span>
                        <span class="info-box-number">{$totalCases}</span>
                        <span class="progress-description text-muted small">Visits in period</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Cases Closed</span>
                        <span class="info-box-number">{$closedCases}</span>
                        <span class="progress-description text-muted small">Completed in period</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-info"><i class="fas fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Avg Duration</span>
                        <span class="info-box-number">{$avgDuration}</span>
                        <span class="progress-description text-muted small">Open to close</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-warning"><i class="fas {$staleIcon}"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Stale Cases</span>
                        <span class="info-box-number {$staleClass}">{$staleCases}</span>
                        <span class="progress-description text-muted small">Open &gt; 7 days</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-secondary"><i class="fas fa-share-square"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Referrals Issued</span>
                        <span class="info-box-number">{$referrals}</span>
                        <span class="progress-description text-muted small">Closed as referral</span>
                    </div>
                </div>
            </div>
            {$satisfactionCards}
        </div>
        HTML;
    }
}
