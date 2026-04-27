# V07 — Scheduling Appointments

**Phase:** 2 — Core Clinical  
**Audience:** TRIAGE_NURSE, NURSE  
**Estimated duration:** ~3 minutes  
**Watch first:** V01  
**Prerequisites for viewer:** Basic navigation familiarity

---

## Production Setup

- **Login as:** TRIAGE_NURSE account
- **Start screen:** `appointments.php` or `dashboard.php`
- **Test data needed:** At least one existing patient. At least one DOCTOR account in the system to assign.
- **Note:** Show both the list view and the calendar view. The calendar is visually compelling — spend a moment on it.

---

## Script

### [0:00–0:15] Opening
> [On screen: appointments.php or dashboard]

"This video covers scheduling future appointments — booking a patient for an upcoming visit, assigning a doctor, and seeing appointments on the calendar."

---

### [0:15–0:35] Appointments page overview
> [On screen: appointments.php — appointment list]

"The Appointments page shows all upcoming and recent appointments. You can filter by date, patient, or doctor. To schedule something new, click New Appointment."

> [Action: Click "New Appointment" button]

---

### [0:35–1:05] Searching for a patient
> [On screen: New appointment form or modal]

"Start by searching for the patient. Type their name and select them from the results. Their date of birth and contact information will pre-fill automatically — you don't need to re-enter it."

> [Action: Type patient name in the search field. Select the patient from results. Show DOB pre-filling.]

"This pre-fill is useful for confirming you have the right patient before saving the appointment."

---

### [1:05–1:40] Filling appointment details
> [Action: Fill in the appointment date/time field — choose a date a few days out]

"Select the appointment date and time. Then choose the visit type — routine follow-up, specialist consultation, procedure, or others depending on what's configured in your system."

> [Action: Select visit type from dropdown]

> [Action: Select an assigned doctor from the doctor dropdown]

"Assign the doctor this patient will be seeing. If the doctor isn't yet confirmed, you can leave it unassigned and update it later."

> [Action: Add an optional note in the notes field: "Patient requested morning slot. Follow-up from headache visit."]

---

### [1:40–2:00] Saving the appointment
> [Action: Click Save / Schedule]

> [On screen: appointment appears in the list, success message]

"The appointment is saved and appears in the list. The patient will also be able to see this appointment in their patient portal — if they have a portal account."

---

### [2:00–2:30] Calendar view
> [Action: Click to Calendar view tab or navigate to `calendar.php`]

"Switch to the calendar view to see appointments laid out by day or week. This is useful for spotting scheduling conflicts or understanding the day's load at a glance."

> [On screen: calendar with the new appointment visible on the correct date]

"The appointment we just created shows up here. You can click any appointment on the calendar to see its details."

> [Action: Click the appointment on the calendar to show a detail popup or link]

---

### [2:30–2:50] Assigning a doctor from the dashboard queue
> [Action: Navigate back to dashboard. Show the "Assign Doctor" button on a completed intake in the queue.]

"A quick note — you can also assign a doctor to a completed intake directly from the dashboard queue. When a case sheet is marked 'Ready for Doctor', the queue row shows an Assign Doctor button. This links the intake to the doctor without needing to go to the appointments page."

---

### [2:50–3:00] Closing
"Scheduling keeps the clinic organized and gives patients visibility into their upcoming care through the portal. Next, let's look at the internal messaging system."
