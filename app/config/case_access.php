<?php
/**
 * Issue #37 - Case-specific doctor authorization.
 *
 * An explicit inactive assignment overrides legacy access.
 */

function doctorCanAccessCase(
    PDO $pdo,
    int $caseSheetId,
    int $doctorUserId
): bool {
    if ($caseSheetId <= 0 || $doctorUserId <= 0) {
        return false;
    }

    $stmt = $pdo->prepare(
        "SELECT
            cs.assigned_doctor_user_id,
            cda.status AS assignment_status
         FROM case_sheets cs
         LEFT JOIN case_doctor_assignments cda
           ON cda.case_sheet_id = cs.case_sheet_id
          AND cda.doctor_user_id = ?
         WHERE cs.case_sheet_id = ?
         LIMIT 1"
    );

    $stmt->execute([$doctorUserId, $caseSheetId]);
    $case = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$case) {
        return false;
    }

    if ($case['assignment_status'] === 'INACTIVE') {
        return false;
    }

    if ($case['assignment_status'] === 'ACTIVE') {
        return true;
    }

    return (int) $case['assigned_doctor_user_id']
        === $doctorUserId;
}
