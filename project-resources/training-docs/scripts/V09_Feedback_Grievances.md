# V10 — Grievance Officer: Feedback Management

**Phase:** 3 — Communication & Support  
**Audience:** GRIEVANCE_OFFICER, ADMIN, SUPER_ADMIN  
**Estimated duration:** ~3 minutes  
**Watch first:** V01  
**Prerequisites for viewer:** Basic navigation familiarity

---

## Production Setup

- **Login as:** GRIEVANCE_OFFICER account
- **Start screen:** `feedback.php`
- **Test data needed:** At least 2–3 feedback submissions in various states (open, in progress, resolved). Seed data from `sql/test_data_phase3.sql` should cover this.
- **Note:** Briefly mention that clinical staff can VIEW but not manage feedback — the manage/close capability is restricted to GRIEVANCE_OFFICER, ADMIN, and SUPER_ADMIN.

---

## Script

### [0:00–0:15] Opening
> [On screen: feedback.php — feedback list]

"The Feedback module is where patient grievances, complaints, and suggestions are tracked and managed. In this video I'll walk through the Grievance Officer's workflow."

---

### [0:15–0:45] The feedback list
> [On screen: feedback list with status badges]

"The feedback page shows all submissions with their current status — Open, In Progress, or Resolved. You can see the date submitted, the category, and a brief preview of the issue."

> [Action: Point to or filter by status. Show that there are submissions in different states.]

"Use the filter or search to narrow down by status or date range when you're working through a backlog."

---

### [0:45–1:15] Opening a feedback record
> [Action: Click on one of the feedback items to open the detail view]

> [On screen: feedback_detail.php — full feedback record]

"Clicking any item opens the full detail. You'll see the submission date, category, the full description of the issue, and the history of any status changes made so far."

---

### [1:15–1:45] Updating status
> [Action: Find the status update control — dropdown or buttons for Open / In Progress / Resolved]

"To update the status, use the status control on the detail page. When you start investigating an issue, move it to In Progress so other officers know it's being handled."

> [Action: Change status from Open to In Progress]

"You can add a note explaining what action was taken — this becomes part of the record."

> [Action: Add a note: "Contacted patient by phone. Reviewing intake record for the date in question."]

> [Action: Save the update]

---

### [1:45–2:15] Resolving / closing a record
> [Action: Change status to Resolved]

"When the issue is resolved, mark it as Resolved. Add a closing note describing the outcome."

> [Action: Add closing note: "Reviewed with supervising doctor. Patient informed of outcome. No further action required."]

> [Action: Save]

> [On screen: record now shows Resolved status]

"Resolved records remain in the system — they're never deleted — so you always have a full audit trail of how each grievance was handled."

---

### [2:15–2:40] What other roles see
> [Note to presenter: you can briefly describe this rather than switching accounts]

"Clinical staff — nurses, doctors, paramedics — can see the feedback list and read individual records, but they cannot change the status or add resolution notes. That capability is limited to Grievance Officers and Administrators."

"Patients can submit feedback through the patient portal, which feeds directly into this list. That's covered in the patient portal video."

---

### [2:40–3:00] Closing
"The feedback module ensures that every patient concern is documented, tracked, and resolved through a consistent process — with a complete history of actions taken."
