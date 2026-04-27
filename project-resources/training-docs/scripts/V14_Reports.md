# V15 — Admin: Reports & Data Export

**Phase:** 4 — Admin Workflows  
**Audience:** SUPER_ADMIN, ADMIN  
**Estimated duration:** ~2 minutes  
**Watch first:** V01  
**Prerequisites for viewer:** Basic navigation familiarity

---

## Production Setup

- **Login as:** SUPER_ADMIN or ADMIN
- **Start screen:** `reports.php`
- **Test data needed:** System should have data in multiple tables — patients, case sheets, etc. — so the export is non-trivial.
- **Note:** The export downloads a real CSV file. Have a folder open to show the downloaded file briefly. Do not export sensitive real patient data during recording — use demo/test data only.

---

## Script

### [0:00–0:15] Opening
> [On screen: reports.php — Reports page]

"The Reports page is an admin-only area for exporting and importing data. This is useful for backups, analysis, and data migration."

---

### [0:15–0:50] Exporting data
> [On screen: export form — table selection dropdown]

"To export, select the table you want to download from the dropdown. Available tables include Users, Patients, Case Sheets, Events, Messages, Patient Feedback, and Assets."

> [Action: Select "Patients" from the dropdown]

"Sensitive fields like password hashes are automatically excluded from exports — you'll only get the data that's appropriate to export."

> [Action: Click Export CSV]

> [On screen: browser downloads the file]

"The file downloads as a CSV — you can open it in Excel, Google Sheets, or any spreadsheet tool for analysis."

> [Action: Briefly show the downloaded CSV opened in a spreadsheet — just a glimpse at the columns]

---

### [0:50–1:20] Importing data
> [On screen: import section of the page]

"The import tool lets you bring data back in — useful for bulk updates or migrations. Select the target table, then upload a CSV that matches the column structure of an export."

> [Action: Point to the import section — table selector + file upload]

"The system validates the CSV before importing. If there are errors — wrong columns, invalid data — you'll see them listed and nothing will be imported until they're resolved."

---

### [1:20–1:45] When to use Reports vs Analytics
"A quick distinction: the Reports page is for raw data export and import. If you want visual charts, trends over time, or caseload breakdowns — use the Analytics page, which is covered in the next video."

---

### [1:45–2:00] Closing
"Reports gives administrators a straightforward way to get data out of the system for compliance, research, or operational planning. Access is restricted to Admins and Super Admins."
