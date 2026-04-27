# V18 — Role Orientation: Paramedic, Education Team & Data Entry

**Phase:** 5 — Personal & Role Orientation  
**Audience:** PARAMEDIC, EDUCATION_TEAM, DATA_ENTRY_OPERATOR  
**Estimated duration:** ~3 minutes  
**Watch first:** V01  
**Prerequisites for viewer:** Has watched V01

---

## Production Setup

- **Login as:** Rotate through three accounts — one PARAMEDIC, one EDUCATION_TEAM, one DATA_ENTRY_OPERATOR
- **Start screen:** `login.php` for each rotation, or `dashboard.php` if switching is slow
- **Test data needed:** Active accounts for all three roles
- **Note:** The key story here is what each role can and CANNOT see — the sidebar and dashboard are the evidence. Do not show error pages — just show the navigation that's available (and not available) to each role. This video reassures limited-access users that the system is working correctly for them.

---

## Script

### [0:00–0:15] Opening
> [On screen: login.php or dashboard.php]

"CareSystem's access is role-based — what you can see and do depends on the role your administrator assigned. This video is for Paramedics, Education Team members, and Data Entry Operators — roles with more focused access than clinical or admin staff."

---

### [0:15–1:00] PARAMEDIC role

> [Action: Log in as PARAMEDIC account (or show dashboard if already logged in)]

> [On screen: dashboard for PARAMEDIC]

"Logged in as a Paramedic. The dashboard shows the patient queue and current case sheet activity — Paramedics can see patient data and track what's happening in the clinic."

> [Action: Click through the sidebar — show what's accessible]

"From the sidebar, Paramedics can access: Patients, the appointment calendar, Labwork in read-only mode, messages, and tasks. Lab results are viewable but cannot be marked complete — that requires write access."

> [Action: Navigate to Labwork — point out that there's no Complete button, or the button is not present]

"Notice there's no ability to complete a lab order — Paramedics can view the queue but the action buttons are not available for this role."

---

### [1:00–1:55] EDUCATION_TEAM role

> [Action: Log out and log in as EDUCATION_TEAM account]

> [On screen: dashboard for EDUCATION_TEAM]

"Education Team members have a focused set of tools. The dashboard reflects this — fewer tiles, no clinical queue."

> [Action: Walk through available sidebar items]

"Available pages: Assets — where Education Team members can access educational materials and send them to patients. Also Messages, Tasks, and the Calendar. Education Team cannot access patient clinical data, case sheets, or feedback."

> [Action: Navigate to Assets — show browsing and the send-to-patient capability]

"The asset library is the primary workspace for Education Team — uploading content, organising it by type and category, and delivering it to patients."

---

### [1:55–2:45] DATA_ENTRY_OPERATOR role

> [Action: Log out and log in as DATA_ENTRY_OPERATOR account]

> [On screen: dashboard for DATA_ENTRY_OPERATOR]

"Data Entry Operators have the most focused access. The role is designed for administrative support staff who enter and manage patient information but do not participate in clinical workflows."

> [Action: Walk through the sidebar]

"Available: Patients list — to search and view patient records. Messages and Tasks for internal coordination. Calendar for scheduling awareness. Data Entry Operators cannot access case sheets, clinical notes, feedback, analytics, or lab results."

---

### [2:45–3:00] Closing
"If you log in and don't see a page you expected to access, check with your administrator — it may be a role permission that needs to be adjusted. The system is working correctly when your navigation only shows what your role is authorised for."
