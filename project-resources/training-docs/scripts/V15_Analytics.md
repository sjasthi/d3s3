# V16 — Analytics Dashboard

**Phase:** 4 — Admin Workflows  
**Audience:** SUPER_ADMIN, ADMIN (full access); DOCTOR (partial — Caseload and Outcomes tabs may be restricted)  
**Estimated duration:** ~3 minutes  
**Watch first:** V01  
**Prerequisites for viewer:** Familiarity with clinical terminology

---

## Production Setup

- **Login as:** SUPER_ADMIN or ADMIN for full tab access
- **Start screen:** `analytics.php`
- **Test data needed:** The system needs meaningful data for charts to be populated — at least 10–15 case sheets, a mix of open/closed statuses, a few feedback entries. The BlueHost deployment with real test data will look better than a fresh local install.
- **Note:** Analytics loads tabs via AJAX — each tab click triggers a data fetch. There may be a brief loading state. This is normal — don't edit it out.

---

## Script

### [0:00–0:15] Opening
> [On screen: analytics.php — Overview tab loading]

"The Analytics Dashboard gives administrators and senior clinical staff a real-time picture of how the clinic is operating. It's organized into five tabs — let's go through each one."

---

### [0:15–0:45] Overview tab
> [On screen: Overview tab — summary cards/charts]

"The Overview tab shows headline metrics — total patients, case sheets this period, intake completion rates, and average time from intake to doctor review. This is the at-a-glance health check for the system."

> [Action: Point to the key metric cards — don't dwell, just orient]

---

### [0:45–1:10] Caseload tab
> [Action: Click the Caseload tab — wait for AJAX load]

> [On screen: Caseload tab — per-doctor or per-period breakdown]

"Caseload shows how work is distributed — how many case sheets each doctor has handled, intake volumes by time period, and role-by-role breakdowns. This helps with staffing decisions and workload balance."

> [Action: Point to the doctor-level breakdown if visible]

---

### [1:10–1:35] Outcomes tab
> [Action: Click the Outcomes tab]

> [On screen: Outcomes tab — referral rates, risk levels, closure rates]

"Outcomes tracks clinical results — referral rates, risk level distributions, how cases were disposed of. This gives management a picture of clinical patterns over time."

---

### [1:35–1:55] Satisfaction tab
> [Action: Click the Satisfaction tab]

> [On screen: Satisfaction tab — feedback/grievance trends]

"Satisfaction pulls from the feedback and grievances module. You can see submission volumes, resolution rates, and category breakdowns. Useful for quality improvement tracking."

---

### [1:55–2:20] Patient Trends tab
> [Action: Click the Patient Trends tab]

> [On screen: Patient Trends — new vs returning, demographics, visit frequency]

"Patient Trends shows registration patterns, new versus returning patient ratios, and demographic summaries. This is useful for planning capacity and understanding the patient population."

---

### [2:20–2:45] Date range and print
> [Action: If date range filters are visible — show adjusting the period]

"Most tabs support date range filtering — you can narrow the view to the last 30 days, a specific month, or a custom range."

> [Action: Click the Print Report button if visible]

"The Print Report option generates a printer-friendly summary of the current view — useful for reporting to leadership or board meetings."

---

### [2:45–3:00] Closing
"Analytics is read-only — no data is changed here. It's purely for visibility and decision-making. For raw data export, use the Reports page covered in Video 15."
