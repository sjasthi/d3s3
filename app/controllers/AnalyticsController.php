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
    /** When true, data methods skip header() so output can be captured for the print page. */
    private $printMode = false;

    // =========================================================================
    // Shared HTML snippets
    // =========================================================================

    /**
     * Loading spinner HTML.
     * Used by the view (initial state of the Overview pane) and serialised
     * into the JS bundle so the client can insert it while fetching other tabs.
     */
    public static function spinnerHTML()
    {
        return '<div class="analytics-spinner">'
             . '<div class="spinner-border" role="status">'
             . '<span class="sr-only">Loading...</span>'
             . '</div>'
             . '<span>Loading...</span>'
             . '</div>';
    }

    // =========================================================================
    // Page render
    // =========================================================================

    public function index()
    {
        $this->requireLogin();

        $scope = $this->buildScope();
        extract($scope);

        list($defaultFrom, $defaultTo) = $this->getDateRange();

        $spinnerHTML = self::spinnerHTML();

        require __DIR__ . '/../views/analytics.php';
    }

    // =========================================================================
    // AJAX: Overview tab
    // =========================================================================

    public function dataOverview()
    {
        $this->requireLogin();
        if (!$this->printMode) header('Content-Type: text/html; charset=utf-8');

        $scope = $this->buildScope();
        extract($scope);
        list($from, $to) = $this->getDateRange();

        $pdo = getDBConnection();

        $userFilter = $fullAccess
            ? ''
            : ' AND (cs.created_by_user_id = :uid OR cs.assigned_doctor_user_id = :uid2)';
        $baseParams = $fullAccess
            ? array(':from' => $from, ':to' => $to)
            : array(':from' => $from, ':to' => $to, ':uid' => (int)$_SESSION['user_id'], ':uid2' => (int)$_SESSION['user_id']);

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM case_sheets cs'
            . ' WHERE DATE(cs.visit_datetime) BETWEEN :from AND :to'
            . $userFilter
        );
        $stmt->execute($baseParams);
        $totalCases = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM case_sheets cs'
            . ' WHERE cs.is_closed = 1'
            . ' AND DATE(cs.closed_at) BETWEEN :from AND :to'
            . $userFilter
        );
        $stmt->execute($baseParams);
        $closedCases = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare(
            'SELECT ROUND(AVG(TIMESTAMPDIFF(MINUTE, cs.visit_datetime, cs.closed_at)), 1)'
            . ' FROM case_sheets cs'
            . ' WHERE cs.is_closed = 1'
            . ' AND cs.closed_at IS NOT NULL'
            . ' AND DATE(cs.closed_at) BETWEEN :from AND :to'
            . $userFilter
        );
        $stmt->execute($baseParams);
        $rawDur      = $stmt->fetchColumn();
        $avgDuration = ($rawDur !== null && $rawDur !== false)
            ? self::formatDuration((float)$rawDur)
            : '&mdash;';

        // Median duration (computed in PHP from individual durations)
        $medianDuration = '&mdash;';
        $stmt = $pdo->prepare(
            'SELECT TIMESTAMPDIFF(MINUTE, cs.visit_datetime, cs.closed_at) AS dur'
            . ' FROM case_sheets cs'
            . ' WHERE cs.is_closed = 1'
            . ' AND cs.closed_at IS NOT NULL'
            . ' AND TIMESTAMPDIFF(MINUTE, cs.visit_datetime, cs.closed_at) >= 0'
            . ' AND DATE(cs.closed_at) BETWEEN :from AND :to'
            . $userFilter
            . ' ORDER BY dur'
        );
        $stmt->execute($baseParams);
        $ovMedian = self::computeMedian($stmt->fetchAll(PDO::FETCH_COLUMN));
        if ($ovMedian !== null) {
            $medianDuration = self::formatDuration($ovMedian);
        }

        $staleFilter = $fullAccess
            ? ''
            : ' AND (created_by_user_id = :uid OR assigned_doctor_user_id = :uid2)';
        $staleParams = $fullAccess
            ? array()
            : array(':uid' => (int)$_SESSION['user_id'], ':uid2' => (int)$_SESSION['user_id']);
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM case_sheets'
            . ' WHERE is_closed = 0'
            . ' AND visit_datetime < DATE_SUB(NOW(), INTERVAL 7 DAY)'
            . $staleFilter
        );
        $stmt->execute($staleParams);
        $staleCases = (int)$stmt->fetchColumn();

        $refFilter = $fullAccess ? '' : ' AND cs.assigned_doctor_user_id = :uid';
        $refParams  = array_merge(
            array(':from' => $from, ':to' => $to),
            $fullAccess ? array() : array(':uid' => (int)$_SESSION['user_id'])
        );
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM case_sheets cs"
            . " WHERE cs.closure_type = 'REFERRAL'"
            . " AND cs.is_closed = 1"
            . " AND DATE(cs.closed_at) BETWEEN :from AND :to"
            . $refFilter
        );
        $stmt->execute($refParams);
        $referrals = (int)$stmt->fetchColumn();

        $totalFb        = 0;
        $positiveFb     = 0;
        $openComplaints = 0;
        if ($canSeeSatisfaction) {
            $fbFilter = ($isAdmin || $isGrievance) ? '' : ' AND related_user_id = :uid';
            $fbParams = ($isAdmin || $isGrievance)
                ? array()
                : array(':uid' => (int)$_SESSION['user_id']);

            $stmt = $pdo->prepare(
                'SELECT feedback_type, COUNT(*) AS cnt'
                . ' FROM patient_feedback'
                . ' WHERE DATE(created_at) BETWEEN :from AND :to'
                . $fbFilter
                . ' GROUP BY feedback_type'
            );
            $stmt->execute(array_merge(array(':from' => $from, ':to' => $to), $fbParams));
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $totalFb += (int)$row['cnt'];
                if ($row['feedback_type'] === 'POSITIVE') {
                    $positiveFb = (int)$row['cnt'];
                }
            }

            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM patient_feedback"
                . " WHERE feedback_type = 'COMPLAINT'"
                . " AND status != 'CLOSED'"
                . $fbFilter
            );
            $stmt->execute($fbParams);
            $openComplaints = (int)$stmt->fetchColumn();
        }

        $satisfactionPct = ($totalFb > 0)
            ? round(($positiveFb / $totalFb) * 100) . '%'
            : '&mdash;';

        echo $this->overviewHTML(
            $isAdmin ? 'Clinic-wide' : 'Your cases',
            $from, $to,
            $totalCases, $closedCases, $avgDuration, $medianDuration,
            $staleCases, $referrals,
            $canSeeSatisfaction, $satisfactionPct, $openComplaints
        );
    }

    // =========================================================================
    // AJAX: Caseload tab
    // =========================================================================

    public function dataCaseload()
    {
        $this->requireLogin();
        if (!$this->printMode) header('Content-Type: text/html; charset=utf-8');

        $scope = $this->buildScope();
        extract($scope);
        list($from, $to) = $this->getDateRange();

        $pdo = getDBConnection();

        $uid        = (int)$_SESSION['user_id'];
        $dateParams = array(':from' => $from, ':to' => $to);

        // Non-admins: scope intake to cases they created,
        // and closures to cases they were assigned to as doctor.
        $intakeFilter  = $fullAccess ? '' : ' AND cs.created_by_user_id = :uid';
        $closureFilter = $fullAccess ? '' : ' AND cs.assigned_doctor_user_id = :uid';
        $intakeParams  = array_merge($dateParams, $fullAccess ? array() : array(':uid' => $uid));
        $closureParams = array_merge($dateParams, $fullAccess ? array() : array(':uid' => $uid));

        // ── 1. Nurse intake volume ────────────────────────────────────────────
        $stmt = $pdo->prepare(
            'SELECT u.user_id, u.first_name, u.last_name, u.role, COUNT(*) AS cases_opened'
            . ' FROM case_sheets cs'
            . ' JOIN users u ON u.user_id = cs.created_by_user_id'
            . ' WHERE DATE(cs.visit_datetime) BETWEEN :from AND :to'
            . $intakeFilter
            . ' GROUP BY cs.created_by_user_id, u.user_id, u.first_name, u.last_name, u.role'
            . ' ORDER BY cases_opened DESC'
        );
        $stmt->execute($intakeParams);
        $intakeRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── 1b. Intake duration per nurse (case open → INTAKE_COMPLETE) ───────
        // Uses the audit log to find the exact timestamp intake was completed,
        // then computes the elapsed time from visit_datetime per nurse.
        $intakeTimings = array();
        $stmt = $pdo->prepare(
            'SELECT cs.created_by_user_id AS nurse_id,'
            . ' TIMESTAMPDIFF(MINUTE, cs.visit_datetime, al.changed_at) AS intake_dur'
            . ' FROM case_sheets cs'
            . ' JOIN case_sheet_audit_log al ON al.case_sheet_id = cs.case_sheet_id'
            . " WHERE al.field_name = 'status'"
            . " AND al.new_value = 'INTAKE_COMPLETE'"
            . ' AND TIMESTAMPDIFF(MINUTE, cs.visit_datetime, al.changed_at) >= 0'
            . ' AND cs.created_by_user_id IS NOT NULL'
            . ' AND DATE(cs.visit_datetime) BETWEEN :from AND :to'
            . $intakeFilter
            . ' ORDER BY nurse_id, intake_dur'
        );
        $stmt->execute($intakeParams);
        $nurseGroups = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $nurseGroups[$row['nurse_id']][] = (float)$row['intake_dur'];
        }
        foreach ($nurseGroups as $nurseId => $durs) {
            $avg = count($durs) > 0 ? array_sum($durs) / count($durs) : null;
            $intakeTimings[$nurseId] = array(
                'avg'    => $avg,
                'median' => self::computeMedian($durs),
            );
        }

        // ── 2. Doctor case closures ───────────────────────────────────────────
        $stmt = $pdo->prepare(
            'SELECT u.user_id, u.first_name, u.last_name, COUNT(*) AS cases_closed,'
            . ' ROUND(AVG(TIMESTAMPDIFF(MINUTE, cs.visit_datetime, cs.closed_at)), 0) AS avg_dur_min'
            . ' FROM case_sheets cs'
            . ' JOIN users u ON u.user_id = cs.assigned_doctor_user_id'
            . ' WHERE cs.is_closed = 1'
            . ' AND cs.assigned_doctor_user_id IS NOT NULL'
            . ' AND TIMESTAMPDIFF(MINUTE, cs.visit_datetime, cs.closed_at) >= 0'
            . ' AND DATE(cs.closed_at) BETWEEN :from AND :to'
            . $closureFilter
            . ' GROUP BY cs.assigned_doctor_user_id, u.user_id, u.first_name, u.last_name'
            . ' ORDER BY cases_closed DESC'
        );
        $stmt->execute($closureParams);
        $closureRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── Median duration per doctor ────────────────────────────────────────
        $caseloadDoctorMedians = array();
        $stmt = $pdo->prepare(
            'SELECT cs.assigned_doctor_user_id AS doc_id,'
            . ' TIMESTAMPDIFF(MINUTE, cs.visit_datetime, cs.closed_at) AS dur'
            . ' FROM case_sheets cs'
            . ' WHERE cs.is_closed = 1'
            . ' AND cs.assigned_doctor_user_id IS NOT NULL'
            . ' AND TIMESTAMPDIFF(MINUTE, cs.visit_datetime, cs.closed_at) >= 0'
            . ' AND DATE(cs.closed_at) BETWEEN :from AND :to'
            . $closureFilter
            . ' ORDER BY doc_id, dur'
        );
        $stmt->execute($closureParams);
        $clDurGroups = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $clDurGroups[$row['doc_id']][] = (float)$row['dur'];
        }
        foreach ($clDurGroups as $docId => $durs) {
            $caseloadDoctorMedians[$docId] = self::computeMedian($durs);
        }

        // ── 3. Role-level summary (admin only) ────────────────────────────────
        $roleRows = array();
        if ($fullAccess) {
            $stmt = $pdo->prepare(
                'SELECT u.role, COUNT(*) AS total_cases'
                . ' FROM case_sheets cs'
                . ' JOIN users u ON u.user_id = cs.created_by_user_id'
                . ' WHERE DATE(cs.visit_datetime) BETWEEN :from AND :to'
                . ' GROUP BY u.role'
                . ' ORDER BY total_cases DESC'
            );
            $stmt->execute($dateParams);
            $roleRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // ── 4. Walk-in vs scheduled ───────────────────────────────────────────
        $walkInFilter = $fullAccess ? '' : ' AND (cs.created_by_user_id = :uid OR cs.assigned_doctor_user_id = :uid2)';
        $walkInParams = array_merge($dateParams, $fullAccess ? array() : array(':uid' => $uid, ':uid2' => $uid));
        $stmt = $pdo->prepare(
            'SELECT'
            . ' COUNT(DISTINCT CASE WHEN a.appointment_id IS NOT NULL THEN cs.case_sheet_id END) AS scheduled,'
            . ' COUNT(DISTINCT CASE WHEN a.appointment_id IS NULL     THEN cs.case_sheet_id END) AS walkin'
            . ' FROM case_sheets cs'
            . ' LEFT JOIN appointments a ON a.case_sheet_id = cs.case_sheet_id'
            . ' WHERE DATE(cs.visit_datetime) BETWEEN :from AND :to'
            . $walkInFilter
        );
        $stmt->execute($walkInParams);
        $walkInRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $scheduled = (int)($walkInRow['scheduled'] ?? 0);
        $walkin    = (int)($walkInRow['walkin']    ?? 0);

        // ── 5. No-show rate by doctor (admin only) ────────────────────────────
        $noShowRows = array();
        if ($fullAccess) {
            $stmt = $pdo->prepare(
                'SELECT u.first_name, u.last_name,'
                . ' SUM(a.status = \'NO_SHOW\') AS no_shows,'
                . ' COUNT(*) AS total_appts'
                . ' FROM appointments a'
                . ' JOIN users u ON u.user_id = a.doctor_user_id'
                . ' WHERE DATE(a.scheduled_date) BETWEEN :from AND :to'
                . " AND a.status IN ('COMPLETED','NO_SHOW','CANCELLED')"
                . ' GROUP BY a.doctor_user_id, u.first_name, u.last_name'
                . ' HAVING total_appts > 0'
                . ' ORDER BY no_shows DESC'
            );
            $stmt->execute($dateParams);
            $noShowRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo $this->caseloadHTML(
            $from, $to, $isAdmin,
            $intakeRows, $intakeTimings, $closureRows, $caseloadDoctorMedians, $roleRows,
            $scheduled, $walkin, $noShowRows
        );
    }

    // =========================================================================
    // AJAX: Outcomes tab
    // =========================================================================

    public function dataOutcomes()
    {
        $this->requireLogin();
        if (!$this->printMode) header('Content-Type: text/html; charset=utf-8');

        $scope = $this->buildScope();
        extract($scope);
        list($from, $to) = $this->getDateRange();

        $pdo = getDBConnection();

        // Doctors see only their own cases; admins see all.
        $doctorFilter = $fullAccess ? '' : ' AND cs.assigned_doctor_user_id = :uid';
        $uidParam     = $fullAccess ? array() : array(':uid' => (int)$_SESSION['user_id']);
        $dateParams   = array(':from' => $from, ':to' => $to);
        $allParams    = array_merge($dateParams, $uidParam);

        // ── Duration summary ──────────────────────────────────────────────────
        $stmt = $pdo->prepare(
            'SELECT'
            . ' COUNT(*) AS total_closed,'
            . ' ROUND(AVG(TIMESTAMPDIFF(MINUTE, cs.visit_datetime, cs.closed_at)), 0) AS avg_min,'
            . ' ROUND(MIN(TIMESTAMPDIFF(MINUTE, cs.visit_datetime, cs.closed_at)), 0) AS min_min,'
            . ' ROUND(MAX(TIMESTAMPDIFF(MINUTE, cs.visit_datetime, cs.closed_at)), 0) AS max_min'
            . ' FROM case_sheets cs'
            . ' WHERE cs.is_closed = 1'
            . ' AND cs.closed_at IS NOT NULL'
            . ' AND TIMESTAMPDIFF(MINUTE, cs.visit_datetime, cs.closed_at) >= 0'
            . ' AND DATE(cs.closed_at) BETWEEN :from AND :to'
            . $doctorFilter
        );
        $stmt->execute($allParams);
        $durSummary  = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalClosed = (int)(isset($durSummary['total_closed'])  ? $durSummary['total_closed']  : 0);
        $avgMin      = (float)(isset($durSummary['avg_min'])     ? $durSummary['avg_min']       : 0);
        $minMin      = (int)(isset($durSummary['min_min'])       ? $durSummary['min_min']       : 0);
        $maxMin      = (int)(isset($durSummary['max_min'])       ? $durSummary['max_min']       : 0);

        // ── Median duration (computed in PHP to avoid complex SQL) ────────────
        $medianMin = null;
        if ($totalClosed > 0) {
            $stmt = $pdo->prepare(
                'SELECT TIMESTAMPDIFF(MINUTE, cs.visit_datetime, cs.closed_at) AS dur'
                . ' FROM case_sheets cs'
                . ' WHERE cs.is_closed = 1'
                . ' AND cs.closed_at IS NOT NULL'
                . ' AND TIMESTAMPDIFF(MINUTE, cs.visit_datetime, cs.closed_at) >= 0'
                . ' AND DATE(cs.closed_at) BETWEEN :from AND :to'
                . $doctorFilter
                . ' ORDER BY dur'
            );
            $stmt->execute($allParams);
            $medianMin = self::computeMedian($stmt->fetchAll(PDO::FETCH_COLUMN));
        }

        // ── Duration by visit type ────────────────────────────────────────────
        $stmt = $pdo->prepare(
            'SELECT cs.visit_type, COUNT(*) AS cnt,'
            . ' ROUND(AVG(TIMESTAMPDIFF(MINUTE, cs.visit_datetime, cs.closed_at)), 0) AS avg_min'
            . ' FROM case_sheets cs'
            . ' WHERE cs.is_closed = 1'
            . ' AND cs.closed_at IS NOT NULL'
            . ' AND TIMESTAMPDIFF(MINUTE, cs.visit_datetime, cs.closed_at) >= 0'
            . ' AND DATE(cs.closed_at) BETWEEN :from AND :to'
            . $doctorFilter
            . ' GROUP BY cs.visit_type'
            . ' ORDER BY avg_min DESC'
        );
        $stmt->execute($allParams);
        $durByType = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── Duration by doctor (admin only) ───────────────────────────────────
        $durByDoctor = array();
        if ($fullAccess) {
            $stmt = $pdo->prepare(
                'SELECT u.user_id, u.first_name, u.last_name, COUNT(*) AS cnt,'
                . ' ROUND(AVG(TIMESTAMPDIFF(MINUTE, cs.visit_datetime, cs.closed_at)), 0) AS avg_min'
                . ' FROM case_sheets cs'
                . ' JOIN users u ON u.user_id = cs.assigned_doctor_user_id'
                . ' WHERE cs.is_closed = 1'
                . ' AND cs.closed_at IS NOT NULL'
                . ' AND cs.assigned_doctor_user_id IS NOT NULL'
                . ' AND TIMESTAMPDIFF(MINUTE, cs.visit_datetime, cs.closed_at) >= 0'
                . ' AND DATE(cs.closed_at) BETWEEN :from AND :to'
                . ' GROUP BY cs.assigned_doctor_user_id, u.user_id, u.first_name, u.last_name'
                . ' ORDER BY avg_min ASC'
            );
            $stmt->execute($dateParams);
            $durByDoctor = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // ── Median by visit type ──────────────────────────────────────────────
        $typeMedians = array();
        $stmt = $pdo->prepare(
            'SELECT cs.visit_type, TIMESTAMPDIFF(MINUTE, cs.visit_datetime, cs.closed_at) AS dur'
            . ' FROM case_sheets cs'
            . ' WHERE cs.is_closed = 1'
            . ' AND cs.closed_at IS NOT NULL'
            . ' AND TIMESTAMPDIFF(MINUTE, cs.visit_datetime, cs.closed_at) >= 0'
            . ' AND DATE(cs.closed_at) BETWEEN :from AND :to'
            . $doctorFilter
            . ' ORDER BY cs.visit_type, dur'
        );
        $stmt->execute($allParams);
        $typeDurGroups = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $typeDurGroups[$row['visit_type']][] = (float)$row['dur'];
        }
        foreach ($typeDurGroups as $type => $durs) {
            $typeMedians[$type] = self::computeMedian($durs);
        }

        // ── Median by doctor (admin only) ─────────────────────────────────────
        $doctorMedians = array();
        if ($fullAccess) {
            $stmt = $pdo->prepare(
                'SELECT cs.assigned_doctor_user_id AS doc_id,'
                . ' TIMESTAMPDIFF(MINUTE, cs.visit_datetime, cs.closed_at) AS dur'
                . ' FROM case_sheets cs'
                . ' WHERE cs.is_closed = 1'
                . ' AND cs.closed_at IS NOT NULL'
                . ' AND cs.assigned_doctor_user_id IS NOT NULL'
                . ' AND TIMESTAMPDIFF(MINUTE, cs.visit_datetime, cs.closed_at) >= 0'
                . ' AND DATE(cs.closed_at) BETWEEN :from AND :to'
                . ' ORDER BY doc_id, dur'
            );
            $stmt->execute($dateParams);
            $docDurGroups = array();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $docDurGroups[$row['doc_id']][] = (float)$row['dur'];
            }
            foreach ($docDurGroups as $docId => $durs) {
                $doctorMedians[$docId] = self::computeMedian($durs);
            }
        }

        // ── Closure type breakdown ────────────────────────────────────────────
        $stmt = $pdo->prepare(
            'SELECT cs.closure_type, COUNT(*) AS cnt'
            . ' FROM case_sheets cs'
            . ' WHERE cs.is_closed = 1'
            . ' AND DATE(cs.closed_at) BETWEEN :from AND :to'
            . $doctorFilter
            . ' GROUP BY cs.closure_type'
            . ' ORDER BY cnt DESC'
        );
        $stmt->execute($allParams);
        $closureRows       = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalClosureCount = 0;
        foreach ($closureRows as $r) {
            $totalClosureCount += (int)$r['cnt'];
        }

        // ── Referrals list (most recent 25) ───────────────────────────────────
        $stmt = $pdo->prepare(
            'SELECT cs.case_sheet_id, cs.closed_at, cs.referral_to, cs.referral_reason,'
            . ' p.patient_code, p.first_name AS pat_first, p.last_name AS pat_last,'
            . ' u.first_name AS doc_first, u.last_name AS doc_last'
            . ' FROM case_sheets cs'
            . ' JOIN patients p ON p.patient_id = cs.patient_id'
            . ' LEFT JOIN users u ON u.user_id = cs.assigned_doctor_user_id'
            . " WHERE cs.closure_type = 'REFERRAL'"
            . ' AND cs.is_closed = 1'
            . ' AND DATE(cs.closed_at) BETWEEN :from AND :to'
            . $doctorFilter
            . ' ORDER BY cs.closed_at DESC'
            . ' LIMIT 25'
        );
        $stmt->execute($allParams);
        $referralRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM case_sheets cs'
            . " WHERE cs.closure_type = 'REFERRAL'"
            . ' AND cs.is_closed = 1'
            . ' AND DATE(cs.closed_at) BETWEEN :from AND :to'
            . $doctorFilter
        );
        $stmt->execute($allParams);
        $totalReferrals = (int)$stmt->fetchColumn();

        echo $this->outcomesHTML(
            $from, $to, $isAdmin,
            $totalClosed, $avgMin, $medianMin, $minMin, $maxMin,
            $durByType, $typeMedians, $durByDoctor, $doctorMedians,
            $closureRows, $totalClosureCount,
            $referralRows, $totalReferrals
        );
    }

    // =========================================================================
    // AJAX: Satisfaction tab
    // =========================================================================

    public function dataSatisfaction()
    {
        $this->requireLogin();
        if (!$this->printMode) header('Content-Type: text/html; charset=utf-8');

        $scope = $this->buildScope();
        extract($scope);
        list($from, $to) = $this->getDateRange();

        $pdo = getDBConnection();

        $dateParams = array(':from' => $from, ':to' => $to);

        // Admins and grievance officers see all feedback.
        // Doctors see only feedback where they are the related_user_id.
        $fbFilter = ($isAdmin || $isGrievance) ? '' : ' AND pf.related_user_id = :uid';
        $fbParams = ($isAdmin || $isGrievance)
            ? $dateParams
            : array_merge($dateParams, array(':uid' => (int)$_SESSION['user_id']));

        // ── 1. Feedback type breakdown ────────────────────────────────────────
        $stmt = $pdo->prepare(
            'SELECT feedback_type, COUNT(*) AS cnt'
            . ' FROM patient_feedback pf'
            . ' WHERE DATE(pf.created_at) BETWEEN :from AND :to'
            . $fbFilter
            . ' GROUP BY pf.feedback_type'
        );
        $stmt->execute($fbParams);
        $typeBreakdown = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $typeBreakdown[$row['feedback_type']] = (int)$row['cnt'];
        }
        $totalFb    = array_sum($typeBreakdown);
        $positiveCnt = isset($typeBreakdown['POSITIVE'])   ? $typeBreakdown['POSITIVE']   : 0;
        $complaintCnt = isset($typeBreakdown['COMPLAINT'])  ? $typeBreakdown['COMPLAINT']  : 0;
        $suggestionCnt = isset($typeBreakdown['SUGGESTION']) ? $typeBreakdown['SUGGESTION'] : 0;

        // ── 2. Average rating ─────────────────────────────────────────────────
        $stmt = $pdo->prepare(
            'SELECT ROUND(AVG(pf.rating), 1) AS avg_rating, COUNT(*) AS rated_cnt'
            . ' FROM patient_feedback pf'
            . ' WHERE pf.rating IS NOT NULL'
            . ' AND DATE(pf.created_at) BETWEEN :from AND :to'
            . $fbFilter
        );
        $stmt->execute($fbParams);
        $ratingRow  = $stmt->fetch(PDO::FETCH_ASSOC);
        $avgRating  = ($ratingRow && $ratingRow['avg_rating'] !== null) ? (float)$ratingRow['avg_rating'] : null;
        $ratedCount = ($ratingRow) ? (int)$ratingRow['rated_cnt'] : 0;

        // ── 3. Complaint status pipeline ──────────────────────────────────────
        $compFilter = ($isAdmin || $isGrievance) ? '' : ' AND pf.related_user_id = :uid';
        $compParams = ($isAdmin || $isGrievance)
            ? array()
            : array(':uid' => (int)$_SESSION['user_id']);
        $stmt = $pdo->prepare(
            "SELECT pf.status, COUNT(*) AS cnt"
            . " FROM patient_feedback pf"
            . " WHERE pf.feedback_type = 'COMPLAINT'"
            . $compFilter
            . " GROUP BY pf.status"
        );
        $stmt->execute($compParams);
        $pipelineStatuses = array('NEW' => 0, 'REVIEWED' => 0, 'ACTIONED' => 0, 'CLOSED' => 0);
        $totalComplaints  = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $pipelineStatuses[$row['status']] = (int)$row['cnt'];
            $totalComplaints += (int)$row['cnt'];
        }
        $resolvedComplaints = $pipelineStatuses['CLOSED'];
        $resolutionRate = $totalComplaints > 0
            ? round(($resolvedComplaints / $totalComplaints) * 100)
            : null;

        // ── 4. Feedback by staff member (admin + grievance only) ──────────────
        $byStaffRows = array();
        if ($isAdmin || $isGrievance) {
            $stmt = $pdo->prepare(
                'SELECT u.first_name, u.last_name, u.role,'
                . ' SUM(pf.feedback_type = \'POSITIVE\')   AS positive_cnt,'
                . ' SUM(pf.feedback_type = \'COMPLAINT\')  AS complaint_cnt,'
                . ' SUM(pf.feedback_type = \'SUGGESTION\') AS suggestion_cnt,'
                . ' COUNT(*) AS total_cnt,'
                . ' ROUND(AVG(pf.rating), 1) AS avg_rating'
                . ' FROM patient_feedback pf'
                . ' JOIN users u ON u.user_id = pf.related_user_id'
                . ' WHERE pf.related_user_id IS NOT NULL'
                . ' AND DATE(pf.created_at) BETWEEN :from AND :to'
                . ' GROUP BY pf.related_user_id, u.first_name, u.last_name, u.role'
                . ' ORDER BY total_cnt DESC'
            );
            $stmt->execute($dateParams);
            $byStaffRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // ── 5. Recent complaints list (most recent 20) ────────────────────────
        $stmt = $pdo->prepare(
            'SELECT pf.feedback_id, pf.feedback_text, pf.rating, pf.status,'
            . ' pf.created_at, pf.admin_notes,'
            . ' p.patient_code, p.first_name AS pat_first, p.last_name AS pat_last,'
            . ' u.first_name AS staff_first, u.last_name AS staff_last'
            . ' FROM patient_feedback pf'
            . ' JOIN patients p ON p.patient_id = pf.patient_id'
            . ' LEFT JOIN users u ON u.user_id = pf.related_user_id'
            . " WHERE pf.feedback_type = 'COMPLAINT'"
            . $compFilter
            . ' ORDER BY pf.created_at DESC'
            . ' LIMIT 20'
        );
        $stmt->execute($compParams);
        $recentComplaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo $this->satisfactionHTML(
            $from, $to, $isAdmin, $isGrievance,
            $totalFb, $positiveCnt, $complaintCnt, $suggestionCnt,
            $avgRating, $ratedCount,
            $pipelineStatuses, $totalComplaints, $resolutionRate,
            $byStaffRows, $recentComplaints
        );
    }

    // =========================================================================
    // AJAX: Patient Trends tab
    // =========================================================================

    public function dataTrends()
    {
        $this->requireLogin();
        if (!$this->printMode) header('Content-Type: text/html; charset=utf-8');

        list($from, $to) = $this->getDateRange();
        $pdo        = getDBConnection();
        $dateParams = array(':from' => $from, ':to' => $to);

        // ── 1. Total visits in period ─────────────────────────────────────────
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM case_sheets'
            . ' WHERE DATE(visit_datetime) BETWEEN :from AND :to'
        );
        $stmt->execute($dateParams);
        $totalVisits = (int)$stmt->fetchColumn();

        // ── 2. Total unique patients seen in period ───────────────────────────
        $stmt = $pdo->prepare(
            'SELECT COUNT(DISTINCT cs.patient_id) FROM case_sheets cs'
            . ' WHERE DATE(cs.visit_datetime) BETWEEN :from AND :to'
        );
        $stmt->execute($dateParams);
        $totalPatients = (int)$stmt->fetchColumn();

        // ── 2. New vs returning patients ──────────────────────────────────────
        $stmt = $pdo->prepare(
            'SELECT COUNT(DISTINCT p.patient_id) FROM patients p'
            . ' JOIN case_sheets cs ON cs.patient_id = p.patient_id'
            . ' WHERE DATE(cs.visit_datetime) BETWEEN :from AND :to'
            . ' AND DATE(p.first_seen_date) BETWEEN :from2 AND :to2'
        );
        $stmt->execute(array(':from' => $from, ':to' => $to, ':from2' => $from, ':to2' => $to));
        $newPatients = (int)$stmt->fetchColumn();
        $returning   = $totalPatients - $newPatients;

        // ── 3. Sex breakdown ──────────────────────────────────────────────────
        $stmt = $pdo->prepare(
            'SELECT p.sex, COUNT(DISTINCT p.patient_id) AS cnt'
            . ' FROM patients p'
            . ' JOIN case_sheets cs ON cs.patient_id = p.patient_id'
            . ' WHERE DATE(cs.visit_datetime) BETWEEN :from AND :to'
            . ' GROUP BY p.sex'
            . ' ORDER BY cnt DESC'
        );
        $stmt->execute($dateParams);
        $sexRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── 4. Age group distribution ─────────────────────────────────────────
        $stmt = $pdo->prepare(
            "SELECT"
            . " CASE"
            . "   WHEN p.age_years IS NULL      THEN 'Unknown'"
            . "   WHEN p.age_years < 18         THEN 'Under 18'"
            . "   WHEN p.age_years BETWEEN 18 AND 29 THEN '18 - 29'"
            . "   WHEN p.age_years BETWEEN 30 AND 44 THEN '30 - 44'"
            . "   WHEN p.age_years BETWEEN 45 AND 59 THEN '45 - 59'"
            . "   ELSE '60+'"
            . " END AS age_group,"
            . " COUNT(DISTINCT p.patient_id) AS cnt"
            . " FROM patients p"
            . " JOIN case_sheets cs ON cs.patient_id = p.patient_id"
            . " WHERE DATE(cs.visit_datetime) BETWEEN :from AND :to"
            . " GROUP BY age_group"
            . " ORDER BY FIELD(age_group,'Under 18','18 - 29','30 - 44','45 - 59','60+','Unknown')"
        );
        $stmt->execute($dateParams);
        $ageRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── 5. Visit type breakdown ───────────────────────────────────────────
        $stmt = $pdo->prepare(
            'SELECT cs.visit_type, COUNT(*) AS cnt'
            . ' FROM case_sheets cs'
            . ' WHERE DATE(cs.visit_datetime) BETWEEN :from AND :to'
            . ' GROUP BY cs.visit_type'
            . ' ORDER BY cnt DESC'
        );
        $stmt->execute($dateParams);
        $visitTypeRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── 6. Peak days of week ──────────────────────────────────────────────
        $stmt = $pdo->prepare(
            'SELECT DAYNAME(cs.visit_datetime) AS day_name,'
            . ' DAYOFWEEK(cs.visit_datetime) AS day_num,'
            . ' COUNT(*) AS cnt'
            . ' FROM case_sheets cs'
            . ' WHERE DATE(cs.visit_datetime) BETWEEN :from AND :to'
            . ' GROUP BY day_name, day_num'
            . ' ORDER BY day_num'
        );
        $stmt->execute($dateParams);
        $dayRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── 7. Chief complaint frequency (top 10) ─────────────────────────────
        $stmt = $pdo->prepare(
            'SELECT cs.chief_complaint, COUNT(*) AS cnt'
            . ' FROM case_sheets cs'
            . ' WHERE DATE(cs.visit_datetime) BETWEEN :from AND :to'
            . ' AND cs.chief_complaint IS NOT NULL'
            . " AND cs.chief_complaint != ''"
            . ' GROUP BY cs.chief_complaint'
            . ' ORDER BY cnt DESC'
            . ' LIMIT 10'
        );
        $stmt->execute($dateParams);
        $complaintRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── 9. Follow-up compliance ───────────────────────────────────────────
        // Cases closed as FOLLOW_UP with a follow_up_date in the past —
        // did the patient return within 14 days of that date?
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS total_due,'
            . ' SUM(CASE WHEN ('
            . '   SELECT COUNT(*) FROM case_sheets cs2'
            . '   WHERE cs2.patient_id = cs.patient_id'
            . '   AND cs2.case_sheet_id != cs.case_sheet_id'
            . '   AND cs2.visit_datetime'
            . '       BETWEEN cs.follow_up_date AND DATE_ADD(cs.follow_up_date, INTERVAL 14 DAY)'
            . ' ) > 0 THEN 1 ELSE 0 END) AS returned'
            . " FROM case_sheets cs"
            . " WHERE cs.closure_type = 'FOLLOW_UP'"
            . ' AND cs.is_closed = 1'
            . ' AND cs.follow_up_date IS NOT NULL'
            . ' AND cs.follow_up_date <= CURDATE()'
            . ' AND DATE(cs.closed_at) BETWEEN :from AND :to'
        );
        $stmt->execute($dateParams);
        $followRow    = $stmt->fetch(PDO::FETCH_ASSOC);
        $followDue    = $followRow ? (int)$followRow['total_due'] : 0;
        $followReturn = $followRow ? (int)$followRow['returned']  : 0;

        // ── 10. Medicine sources (most recent case sheet per patient) ─────────
        $stmt = $pdo->prepare(
            "SELECT JSON_UNQUOTE(JSON_EXTRACT(vitals_json, '$.medicine_sources')) AS med_source,"
            . " COUNT(*) AS cnt"
            . " FROM case_sheets"
            . " WHERE case_sheet_id IN ("
            . "   SELECT MAX(case_sheet_id) FROM case_sheets"
            . "   WHERE DATE(visit_datetime) BETWEEN :from AND :to"
            . "   GROUP BY patient_id"
            . " )"
            . " AND vitals_json IS NOT NULL AND vitals_json != '{}'"
            . " AND JSON_EXTRACT(vitals_json, '$.medicine_sources') IS NOT NULL"
            . " AND JSON_UNQUOTE(JSON_EXTRACT(vitals_json, '$.medicine_sources')) != ''"
            . " GROUP BY med_source ORDER BY cnt DESC"
        );
        $stmt->execute($dateParams);
        $medSourceRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalMedSource = array_sum(array_column($medSourceRows, 'cnt'));

        // ── 11. Medical conditions (most recent case sheet per patient) ───────
        $stmt = $pdo->prepare(
            "SELECT"
            . " SUM(JSON_UNQUOTE(JSON_EXTRACT(assessment, '$.condition_dm'))           = 'CURRENT') AS dm_current,"
            . " SUM(JSON_UNQUOTE(JSON_EXTRACT(assessment, '$.condition_dm'))           = 'PAST')    AS dm_past,"
            . " SUM(JSON_UNQUOTE(JSON_EXTRACT(assessment, '$.condition_htn'))          = 'CURRENT') AS htn_current,"
            . " SUM(JSON_UNQUOTE(JSON_EXTRACT(assessment, '$.condition_htn'))          = 'PAST')    AS htn_past,"
            . " SUM(JSON_UNQUOTE(JSON_EXTRACT(assessment, '$.condition_tsh'))          = 'CURRENT') AS tsh_current,"
            . " SUM(JSON_UNQUOTE(JSON_EXTRACT(assessment, '$.condition_tsh'))          = 'PAST')    AS tsh_past,"
            . " SUM(JSON_UNQUOTE(JSON_EXTRACT(assessment, '$.condition_heart_disease'))= 'CURRENT') AS heart_current,"
            . " SUM(JSON_UNQUOTE(JSON_EXTRACT(assessment, '$.condition_heart_disease'))= 'PAST')    AS heart_past,"
            . " COUNT(*) AS total_with_assessment"
            . " FROM case_sheets"
            . " WHERE case_sheet_id IN ("
            . "   SELECT MAX(case_sheet_id) FROM case_sheets"
            . "   WHERE DATE(visit_datetime) BETWEEN :from AND :to"
            . "   GROUP BY patient_id"
            . " )"
            . " AND assessment IS NOT NULL AND assessment != '{}'"
        );
        $stmt->execute($dateParams);
        $condRow2 = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalWithAssessment = $condRow2 ? (int)$condRow2['total_with_assessment'] : 0;
        $medConditions = array(
            'DM (Diabetes)'    => array('current' => (int)($condRow2['dm_current']    ?? 0), 'past' => (int)($condRow2['dm_past']    ?? 0)),
            'HTN (Hypertension)'=> array('current' => (int)($condRow2['htn_current']   ?? 0), 'past' => (int)($condRow2['htn_past']   ?? 0)),
            'TSH (Thyroid)'    => array('current' => (int)($condRow2['tsh_current']   ?? 0), 'past' => (int)($condRow2['tsh_past']   ?? 0)),
            'Heart Disease'    => array('current' => (int)($condRow2['heart_current'] ?? 0), 'past' => (int)($condRow2['heart_past'] ?? 0)),
        );

        // Other conditions — free text, top 15 entries (most recent case sheet per patient)
        $stmt = $pdo->prepare(
            "SELECT JSON_UNQUOTE(JSON_EXTRACT(assessment, '$.condition_others')) AS txt,"
            . " COUNT(*) AS cnt"
            . " FROM case_sheets"
            . " WHERE case_sheet_id IN ("
            . "   SELECT MAX(case_sheet_id) FROM case_sheets"
            . "   WHERE DATE(visit_datetime) BETWEEN :from AND :to"
            . "   GROUP BY patient_id"
            . " )"
            . " AND assessment IS NOT NULL"
            . " AND JSON_EXTRACT(assessment, '$.condition_others') IS NOT NULL"
            . " AND JSON_UNQUOTE(JSON_EXTRACT(assessment, '$.condition_others')) != ''"
            . " GROUP BY txt ORDER BY cnt DESC LIMIT 15"
        );
        $stmt->execute($dateParams);
        $otherConditions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── 12. Family history (most recent case sheet per patient) ──────────
        $stmt = $pdo->prepare(
            "SELECT"
            . " SUM(JSON_EXTRACT(assessment, '$.family_history_cancer')       = '1' OR JSON_EXTRACT(assessment, '$.family_history_cancer')       = 1) AS cancer,"
            . " SUM(JSON_EXTRACT(assessment, '$.family_history_tuberculosis')  = '1' OR JSON_EXTRACT(assessment, '$.family_history_tuberculosis')  = 1) AS tuberculosis,"
            . " SUM(JSON_EXTRACT(assessment, '$.family_history_diabetes')      = '1' OR JSON_EXTRACT(assessment, '$.family_history_diabetes')      = 1) AS diabetes,"
            . " SUM(JSON_EXTRACT(assessment, '$.family_history_bp')            = '1' OR JSON_EXTRACT(assessment, '$.family_history_bp')            = 1) AS bp,"
            . " SUM(JSON_EXTRACT(assessment, '$.family_history_thyroid')       = '1' OR JSON_EXTRACT(assessment, '$.family_history_thyroid')       = 1) AS thyroid,"
            . " COUNT(*) AS total_fh"
            . " FROM case_sheets"
            . " WHERE case_sheet_id IN ("
            . "   SELECT MAX(case_sheet_id) FROM case_sheets"
            . "   WHERE DATE(visit_datetime) BETWEEN :from AND :to"
            . "   GROUP BY patient_id"
            . " )"
            . " AND assessment IS NOT NULL AND assessment != '{}'"
        );
        $stmt->execute($dateParams);
        $fhRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalWithFH = $fhRow ? (int)$fhRow['total_fh'] : 0;
        $familyHistory = array(
            'Cancer'         => (int)($fhRow['cancer']       ?? 0),
            'Tuberculosis'   => (int)($fhRow['tuberculosis'] ?? 0),
            'Diabetes'       => (int)($fhRow['diabetes']     ?? 0),
            'BP (Hypertension)' => (int)($fhRow['bp']        ?? 0),
            'Thyroid'        => (int)($fhRow['thyroid']      ?? 0),
        );

        // Other family history — free text, top 15 entries (most recent case sheet per patient)
        $stmt = $pdo->prepare(
            "SELECT JSON_UNQUOTE(JSON_EXTRACT(assessment, '$.family_history_other')) AS txt,"
            . " COUNT(*) AS cnt"
            . " FROM case_sheets"
            . " WHERE case_sheet_id IN ("
            . "   SELECT MAX(case_sheet_id) FROM case_sheets"
            . "   WHERE DATE(visit_datetime) BETWEEN :from AND :to"
            . "   GROUP BY patient_id"
            . " )"
            . " AND assessment IS NOT NULL"
            . " AND JSON_EXTRACT(assessment, '$.family_history_other') IS NOT NULL"
            . " AND JSON_UNQUOTE(JSON_EXTRACT(assessment, '$.family_history_other')) != ''"
            . " GROUP BY txt ORDER BY cnt DESC LIMIT 15"
        );
        $stmt->execute($dateParams);
        $otherFamilyHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo $this->trendsHTML(
            $from, $to,
            $totalVisits, $totalPatients, $newPatients, $returning,
            $sexRows, $ageRows,
            $visitTypeRows, $dayRows,
            $complaintRows,
            $followDue, $followReturn,
            $medSourceRows, $totalMedSource,
            $medConditions, $totalWithAssessment, $otherConditions,
            $familyHistory, $totalWithFH, $otherFamilyHistory
        );
    }

    // =========================================================================
    // Print report
    // =========================================================================

    public function printReport()
    {
        $this->requireLogin();

        $scope = $this->buildScope();
        extract($scope);
        list($from, $to) = $this->getDateRange();

        // Tabs accessible to this role, in display order.
        $accessibleTabs = array();
        $accessibleTabs['overview']     = 'Overview';
        if ($canSeeCaseload)     $accessibleTabs['caseload']     = 'Caseload';
        if ($canSeeOutcomes)     $accessibleTabs['outcomes']     = 'Outcomes';
        if ($canSeeSatisfaction) $accessibleTabs['satisfaction'] = 'Satisfaction';
        $accessibleTabs['trends'] = 'Patient Trends';

        // Intersect with what the caller requested (?tabs=overview,caseload or ?tabs=all).
        $requestedRaw = isset($_GET['tabs']) ? trim($_GET['tabs']) : 'all';
        if ($requestedRaw !== 'all') {
            $requested     = array_map('trim', explode(',', $requestedRaw));
            $accessibleTabs = array_intersect_key($accessibleTabs, array_flip($requested));
        }

        // Capture each tab's HTML using the existing data methods.
        $this->printMode = true;
        $sections = array();
        foreach (array_keys($accessibleTabs) as $tab) {
            $method = 'data' . ucfirst($tab);
            ob_start();
            $this->$method();
            $sections[$tab] = ob_get_clean();
        }
        $this->printMode = false;

        // Metadata for the report header.
        $fromFmt   = date('M j, Y', strtotime($from));
        $toFmt     = date('M j, Y', strtotime($to));
        $roleLabels = array(
            'SUPER_ADMIN'         => 'Super Admin',
            'ADMIN'               => 'Admin',
            'DOCTOR'              => 'Doctor',
            'TRIAGE_NURSE'        => 'Triage Nurse',
            'NURSE'               => 'Nurse',
            'PARAMEDIC'           => 'Paramedic',
            'GRIEVANCE_OFFICER'   => 'Grievance Officer',
            'EDUCATION_TEAM'      => 'Education Team',
            'DATA_ENTRY_OPERATOR' => 'Data Entry',
        );
        $roleLabel = isset($roleLabels[$_SESSION['user_role'] ?? ''])
            ? $roleLabels[$_SESSION['user_role']]
            : ($_SESSION['user_role'] ?? '');
        $userName  = htmlspecialchars($_SESSION['user_name'] ?? '');
        $genTime   = date('M j, Y g:i A');
        $scopeNote = $isAdmin ? 'Clinic-wide data' : 'Your data only';

        header('Content-Type: text/html; charset=utf-8');
        echo $this->printPageHTML(
            $fromFmt, $toFmt, $userName, $roleLabel, $genTime, $scopeNote,
            $accessibleTabs, $sections
        );
    }

    /**
     * Render the standalone print-page HTML document.
     */
    private function printPageHTML(
        $fromFmt, $toFmt, $userName, $roleLabel, $genTime, $scopeNote,
        $accessibleTabs, $sections
    ) {
        $tabCount  = count($sections);
        $tabLabels = $accessibleTabs;

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Analytics Report | D3S3 CareSystem</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
<link rel="stylesheet" href="assets/icons/css/all.min.css" />
<link rel="stylesheet" href="assets/css/adminlte.min.css" />
<style>
/* ── Screen styles ────────────────────────────────────────────── */
body            { background: #f4f6f9; font-family: Arial, sans-serif; }
.print-wrap     { max-width: 1100px; margin: 0 auto; padding: 20px; }
.report-header  { background: #fff; border: 1px solid #dee2e6; border-radius: 6px;
                  padding: 20px 24px; margin-bottom: 24px; }
.report-meta    { font-size: .85rem; color: #6c757d; }
.section-card   { background: #fff; border: 1px solid #dee2e6; border-radius: 6px;
                  margin-bottom: 24px; overflow: hidden; }
.section-header { background: #343a40; color: #fff; padding: 10px 20px;
                  font-size: 1rem; font-weight: 600; }
.section-header i { margin-right: 8px; }
.section-body   { padding: 20px; }
.no-print-bar   { background: #fff; border-bottom: 1px solid #dee2e6;
                  padding: 10px 20px; display: flex; gap: 10px;
                  align-items: center; position: sticky; top: 0; z-index: 100; }
.no-print-bar .brand { font-weight: 700; color: #343a40; font-size: 1rem; }

/* ── Print styles ─────────────────────────────────────────────── */
@media print {
    .no-print-bar   { display: none !important; }
    body            { background: #fff; }
    .print-wrap     { max-width: 100%; padding: 0; }
    .report-header  { border: none; border-bottom: 2px solid #343a40;
                      border-radius: 0; margin-bottom: 16px; padding: 12px 0; }
    .section-card   { border: none; border-radius: 0; margin-bottom: 0;
                      page-break-inside: avoid; }
    .section-header { color: #343a40 !important; background: none !important;
                      border-bottom: 1px solid #343a40; padding: 8px 0;
                      font-size: .95rem; }
    .section-body   { padding: 10px 0; }
    .page-break     { page-break-after: always; }
    /* AdminLTE info-box adjustments */
    .info-box       { border: 1px solid #dee2e6 !important;
                      box-shadow: none !important; break-inside: avoid; }
    .card           { border: 1px solid #dee2e6 !important;
                      box-shadow: none !important; }
    /* Hide interactive elements */
    details summary::marker { display: none; }
}
</style>
</head>
<body>

<!-- Sticky bar (hidden when printing) -->
<div class="no-print-bar">
    <span class="brand">D3S3 CareSystem &mdash; Analytics Report</span>
    <button class="btn btn-sm btn-primary" onclick="window.print()">
        <i class="fas fa-print mr-1"></i>Print / Save as PDF
    </button>
    <button class="btn btn-sm btn-outline-secondary" onclick="window.close()">
        <i class="fas fa-times mr-1"></i>Close
    </button>
</div>

<div class="print-wrap">

    <!-- Report header -->
    <div class="report-header">
        <h4 class="mb-1 font-weight-bold">Analytics Report</h4>
        <div class="report-meta">
            <span class="mr-3"><i class="fas fa-calendar-alt mr-1"></i>Period: <strong><?php echo $fromFmt; ?></strong> to <strong><?php echo $toFmt; ?></strong></span>
            <span class="mr-3"><i class="fas fa-user mr-1"></i><?php echo $userName; ?> (<?php echo htmlspecialchars($roleLabel); ?>)</span>
            <span class="mr-3"><i class="fas fa-database mr-1"></i><?php echo htmlspecialchars($scopeNote); ?></span>
            <span><i class="fas fa-clock mr-1"></i>Generated: <?php echo $genTime; ?></span>
        </div>
        <?php if ($tabCount > 1): ?>
        <div class="mt-2 report-meta">
            <i class="fas fa-list mr-1"></i>Sections included:
            <?php echo implode(', ', array_values($tabLabels)); ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sections -->
    <?php
    $tabIcons = array(
        'overview'     => 'fa-tachometer-alt',
        'caseload'     => 'fa-users',
        'outcomes'     => 'fa-chart-bar',
        'satisfaction' => 'fa-smile',
        'trends'       => 'fa-chart-line',
    );
    $keys = array_keys($sections);
    foreach ($keys as $i => $tab):
        $isLast  = ($i === count($keys) - 1);
        $icon    = isset($tabIcons[$tab]) ? $tabIcons[$tab] : 'fa-chart-bar';
        $label   = isset($tabLabels[$tab]) ? $tabLabels[$tab] : ucfirst($tab);
    ?>
    <div class="section-card <?php echo (!$isLast) ? 'page-break' : ''; ?>">
        <div class="section-header">
            <i class="fas <?php echo $icon; ?>"></i><?php echo htmlspecialchars($label); ?>
        </div>
        <div class="section-body">
            <?php echo $sections[$tab]; ?>
        </div>
    </div>
    <?php endforeach; ?>

</div><!-- /print-wrap -->

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Compute role-based scope variables.
     * Use extract() on the return value to bring them into local scope.
     */
    private function buildScope()
    {
        $role = $_SESSION['user_role'] ?? '';

        $isAdmin     = in_array($role, array('SUPER_ADMIN', 'ADMIN'), true);
        $isClinical  = can($role, 'case_sheets', 'W');
        $isDoctor    = ($role === 'DOCTOR');
        $isGrievance = ($role === 'GRIEVANCE_OFFICER');
        $fullAccess  = $isAdmin;

        return array(
            'isAdmin'            => $isAdmin,
            'isClinical'         => $isClinical,
            'isDoctor'           => $isDoctor,
            'isGrievance'        => $isGrievance,
            'fullAccess'         => $fullAccess,
            'canSeeCaseload'     => $isAdmin || $isClinical,
            'canSeeOutcomes'     => $isAdmin || $isDoctor,
            'canSeeSatisfaction' => $isAdmin || $isGrievance || $isDoctor,
        );
    }

    /**
     * Parse the from/to date range from GET params.
     * Defaults to first day of current month through today.
     */
    private function getDateRange()
    {
        $today        = date('Y-m-d');
        $firstOfMonth = date('Y-m-01');

        $from = isset($_GET['from']) ? $_GET['from'] : $firstOfMonth;
        $to   = isset($_GET['to'])   ? $_GET['to']   : $today;

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = $firstOfMonth;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = $today;
        if ($from > $to) $from = $firstOfMonth;

        return array($from, $to);
    }

    /**
     * Redirect unauthenticated visitors to login.
     */
    private function requireLogin()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
    }

    /**
     * Placeholder card for unimplemented tabs.
     */
    private function stubPanel($title, $icon, $message)
    {
        return '<div class="text-center py-5 text-muted">'
             . '<i class="fas ' . $icon . ' fa-3x mb-3 d-block"></i>'
             . '<h5>' . $title . '</h5>'
             . '<p class="mb-0 small">' . $message . '</p>'
             . '</div>';
    }

    /**
     * Format minutes into a human-readable duration string.
     * 75 → "1h 15m",  45 → "45m",  120 → "2h"
     */
    public static function formatDuration($minutes)
    {
        $mins = (int)round((float)$minutes);
        if ($mins < 60) return $mins . 'm';
        $h = (int)($mins / 60);
        $m = $mins % 60;
        return $m > 0 ? $h . 'h ' . $m . 'm' : $h . 'h';
    }

    /**
     * Compute the median of an array of numeric values.
     * Returns null for an empty array.
     */
    public static function computeMedian(array $values)
    {
        if (empty($values)) return null;
        sort($values);
        $count = count($values);
        $mid   = (int)($count / 2);
        if ($count % 2 === 0) {
            return ($values[$mid - 1] + $values[$mid]) / 2.0;
        }
        return (float)$values[$mid];
    }

    // =========================================================================
    // HTML rendering: Overview tab
    // =========================================================================

    private function overviewHTML(
        $scopeLabel, $from, $to,
        $totalCases, $closedCases, $avgDuration, $medianDuration,
        $staleCases, $referrals,
        $canSeeSatisfaction, $satisfactionPct, $openComplaints
    ) {
        $fromFmt    = date('M j, Y', strtotime($from));
        $toFmt      = date('M j, Y', strtotime($to));
        $staleClass = $staleCases     > 0 ? 'text-warning' : 'text-success';
        $staleIcon  = $staleCases     > 0 ? 'fa-exclamation-triangle' : 'fa-check-circle';
        $compClass  = $openComplaints > 0 ? 'text-danger'  : 'text-success';
        $compIcon   = $openComplaints > 0 ? 'fa-exclamation-circle'  : 'fa-check-circle';

        ob_start();
        ?>
        <div class="d-flex align-items-center mb-3">
            <span class="text-muted small">
                <i class="fas fa-info-circle mr-1"></i>
                <?php echo $scopeLabel; ?> &mdash; <?php echo $fromFmt; ?> to <?php echo $toFmt; ?>
            </span>
        </div>
        <div class="row">
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-primary"><i class="fas fa-file-medical"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Cases Opened</span>
                        <span class="info-box-number"><?php echo $totalCases; ?></span>
                        <span class="progress-description text-muted small">Visits in period</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Cases Closed</span>
                        <span class="info-box-number"><?php echo $closedCases; ?></span>
                        <span class="progress-description text-muted small">Completed in period</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-info"><i class="fas fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Avg Duration</span>
                        <span class="info-box-number"><?php echo $avgDuration; ?></span>
                        <span class="progress-description text-muted small">Open to close</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-info"><i class="fas fa-equals"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Median Duration</span>
                        <span class="info-box-number"><?php echo $medianDuration; ?></span>
                        <span class="progress-description text-muted small">Outlier-resistant midpoint</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-warning"><i class="fas <?php echo $staleIcon; ?>"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Stale Cases</span>
                        <span class="info-box-number <?php echo $staleClass; ?>"><?php echo $staleCases; ?></span>
                        <span class="progress-description text-muted small">Open &gt; 7 days</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-secondary"><i class="fas fa-share-square"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Referrals Issued</span>
                        <span class="info-box-number"><?php echo $referrals; ?></span>
                        <span class="progress-description text-muted small">Closed as referral</span>
                    </div>
                </div>
            </div>
            <?php if ($canSeeSatisfaction): ?>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-success"><i class="fas fa-smile"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Satisfaction</span>
                        <span class="info-box-number"><?php echo $satisfactionPct; ?></span>
                        <span class="progress-description text-muted small">Positive feedback rate</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-danger"><i class="fas <?php echo $compIcon; ?>"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Open Complaints</span>
                        <span class="info-box-number <?php echo $compClass; ?>"><?php echo $openComplaints; ?></span>
                        <span class="progress-description text-muted small">Unresolved complaints</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // HTML rendering: Patient Trends tab
    // =========================================================================

    private function trendsHTML(
        $from, $to,
        $totalVisits, $totalPatients, $newPatients, $returning,
        $sexRows, $ageRows,
        $visitTypeRows, $dayRows,
        $complaintRows,
        $followDue, $followReturn,
        $medSourceRows, $totalMedSource,
        $medConditions, $totalWithAssessment, $otherConditions,
        $familyHistory, $totalWithFH, $otherFamilyHistory
    ) {
        $fromFmt   = date('M j, Y', strtotime($from));
        $toFmt     = date('M j, Y', strtotime($to));

        $sexLabels = array(
            'MALE'    => 'Male',
            'FEMALE'  => 'Female',
            'OTHER'   => 'Other',
            'UNKNOWN' => 'Unknown',
        );
        $sexColors = array(
            'MALE'    => 'bg-info',
            'FEMALE'  => 'bg-primary',
            'OTHER'   => 'bg-secondary',
            'UNKNOWN' => 'bg-light',
        );
        $visitTypeLabels = array(
            'CAMP'      => 'Camp',
            'CLINIC'    => 'Clinic',
            'FOLLOW_UP' => 'Follow-up',
            'EMERGENCY' => 'Emergency',
            'OTHER'     => 'Other',
        );
        $visitTypeColors = array(
            'CAMP'      => 'bg-success',
            'CLINIC'    => 'bg-primary',
            'FOLLOW_UP' => 'bg-info',
            'EMERGENCY' => 'bg-danger',
            'OTHER'     => 'bg-secondary',
        );

        $followPct    = $followDue > 0 ? round(($followReturn / $followDue) * 100) : null;
        $followClass  = ($followPct !== null && $followPct >= 70) ? 'text-success'
                      : (($followPct !== null && $followPct >= 40) ? 'text-warning' : 'text-danger');

        $totalSex  = array_sum(array_column($sexRows, 'cnt'));
        $totalAge  = !empty($ageRows)  ? array_sum(array_column($ageRows,  'cnt')) : 0;
        $maxAge    = !empty($ageRows)  ? max(array_column($ageRows,  'cnt')) : 1;
        $totalVT   = array_sum(array_column($visitTypeRows, 'cnt'));
        $totalDays = !empty($dayRows)  ? array_sum(array_column($dayRows,  'cnt')) : 0;
        $maxDay    = !empty($dayRows)  ? max(array_column($dayRows,  'cnt')) : 1;
        $maxComp  = !empty($complaintRows) ? max(array_column($complaintRows, 'cnt')) : 1;

        ob_start();
        ?>

        <div class="d-flex align-items-center mb-3">
            <span class="text-muted small">
                <i class="fas fa-info-circle mr-1"></i>
                Clinic-wide &mdash; <?php echo $fromFmt; ?> to <?php echo $toFmt; ?>
            </span>
        </div>

        <!-- ── Patient Summary cards ──────────────────────────────────────── -->
        <div class="row mb-2">
            <div class="col-sm-6 col-lg mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-dark"><i class="fas fa-calendar-alt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Visits</span>
                        <span class="info-box-number"><?php echo $totalVisits; ?></span>
                        <span class="progress-description text-muted small">All visits in period</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-primary"><i class="fas fa-user-injured"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Patients Seen</span>
                        <span class="info-box-number"><?php echo $totalPatients; ?></span>
                        <span class="progress-description text-muted small">Unique patients in period</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-success"><i class="fas fa-user-plus"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">New Patients</span>
                        <span class="info-box-number"><?php echo $newPatients; ?></span>
                        <span class="progress-description text-muted small">First visit in period</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-info"><i class="fas fa-redo"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Returning</span>
                        <span class="info-box-number"><?php echo $returning; ?></span>
                        <span class="progress-description text-muted small">Previously registered</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-warning"><i class="fas fa-calendar-check"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Follow-up Compliance</span>
                        <span class="info-box-number <?php echo $followClass; ?>">
                            <?php echo $followPct !== null ? $followPct . '%' : '&mdash;'; ?>
                        </span>
                        <span class="progress-description text-muted small">
                            <?php echo $followDue > 0 ? $followReturn . ' of ' . $followDue . ' returned' : 'No follow-ups due'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">

            <!-- ── Sex Breakdown ──────────────────────────────────────────── -->
            <div class="col-lg-4 mb-3">
                <div class="card card-outline card-primary h-100 mb-0">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-venus-mars mr-2"></i>Sex Breakdown</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($sexRows)): ?>
                        <div class="text-center py-3 text-muted small">No data in this period.</div>
                        <?php else: ?>
                        <?php foreach ($sexRows as $row):
                            $label  = isset($sexLabels[$row['sex']]) ? $sexLabels[$row['sex']] : $row['sex'];
                            $cnt    = (int)$row['cnt'];
                            $pct    = $totalSex > 0 ? round(($cnt / $totalSex) * 100) : 0;
                            $color  = isset($sexColors[$row['sex']]) ? $sexColors[$row['sex']] : 'bg-secondary';
                        ?>
                        <div class="d-flex align-items-center mb-2">
                            <div style="width:80px;" class="text-muted small"><?php echo $label; ?></div>
                            <div class="flex-grow-1 mx-2">
                                <div class="progress" style="height:14px;">
                                    <div class="progress-bar <?php echo $color; ?>"
                                         style="width:<?php echo $pct; ?>%"
                                         title="<?php echo $cnt; ?> patients">
                                    </div>
                                </div>
                            </div>
                            <div style="width:32px;" class="text-right small font-weight-bold"><?php echo $cnt; ?></div>
                            <div style="width:42px;" class="text-right small text-muted">(<?php echo $pct; ?>%)</div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ── Age Groups ──────────────────────────────────────────────── -->
            <div class="col-lg-4 mb-3">
                <div class="card card-outline card-info h-100 mb-0">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-users mr-2"></i>Age Group Distribution</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($ageRows)): ?>
                        <div class="text-center py-3 text-muted small">No data in this period.</div>
                        <?php else: ?>
                        <?php foreach ($ageRows as $row):
                            $cnt    = (int)$row['cnt'];
                            $barPct = $maxAge   > 0 ? round(($cnt / $maxAge)   * 100) : 0;
                            $agePct = $totalAge > 0 ? round(($cnt / $totalAge) * 100) : 0;
                        ?>
                        <div class="d-flex align-items-center mb-2">
                            <div style="width:80px;" class="text-muted small"><?php echo htmlspecialchars($row['age_group']); ?></div>
                            <div class="flex-grow-1 mx-2">
                                <div class="progress" style="height:14px;">
                                    <div class="progress-bar bg-info" style="width:<?php echo $barPct; ?>%"></div>
                                </div>
                            </div>
                            <div style="width:32px;" class="text-right small font-weight-bold"><?php echo $cnt; ?></div>
                            <div style="width:42px;" class="text-right small text-muted">(<?php echo $agePct; ?>%)</div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ── Medicine Sources ────────────────────────────────────────── -->
            <div class="col-lg-4 mb-3">
                <div class="card card-outline card-success h-100 mb-0">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-pills mr-2"></i>Medicine Sources</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $medSourceLabels = array('NONE' => 'None', 'PRIVATE' => 'Private', 'GOVERNMENT' => 'Government');
                        $medSourceColors = array('NONE' => 'bg-secondary', 'PRIVATE' => 'bg-info', 'GOVERNMENT' => 'bg-success');
                        ?>
                        <?php if (empty($medSourceRows) || $totalMedSource === 0): ?>
                        <div class="text-center py-3 text-muted small">No medicine source data recorded in this period.</div>
                        <?php else: ?>
                        <p class="text-muted small mb-3">
                            <i class="fas fa-info-circle mr-1"></i>
                            Based on <?php echo $totalMedSource; ?> patient<?php echo $totalMedSource !== 1 ? 's' : ''; ?> (most recent visit).
                        </p>
                        <?php foreach ($medSourceRows as $row):
                            $src    = $row['med_source'];
                            $label  = isset($medSourceLabels[$src]) ? $medSourceLabels[$src] : htmlspecialchars($src);
                            $color  = isset($medSourceColors[$src]) ? $medSourceColors[$src] : 'bg-secondary';
                            $cnt    = (int)$row['cnt'];
                            $pct    = $totalMedSource > 0 ? round(($cnt / $totalMedSource) * 100) : 0;
                        ?>
                        <div class="d-flex align-items-center mb-2">
                            <div style="width:90px;" class="text-muted small"><?php echo $label; ?></div>
                            <div class="flex-grow-1 mx-2">
                                <div class="progress" style="height:14px;">
                                    <div class="progress-bar <?php echo $color; ?>" style="width:<?php echo $pct; ?>%"></div>
                                </div>
                            </div>
                            <div style="width:32px;" class="text-right small font-weight-bold"><?php echo $cnt; ?></div>
                            <div style="width:42px;" class="text-right small text-muted">(<?php echo $pct; ?>%)</div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div><!-- /demographics row -->

        <div class="row">

            <!-- ── Visit Type Breakdown ───────────────────────────────────── -->
            <div class="col-lg-6 mb-3">
                <div class="card card-outline card-success h-100 mb-0">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-tag mr-2"></i>Visit Type Breakdown</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($visitTypeRows)): ?>
                        <div class="text-center py-3 text-muted small">No visits in this period.</div>
                        <?php else: ?>
                        <?php foreach ($visitTypeRows as $row):
                            $type   = $row['visit_type'];
                            $label  = isset($visitTypeLabels[$type]) ? $visitTypeLabels[$type] : $type;
                            $color  = isset($visitTypeColors[$type]) ? $visitTypeColors[$type] : 'bg-secondary';
                            $cnt    = (int)$row['cnt'];
                            $pct    = $totalVT > 0 ? round(($cnt / $totalVT) * 100) : 0;
                        ?>
                        <div class="d-flex align-items-center mb-2">
                            <div style="width:80px;" class="text-muted small"><?php echo htmlspecialchars($label); ?></div>
                            <div class="flex-grow-1 mx-2">
                                <div class="progress" style="height:14px;">
                                    <div class="progress-bar <?php echo $color; ?>" style="width:<?php echo $pct; ?>%"></div>
                                </div>
                            </div>
                            <div style="width:32px;" class="text-right small font-weight-bold"><?php echo $cnt; ?></div>
                            <div style="width:42px;" class="text-right small text-muted">(<?php echo $pct; ?>%)</div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ── Peak Days ──────────────────────────────────────────────── -->
            <div class="col-lg-6 mb-3">
                <div class="card card-outline card-warning h-100 mb-0">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-calendar-week mr-2"></i>Visits by Day of Week</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($dayRows)): ?>
                        <div class="text-center py-3 text-muted small">No visits in this period.</div>
                        <?php else: ?>
                        <?php foreach ($dayRows as $row):
                            $cnt    = (int)$row['cnt'];
                            $barPct = $maxDay    > 0 ? round(($cnt / $maxDay)    * 100) : 0;
                            $dayPct = $totalDays > 0 ? round(($cnt / $totalDays) * 100) : 0;
                            $isPeak = ($cnt === $maxDay);
                        ?>
                        <div class="d-flex align-items-center mb-2">
                            <div style="width:80px;" class="text-muted small <?php echo $isPeak ? 'font-weight-bold text-dark' : ''; ?>">
                                <?php echo htmlspecialchars($row['day_name']); ?>
                            </div>
                            <div class="flex-grow-1 mx-2">
                                <div class="progress" style="height:14px;">
                                    <div class="progress-bar <?php echo $isPeak ? 'bg-warning' : 'bg-light border'; ?>"
                                         style="width:<?php echo $barPct; ?>%;<?php echo !$isPeak ? 'color:#666' : ''; ?>">
                                    </div>
                                </div>
                            </div>
                            <div style="width:32px;" class="text-right small font-weight-bold <?php echo $isPeak ? 'text-warning' : ''; ?>"><?php echo $cnt; ?></div>
                            <div style="width:42px;" class="text-right small text-muted">(<?php echo $dayPct; ?>%)</div>
                        </div>
                        <?php endforeach; ?>
                        <p class="text-muted small mb-0 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Highlighted bar is the busiest day in the period.
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div><!-- /patterns row -->

        <!-- ── Chief Complaint Frequency ──────────────────────────────────── -->
        <div class="card card-outline card-secondary mb-0">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-comment-medical mr-2"></i>Most Common Chief Complaints</h3>
            </div>
            <div class="card-body">
                <?php if (empty($complaintRows)): ?>
                <div class="text-center py-3 text-muted small">No chief complaints recorded in this period.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Chief Complaint</th>
                                <th class="text-center">Cases</th>
                                <th style="width:40%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($complaintRows as $row):
                                $cnt    = (int)$row['cnt'];
                                $barPct = $maxComp > 0 ? round(($cnt / $maxComp) * 100) : 0;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['chief_complaint']); ?></td>
                                <td class="text-center font-weight-bold"><?php echo $cnt; ?></td>
                                <td>
                                    <div class="progress" style="height:8px;margin-top:4px;">
                                        <div class="progress-bar bg-secondary" style="width:<?php echo $barPct; ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted small mt-2 mb-0">
                    <i class="fas fa-info-circle mr-1"></i>
                    Ranked by exact text match. Consistent data entry improves the usefulness of this report.
                </p>
                <?php endif; ?>
            </div>
        </div>
        <!-- ── Medical Conditions ─────────────────────────────────────────── -->
        <div class="card card-outline card-danger mb-3">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-notes-medical mr-2"></i>Medical Conditions at Intake</h3>
            </div>
            <div class="card-body">
                <?php if ($totalWithAssessment === 0): ?>
                <div class="text-center py-3 text-muted small">No medical conditions data recorded in this period.</div>
                <?php else: ?>
                <p class="text-muted small mb-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    Based on <?php echo $totalWithAssessment; ?> patient<?php echo $totalWithAssessment !== 1 ? 's' : ''; ?> (most recent visit per patient). Includes current and past conditions.
                </p>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Condition</th>
                                <th class="text-center">Current</th>
                                <th class="text-center">Past</th>
                                <th class="text-center">Any</th>
                                <th class="text-center">% of Cases</th>
                                <th style="width:30%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($medConditions as $condLabel => $counts):
                                $curr  = $counts['current'];
                                $past  = $counts['past'];
                                $any   = $curr + $past;
                                $pct   = $totalWithAssessment > 0 ? round(($any / $totalWithAssessment) * 100) : 0;
                                $barPct = $pct;
                            ?>
                            <tr>
                                <td class="font-weight-bold"><?php echo htmlspecialchars($condLabel); ?></td>
                                <td class="text-center">
                                    <?php if ($curr > 0): ?>
                                    <span class="badge badge-danger"><?php echo $curr; ?></span>
                                    <?php else: ?>
                                    <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($past > 0): ?>
                                    <span class="badge badge-secondary"><?php echo $past; ?></span>
                                    <?php else: ?>
                                    <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center font-weight-bold"><?php echo $any; ?></td>
                                <td class="text-center text-muted"><?php echo $pct; ?>%</td>
                                <td>
                                    <div class="progress" style="height:8px;margin-top:4px;">
                                        <div class="progress-bar bg-danger" style="width:<?php echo $barPct; ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($otherConditions)): ?>
                <details class="mt-3">
                    <summary class="text-muted small" style="cursor:pointer;user-select:none;outline:none;padding:6px 0;">
                        <i class="fas fa-chevron-right mr-1" style="font-size:.75rem;transition:transform .2s;"></i>
                        <strong>Other Conditions</strong>
                        <span class="badge badge-secondary ml-1"><?php echo count($otherConditions); ?> entr<?php echo count($otherConditions) !== 1 ? 'ies' : 'y'; ?></span>
                        — click to expand
                    </summary>
                    <div class="mt-2 pl-3">
                        <table class="table table-sm mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Entry</th>
                                    <th class="text-center" style="width:80px;">Cases</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($otherConditions as $oc): ?>
                                <tr>
                                    <td class="small"><?php echo htmlspecialchars($oc['txt']); ?></td>
                                    <td class="text-center small"><?php echo (int)$oc['cnt']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p class="text-muted small mt-1 mb-0">Free-text entries grouped by exact match.</p>
                    </div>
                </details>
                <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>

        <!-- ── Family History ─────────────────────────────────────────────── -->
        <div class="card card-outline card-warning mb-0">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-dna mr-2"></i>Family History at Intake</h3>
            </div>
            <div class="card-body">
                <?php if ($totalWithFH === 0): ?>
                <div class="text-center py-3 text-muted small">No family history data recorded in this period.</div>
                <?php else: ?>
                <p class="text-muted small mb-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    Based on <?php echo $totalWithFH; ?> patient<?php echo $totalWithFH !== 1 ? 's' : ''; ?> (most recent visit per patient).
                </p>
                <?php
                $maxFH = max(max(array_values($familyHistory)), 1);
                foreach ($familyHistory as $fhLabel => $cnt):
                    $pct    = $totalWithFH > 0 ? round(($cnt / $totalWithFH) * 100) : 0;
                    $barPct = $maxFH > 0 ? round(($cnt / $maxFH) * 100) : 0;
                ?>
                <div class="d-flex align-items-center mb-2">
                    <div style="width:130px;" class="text-muted small"><?php echo htmlspecialchars($fhLabel); ?></div>
                    <div class="flex-grow-1 mx-2">
                        <div class="progress" style="height:14px;">
                            <div class="progress-bar bg-warning" style="width:<?php echo $barPct; ?>%"></div>
                        </div>
                    </div>
                    <div style="width:32px;" class="text-right small font-weight-bold"><?php echo $cnt; ?></div>
                    <div style="width:42px;" class="text-right small text-muted">(<?php echo $pct; ?>%)</div>
                </div>
                <?php endforeach; ?>

                <?php if (!empty($otherFamilyHistory)): ?>
                <details class="mt-3">
                    <summary class="text-muted small" style="cursor:pointer;user-select:none;outline:none;padding:6px 0;">
                        <i class="fas fa-chevron-right mr-1" style="font-size:.75rem;transition:transform .2s;"></i>
                        <strong>Other Family History</strong>
                        <span class="badge badge-secondary ml-1"><?php echo count($otherFamilyHistory); ?> entr<?php echo count($otherFamilyHistory) !== 1 ? 'ies' : 'y'; ?></span>
                        — click to expand
                    </summary>
                    <div class="mt-2 pl-3">
                        <table class="table table-sm mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Entry</th>
                                    <th class="text-center" style="width:80px;">Cases</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($otherFamilyHistory as $ofh): ?>
                                <tr>
                                    <td class="small"><?php echo htmlspecialchars($ofh['txt']); ?></td>
                                    <td class="text-center small"><?php echo (int)$ofh['cnt']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p class="text-muted small mt-1 mb-0">Free-text entries grouped by exact match.</p>
                    </div>
                </details>
                <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>

        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // HTML rendering: Satisfaction tab
    // =========================================================================

    private function satisfactionHTML(
        $from, $to, $isAdmin, $isGrievance,
        $totalFb, $positiveCnt, $complaintCnt, $suggestionCnt,
        $avgRating, $ratedCount,
        $pipelineStatuses, $totalComplaints, $resolutionRate,
        $byStaffRows, $recentComplaints
    ) {
        $fromFmt       = date('M j, Y', strtotime($from));
        $toFmt         = date('M j, Y', strtotime($to));
        $scope         = ($isAdmin || $isGrievance) ? 'Clinic-wide' : 'Your feedback';
        $satisfPct     = $totalFb > 0 ? round(($positiveCnt / $totalFb) * 100) : null;
        $satisfClass   = ($satisfPct !== null && $satisfPct >= 70) ? 'text-success'
                       : (($satisfPct !== null && $satisfPct >= 40) ? 'text-warning' : 'text-danger');

        $statusLabels  = array(
            'NEW'      => 'New',
            'REVIEWED' => 'Reviewed',
            'ACTIONED' => 'Actioned',
            'CLOSED'   => 'Closed',
        );
        $statusBadge   = array(
            'NEW'      => 'badge-danger',
            'REVIEWED' => 'badge-warning',
            'ACTIONED' => 'badge-info',
            'CLOSED'   => 'badge-success',
        );
        $roleLabels = array(
            'SUPER_ADMIN'         => 'Super Admin',
            'ADMIN'               => 'Admin',
            'DOCTOR'              => 'Doctor',
            'TRIAGE_NURSE'        => 'Triage Nurse',
            'NURSE'               => 'Nurse',
            'PARAMEDIC'           => 'Paramedic',
            'GRIEVANCE_OFFICER'   => 'Grievance Officer',
            'EDUCATION_TEAM'      => 'Education Team',
            'DATA_ENTRY_OPERATOR' => 'Data Entry',
        );

        ob_start();
        ?>

        <div class="d-flex align-items-center mb-3">
            <span class="text-muted small">
                <i class="fas fa-info-circle mr-1"></i>
                <?php echo $scope; ?> &mdash; <?php echo $fromFmt; ?> to <?php echo $toFmt; ?>
            </span>
        </div>

        <!-- ── Summary cards ─────────────────────────────────────────────── -->
        <div class="row mb-2">
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-primary"><i class="fas fa-comment-dots"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Feedback</span>
                        <span class="info-box-number"><?php echo $totalFb; ?></span>
                        <span class="progress-description text-muted small">In period</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-success"><i class="fas fa-smile"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Satisfaction Rate</span>
                        <span class="info-box-number <?php echo $satisfClass; ?>">
                            <?php echo $satisfPct !== null ? $satisfPct . '%' : '&mdash;'; ?>
                        </span>
                        <span class="progress-description text-muted small">Positive feedback</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-warning"><i class="fas fa-star"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Avg Rating</span>
                        <span class="info-box-number">
                            <?php echo $avgRating !== null ? $avgRating . ' / 5' : '&mdash;'; ?>
                        </span>
                        <span class="progress-description text-muted small">
                            <?php echo $ratedCount > 0 ? 'From ' . $ratedCount . ' rated entries' : 'No ratings recorded'; ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Open Complaints</span>
                        <span class="info-box-number <?php echo ($totalComplaints - $pipelineStatuses['CLOSED']) > 0 ? 'text-danger' : 'text-success'; ?>">
                            <?php echo $totalComplaints - $pipelineStatuses['CLOSED']; ?>
                        </span>
                        <span class="progress-description text-muted small">Not yet resolved</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Feedback Type Breakdown ────────────────────────────────────── -->
        <div class="card card-outline card-success mb-3">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i>Feedback Breakdown</h3>
            </div>
            <div class="card-body">
                <?php if ($totalFb === 0): ?>
                <div class="text-center py-3 text-muted small">
                    No feedback recorded in this period.
                </div>
                <?php else: ?>
                <div class="row">
                    <?php
                    $types = array(
                        array('POSITIVE',   $positiveCnt,   'bg-success', 'fa-smile',            'Positive'),
                        array('COMPLAINT',  $complaintCnt,  'bg-danger',  'fa-exclamation-circle','Complaints'),
                        array('SUGGESTION', $suggestionCnt, 'bg-info',    'fa-lightbulb',         'Suggestions'),
                    );
                    foreach ($types as $t):
                        list($key, $cnt, $color, $icon, $label) = $t;
                        $pct = $totalFb > 0 ? round(($cnt / $totalFb) * 100) : 0;
                    ?>
                    <div class="col-sm-4 mb-3">
                        <div class="d-flex align-items-center">
                            <span class="info-box-icon <?php echo $color; ?> mr-3"
                                  style="width:42px;height:42px;border-radius:6px;display:flex;align-items:center;justify-content:center;">
                                <i class="fas <?php echo $icon; ?> text-white"></i>
                            </span>
                            <div>
                                <div class="font-weight-bold"><?php echo $cnt; ?> <span class="text-muted font-weight-normal small">(<?php echo $pct; ?>%)</span></div>
                                <div class="text-muted small"><?php echo $label; ?></div>
                            </div>
                        </div>
                        <div class="progress mt-2" style="height:6px;">
                            <div class="progress-bar <?php echo $color; ?>" style="width:<?php echo $pct; ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Complaint Status Pipeline ─────────────────────────────────── -->
        <div class="card card-outline card-danger mb-3">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-tasks mr-2"></i>Complaint Status Pipeline
                    <?php if ($resolutionRate !== null): ?>
                    <small class="text-muted ml-2">Resolution rate:
                        <strong class="<?php echo $resolutionRate >= 70 ? 'text-success' : 'text-warning'; ?>">
                            <?php echo $resolutionRate; ?>%
                        </strong>
                    </small>
                    <?php endif; ?>
                </h3>
            </div>
            <div class="card-body">
                <?php if ($totalComplaints === 0): ?>
                <div class="text-center py-3 text-muted small">No complaints on record.</div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($pipelineStatuses as $status => $cnt): ?>
                    <div class="col-sm-6 col-lg-3 mb-3">
                        <div class="text-center p-3 border rounded">
                            <div class="h3 mb-1 font-weight-bold"><?php echo $cnt; ?></div>
                            <span class="badge <?php echo isset($statusBadge[$status]) ? $statusBadge[$status] : 'badge-secondary'; ?>">
                                <?php echo isset($statusLabels[$status]) ? $statusLabels[$status] : $status; ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-muted small mb-0">
                    <i class="fas fa-info-circle mr-1"></i>
                    Pipeline shows <strong>all complaints</strong> regardless of date range,
                    so unresolved older complaints remain visible.
                </p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (($isAdmin || $isGrievance) && !empty($byStaffRows)): ?>
        <!-- ── Feedback by Staff Member ───────────────────────────────────── -->
        <div class="card card-outline card-info mb-3">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-users mr-2"></i>Feedback by Staff Member
                    <span class="badge badge-secondary ml-1" style="font-size:.72rem;">
                        <?php echo $isAdmin ? 'Admin view' : 'Grievance Officer view'; ?>
                    </span>
                </h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Staff Member</th>
                                <th>Role</th>
                                <th class="text-center text-success">Positive</th>
                                <th class="text-center text-danger">Complaints</th>
                                <th class="text-center text-info">Suggestions</th>
                                <th class="text-center">Avg Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($byStaffRows as $row): ?>
                            <tr>
                                <td class="font-weight-bold"><?php echo htmlspecialchars(trim($row['first_name'] . ' ' . $row['last_name'])); ?></td>
                                <td><span class="badge badge-secondary"><?php echo htmlspecialchars(isset($roleLabels[$row['role']]) ? $roleLabels[$row['role']] : $row['role']); ?></span></td>
                                <td class="text-center <?php echo (int)$row['positive_cnt']  > 0 ? 'text-success font-weight-bold' : 'text-muted'; ?>"><?php echo (int)$row['positive_cnt']; ?></td>
                                <td class="text-center <?php echo (int)$row['complaint_cnt'] > 0 ? 'text-danger  font-weight-bold' : 'text-muted'; ?>"><?php echo (int)$row['complaint_cnt']; ?></td>
                                <td class="text-center <?php echo (int)$row['suggestion_cnt']> 0 ? 'text-info   font-weight-bold' : 'text-muted'; ?>"><?php echo (int)$row['suggestion_cnt']; ?></td>
                                <td class="text-center"><?php echo $row['avg_rating'] !== null ? $row['avg_rating'] . ' / 5' : '&mdash;'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Recent Complaints ──────────────────────────────────────────── -->
        <div class="card card-outline card-warning mb-0">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list-alt mr-2"></i>Recent Complaints
                    <span class="badge badge-warning ml-2"><?php echo $totalComplaints; ?> total</span>
                </h3>
            </div>
            <div class="card-body">
                <?php if (empty($recentComplaints)): ?>
                <div class="text-center py-3 text-muted small">No complaints on record.</div>
                <?php else: ?>
                <?php if ($totalComplaints > 20): ?>
                <p class="text-muted small mb-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    Showing most recent 20 of <?php echo $totalComplaints; ?> complaints.
                </p>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>Re: Staff</th>
                                <th>Status</th>
                                <th>Excerpt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentComplaints as $row): ?>
                            <tr>
                                <td class="text-muted small text-nowrap"><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <span class="badge badge-light text-dark border"><?php echo htmlspecialchars($row['patient_code'] ?? ''); ?></span>
                                    <span class="small ml-1"><?php echo htmlspecialchars(trim(($row['pat_first'] ?? '') . ' ' . ($row['pat_last'] ?? ''))); ?></span>
                                </td>
                                <td class="small">
                                    <?php
                                    $staffName = trim(($row['staff_first'] ?? '') . ' ' . ($row['staff_last'] ?? ''));
                                    echo $staffName ? htmlspecialchars($staffName) : '<span class="text-muted">&mdash;</span>';
                                    ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo isset($statusBadge[$row['status']]) ? $statusBadge[$row['status']] : 'badge-secondary'; ?>">
                                        <?php echo isset($statusLabels[$row['status']]) ? $statusLabels[$row['status']] : $row['status']; ?>
                                    </span>
                                </td>
                                <td class="small text-muted" style="max-width:280px;">
                                    <?php
                                    $text = $row['feedback_text'] ?? '';
                                    echo $text ? htmlspecialchars(mb_substr($text, 0, 120) . (mb_strlen($text) > 120 ? '…' : '')) : '<em>No text</em>';
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // HTML rendering: Caseload tab
    // =========================================================================

    private function caseloadHTML(
        $from, $to, $isAdmin,
        $intakeRows, $intakeTimings, $closureRows, $caseloadDoctorMedians, $roleRows,
        $scheduled, $walkin, $noShowRows
    ) {
        $fromFmt   = date('M j, Y', strtotime($from));
        $toFmt     = date('M j, Y', strtotime($to));
        $scope     = $isAdmin ? 'Clinic-wide' : 'Your cases';
        $total     = $scheduled + $walkin;
        $schedPct  = $total > 0 ? round(($scheduled / $total) * 100) : 0;
        $walkinPct = $total > 0 ? round(($walkin    / $total) * 100) : 0;

        $roleLabels = array(
            'SUPER_ADMIN'         => 'Super Admin',
            'ADMIN'               => 'Admin',
            'DOCTOR'              => 'Doctor',
            'TRIAGE_NURSE'        => 'Triage Nurse',
            'NURSE'               => 'Nurse',
            'PARAMEDIC'           => 'Paramedic',
            'GRIEVANCE_OFFICER'   => 'Grievance Officer',
            'EDUCATION_TEAM'      => 'Education Team',
            'DATA_ENTRY_OPERATOR' => 'Data Entry',
        );

        ob_start();
        ?>

        <div class="d-flex align-items-center mb-3">
            <span class="text-muted small">
                <i class="fas fa-info-circle mr-1"></i>
                <?php echo $scope; ?> &mdash; <?php echo $fromFmt; ?> to <?php echo $toFmt; ?>
            </span>
        </div>

        <!-- ── Summary cards ─────────────────────────────────────────────── -->
        <div class="row mb-2">
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-primary"><i class="fas fa-clipboard-list"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Cases Opened</span>
                        <span class="info-box-number"><?php echo array_sum(array_column($intakeRows, 'cases_opened')); ?></span>
                        <span class="progress-description text-muted small">By all staff in period</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Cases Closed</span>
                        <span class="info-box-number"><?php echo array_sum(array_column($closureRows, 'cases_closed')); ?></span>
                        <span class="progress-description text-muted small">By doctors in period</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-info"><i class="fas fa-calendar-check"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Scheduled</span>
                        <span class="info-box-number"><?php echo $scheduled; ?> <small class="text-muted" style="font-size:.55em;"><?php echo $schedPct; ?>%</small></span>
                        <span class="progress-description text-muted small">Had prior appointment</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="info-box shadow-none border">
                    <span class="info-box-icon bg-warning"><i class="fas fa-walking"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Walk-ins</span>
                        <span class="info-box-number"><?php echo $walkin; ?> <small class="text-muted" style="font-size:.55em;"><?php echo $walkinPct; ?>%</small></span>
                        <span class="progress-description text-muted small">No prior appointment</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Nurse Intake Volume ────────────────────────────────────────── -->
        <div class="card card-outline card-primary mb-3">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clipboard-list mr-2"></i>Intake Volume by Staff Member</h3>
            </div>
            <div class="card-body">
                <?php if (empty($intakeRows)): ?>
                <div class="text-center py-3 text-muted small">No intake activity in this period.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Staff Member</th>
                                <th>Role</th>
                                <th class="text-center">Cases Opened</th>
                                <th class="text-center">Avg Intake Time</th>
                                <th class="text-center">Median Intake Time</th>
                                <th style="width:20%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $maxIntake = max(array_column($intakeRows, 'cases_opened'));
                            foreach ($intakeRows as $row):
                                $name      = htmlspecialchars(trim($row['first_name'] . ' ' . $row['last_name']));
                                $role      = htmlspecialchars(isset($roleLabels[$row['role']]) ? $roleLabels[$row['role']] : $row['role']);
                                $cnt       = (int)$row['cases_opened'];
                                $barPct    = $maxIntake > 0 ? round(($cnt / $maxIntake) * 100) : 0;
                                $timing    = isset($intakeTimings[$row['user_id']]) ? $intakeTimings[$row['user_id']] : null;
                                $avgIntake = ($timing && $timing['avg'] !== null)    ? self::formatDuration($timing['avg'])    : '&mdash;';
                                $medIntake = ($timing && $timing['median'] !== null) ? self::formatDuration($timing['median']) : '&mdash;';
                            ?>
                            <tr>
                                <td class="font-weight-bold"><?php echo $name; ?></td>
                                <td><span class="badge badge-secondary"><?php echo $role; ?></span></td>
                                <td class="text-center"><?php echo $cnt; ?></td>
                                <td class="text-center font-weight-bold"><?php echo $avgIntake; ?></td>
                                <td class="text-center text-muted"><?php echo $medIntake; ?></td>
                                <td>
                                    <div class="progress" style="height:8px;margin-top:4px;">
                                        <div class="progress-bar bg-primary" style="width:<?php echo $barPct; ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted small mt-2 mb-0">
                    <i class="fas fa-info-circle mr-1"></i>
                    Intake time measured from visit start to intake completion using the audit log.
                    Cases where intake spans multiple sessions may show larger values.
                </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Doctor Case Closures ───────────────────────────────────────── -->
        <div class="card card-outline card-success mb-3">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-md mr-2"></i>Case Closures by Doctor</h3>
            </div>
            <div class="card-body">
                <?php if (empty($closureRows)): ?>
                <div class="text-center py-3 text-muted small">No case closures in this period.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Doctor</th>
                                <th class="text-center">Cases Closed</th>
                                <th class="text-center">Avg Duration</th>
                                <th class="text-center">Median Duration</th>
                                <th style="width:30%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $maxClosed = max(array_column($closureRows, 'cases_closed'));
                            foreach ($closureRows as $row):
                                $name      = htmlspecialchars(trim($row['first_name'] . ' ' . $row['last_name']));
                                $cnt       = (int)$row['cases_closed'];
                                $avgFmt    = ($row['avg_dur_min'] !== null && $row['avg_dur_min'] > 0)
                                             ? self::formatDuration((float)$row['avg_dur_min'])
                                             : '&mdash;';
                                $medVal    = isset($caseloadDoctorMedians[$row['user_id']]) ? $caseloadDoctorMedians[$row['user_id']] : null;
                                $medFmt    = ($medVal !== null) ? self::formatDuration($medVal) : '&mdash;';
                                $barPct    = $maxClosed > 0 ? round(($cnt / $maxClosed) * 100) : 0;
                            ?>
                            <tr>
                                <td class="font-weight-bold"><?php echo $name; ?></td>
                                <td class="text-center"><?php echo $cnt; ?></td>
                                <td class="text-center font-weight-bold"><?php echo $avgFmt; ?></td>
                                <td class="text-center text-muted"><?php echo $medFmt; ?></td>
                                <td>
                                    <div class="progress" style="height:8px;margin-top:4px;">
                                        <div class="progress-bar bg-success" style="width:<?php echo $barPct; ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isAdmin && !empty($noShowRows)): ?>
        <!-- ── Appointment No-show Rate ───────────────────────────────────── -->
        <div class="card card-outline card-warning mb-3">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user-times mr-2"></i>Appointment No-show Rate by Doctor
                    <span class="badge badge-secondary ml-1" style="font-size:.72rem;">Admin view</span>
                </h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Doctor</th>
                                <th class="text-center">Total Appts</th>
                                <th class="text-center">No-shows</th>
                                <th class="text-center">Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($noShowRows as $row): ?>
                            <?php
                                $name      = htmlspecialchars(trim($row['first_name'] . ' ' . $row['last_name']));
                                $noShows   = (int)$row['no_shows'];
                                $totalAppt = (int)$row['total_appts'];
                                $rate      = $totalAppt > 0 ? round(($noShows / $totalAppt) * 100) : 0;
                                $rateClass = $rate >= 20 ? 'text-danger' : ($rate >= 10 ? 'text-warning' : 'text-success');
                            ?>
                            <tr>
                                <td class="font-weight-bold"><?php echo $name; ?></td>
                                <td class="text-center"><?php echo $totalAppt; ?></td>
                                <td class="text-center"><?php echo $noShows; ?></td>
                                <td class="text-center font-weight-bold <?php echo $rateClass; ?>"><?php echo $rate; ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted small mt-2 mb-0">
                    <i class="fas fa-info-circle mr-1"></i>
                    Rate calculated from completed, cancelled, and no-show appointments only.
                    Rates &ge;20% are flagged in red; &ge;10% in amber.
                </p>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($isAdmin && !empty($roleRows)): ?>
        <!-- ── Role-level Summary ─────────────────────────────────────────── -->
        <div class="card card-outline card-secondary mb-0">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-layer-group mr-2"></i>Intake Volume by Role
                    <span class="badge badge-secondary ml-1" style="font-size:.72rem;">Admin view</span>
                </h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Role</th>
                                <th class="text-center">Cases Opened</th>
                                <th style="width:40%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $maxRole = max(array_column($roleRows, 'total_cases'));
                            foreach ($roleRows as $row):
                                $roleLabel = htmlspecialchars(isset($roleLabels[$row['role']]) ? $roleLabels[$row['role']] : $row['role']);
                                $cnt       = (int)$row['total_cases'];
                                $barPct    = $maxRole > 0 ? round(($cnt / $maxRole) * 100) : 0;
                            ?>
                            <tr>
                                <td><?php echo $roleLabel; ?></td>
                                <td class="text-center font-weight-bold"><?php echo $cnt; ?></td>
                                <td>
                                    <div class="progress" style="height:8px;margin-top:4px;">
                                        <div class="progress-bar bg-secondary" style="width:<?php echo $barPct; ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // HTML rendering: Outcomes tab
    // =========================================================================

    private function outcomesHTML(
        $from, $to, $isAdmin,
        $totalClosed, $avgMin, $medianMin, $minMin, $maxMin,
        $durByType, $typeMedians, $durByDoctor, $doctorMedians,
        $closureRows, $totalClosureCount,
        $referralRows, $totalReferrals
    ) {
        $fromFmt = date('M j, Y', strtotime($from));
        $toFmt   = date('M j, Y', strtotime($to));
        $scope   = $isAdmin ? 'Clinic-wide' : 'Your cases';

        $typeLabels = array(
            'CAMP'      => 'Camp',
            'CLINIC'    => 'Clinic',
            'FOLLOW_UP' => 'Follow-up',
            'EMERGENCY' => 'Emergency',
            'OTHER'     => 'Other',
        );
        $closureLabels = array(
            'DISCHARGED' => 'Discharged',
            'FOLLOW_UP'  => 'Follow-up',
            'REFERRAL'   => 'Referral',
            'CANCELLED'  => 'Cancelled',
            'PENDING'    => 'Pending',
        );
        $closureBadge = array(
            'DISCHARGED' => 'badge-success',
            'FOLLOW_UP'  => 'badge-info',
            'REFERRAL'   => 'badge-warning',
            'CANCELLED'  => 'badge-secondary',
            'PENDING'    => 'badge-danger',
        );
        $closureBar = array(
            'DISCHARGED' => 'bg-success',
            'FOLLOW_UP'  => 'bg-info',
            'REFERRAL'   => 'bg-warning',
            'CANCELLED'  => 'bg-secondary',
            'PENDING'    => 'bg-danger',
        );

        ob_start();
        ?>

        <div class="d-flex align-items-center mb-3">
            <span class="text-muted small">
                <i class="fas fa-info-circle mr-1"></i>
                <?php echo $scope; ?> &mdash; <?php echo $fromFmt; ?> to <?php echo $toFmt; ?>
            </span>
        </div>

        <!-- ── Case Duration ─────────────────────────────────────────────── -->
        <div class="card card-outline card-info mb-3">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clock mr-2"></i>Case Duration</h3>
            </div>
            <div class="card-body">
                <?php if ($totalClosed === 0): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-clock fa-2x mb-2 d-block"></i>
                    <p class="mb-0 small">No closed cases in this date range.</p>
                </div>
                <?php else: ?>
                <div class="row">
                    <div class="col-sm-6 col-lg mb-3">
                        <div class="info-box shadow-none border">
                            <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Closed Cases</span>
                                <span class="info-box-number"><?php echo $totalClosed; ?></span>
                                <span class="progress-description text-muted small">In period</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg mb-3">
                        <div class="info-box shadow-none border">
                            <span class="info-box-icon bg-info"><i class="fas fa-clock"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Avg Duration</span>
                                <span class="info-box-number"><?php echo $avgMin > 0 ? self::formatDuration($avgMin) : '&mdash;'; ?></span>
                                <span class="progress-description text-muted small">Open to close</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg mb-3">
                        <div class="info-box shadow-none border">
                            <span class="info-box-icon bg-info"><i class="fas fa-equals"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Median Duration</span>
                                <span class="info-box-number"><?php echo $medianMin !== null ? self::formatDuration($medianMin) : '&mdash;'; ?></span>
                                <span class="progress-description text-muted small">Outlier-resistant midpoint</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg mb-3">
                        <div class="info-box shadow-none border">
                            <span class="info-box-icon bg-primary"><i class="fas fa-arrow-down"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Shortest</span>
                                <span class="info-box-number"><?php echo $minMin > 0 ? self::formatDuration($minMin) : '&mdash;'; ?></span>
                                <span class="progress-description text-muted small">Fastest closure</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg mb-3">
                        <div class="info-box shadow-none border">
                            <span class="info-box-icon bg-warning"><i class="fas fa-arrow-up"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Longest</span>
                                <span class="info-box-number"><?php echo $maxMin > 0 ? self::formatDuration($maxMin) : '&mdash;'; ?></span>
                                <span class="progress-description text-muted small">Slowest closure</span>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-muted small mb-0">
                    <i class="fas fa-info-circle mr-1"></i>
                    Durations are calculated from <strong>visit start to chart close</strong>.
                    Cases spanning multiple days will show larger values &mdash; use the date range to investigate outliers.
                </p>

                <?php if (!empty($durByType)): ?>
                <h6 class="font-weight-bold text-muted mt-4 mb-2">
                    <i class="fas fa-tag mr-1"></i>Duration by Visit Type
                </h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Visit Type</th>
                                <th class="text-center">Cases</th>
                                <th class="text-center">Avg Duration</th>
                                <th class="text-center">Median Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($durByType as $row): ?>
                            <?php $typeMedianFmt = isset($typeMedians[$row['visit_type']]) && $typeMedians[$row['visit_type']] !== null
                                ? self::formatDuration($typeMedians[$row['visit_type']]) : '&mdash;'; ?>
                            <tr>
                                <td><?php echo htmlspecialchars(isset($typeLabels[$row['visit_type']]) ? $typeLabels[$row['visit_type']] : $row['visit_type']); ?></td>
                                <td class="text-center"><?php echo (int)$row['cnt']; ?></td>
                                <td class="text-center font-weight-bold"><?php echo self::formatDuration((float)$row['avg_min']); ?></td>
                                <td class="text-center text-muted"><?php echo $typeMedianFmt; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <?php if ($isAdmin && !empty($durByDoctor)): ?>
                <h6 class="font-weight-bold text-muted mt-4 mb-2">
                    <i class="fas fa-user-md mr-1"></i>Duration by Doctor
                    <span class="badge badge-secondary ml-1" style="font-size:.72rem;">Admin view</span>
                </h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Doctor</th>
                                <th class="text-center">Cases Closed</th>
                                <th class="text-center">Avg Duration</th>
                                <th class="text-center">Median Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($durByDoctor as $row): ?>
                            <?php $docMedianFmt = isset($doctorMedians[$row['user_id']]) && $doctorMedians[$row['user_id']] !== null
                                ? self::formatDuration($doctorMedians[$row['user_id']]) : '&mdash;'; ?>
                            <tr>
                                <td><?php echo htmlspecialchars(trim($row['first_name'] . ' ' . $row['last_name'])); ?></td>
                                <td class="text-center"><?php echo (int)$row['cnt']; ?></td>
                                <td class="text-center font-weight-bold"><?php echo self::formatDuration((float)$row['avg_min']); ?></td>
                                <td class="text-center text-muted"><?php echo $docMedianFmt; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <?php endif; // end $totalClosed > 0 ?>
            </div>
        </div>

        <!-- ── Closure Type Breakdown ─────────────────────────────────────── -->
        <div class="card card-outline card-success mb-3">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>Closure Type Breakdown</h3>
            </div>
            <div class="card-body">
                <?php if (empty($closureRows)): ?>
                <div class="text-center py-3 text-muted small">No closed cases in this period.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Closure Type</th>
                                <th class="text-center">Cases</th>
                                <th class="text-center">Share</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($closureRows as $row): ?>
                            <?php
                                $type   = $row['closure_type'];
                                $cnt    = (int)$row['cnt'];
                                $pct    = $totalClosureCount > 0 ? round(($cnt / $totalClosureCount) * 100) : 0;
                                $label  = htmlspecialchars(isset($closureLabels[$type]) ? $closureLabels[$type] : $type);
                                $badge  = isset($closureBadge[$type]) ? $closureBadge[$type] : 'badge-secondary';
                                $barCol = isset($closureBar[$type])   ? $closureBar[$type]   : 'bg-secondary';
                            ?>
                            <tr>
                                <td><span class="badge <?php echo $badge; ?>"><?php echo $label; ?></span></td>
                                <td class="text-center font-weight-bold"><?php echo $cnt; ?></td>
                                <td class="text-center text-muted"><?php echo $pct; ?>%</td>
                                <td style="width:35%">
                                    <div class="progress" style="height:10px;">
                                        <div class="progress-bar <?php echo $barCol; ?>" role="progressbar"
                                             style="width:<?php echo $pct; ?>%"
                                             aria-valuenow="<?php echo $pct; ?>"
                                             aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Referrals Issued ───────────────────────────────────────────── -->
        <div class="card card-outline card-warning mb-0">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-share-square mr-2"></i>Referrals Issued
                    <span class="badge badge-warning ml-2"><?php echo $totalReferrals; ?></span>
                </h3>
            </div>
            <div class="card-body">
                <?php if (empty($referralRows)): ?>
                <div class="text-center py-3 text-muted small">No referrals issued in this period.</div>
                <?php else: ?>
                <?php if ($totalReferrals > 25): ?>
                <p class="text-muted small mb-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    Showing most recent 25 of <?php echo $totalReferrals; ?> referrals.
                </p>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Date</th>
                                <th>Code</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Referred To</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($referralRows as $row): ?>
                            <tr>
                                <td class="text-muted small text-nowrap"><?php echo date('M j, Y', strtotime($row['closed_at'])); ?></td>
                                <td><span class="badge badge-light text-dark border"><?php echo htmlspecialchars($row['patient_code'] ?? ''); ?></span></td>
                                <td><?php echo htmlspecialchars(trim(($row['pat_first'] ?? '') . ' ' . ($row['pat_last'] ?? ''))); ?></td>
                                <td>
                                    <?php
                                    $docName = trim(($row['doc_first'] ?? '') . ' ' . ($row['doc_last'] ?? ''));
                                    echo $docName ? htmlspecialchars($docName) : '<span class="text-muted">&mdash;</span>';
                                    ?>
                                </td>
                                <td><?php echo $row['referral_to'] ? htmlspecialchars($row['referral_to']) : '<span class="text-muted">Not recorded</span>'; ?></td>
                                <td class="small text-muted"><?php echo $row['referral_reason'] ? htmlspecialchars($row['referral_reason']) : '&mdash;'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php
        return ob_get_clean();
    }
}
