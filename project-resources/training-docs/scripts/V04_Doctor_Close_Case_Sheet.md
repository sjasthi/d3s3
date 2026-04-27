# V05 — Doctor: Reviewing & Closing a Case Sheet

**Phase:** 2 — Core Clinical  
**Audience:** DOCTOR  
**Estimated duration:** ~3 minutes  
**Watch first:** V01, V04  
**Prerequisites for viewer:** Has completed V04; understands the review form

---

## Production Setup

- **Login as:** DOCTOR account
- **Start screen:** `review.php` with a case sheet already in `DOCTOR_REVIEW` status (claimed by this doctor)
- **Test data needed:** A case sheet in DOCTOR_REVIEW state with the doctor's assessment, diagnosis, and plan already partially filled (or fill them live during recording)
- **Note:** The "Close Case Sheet" action is irreversible in normal flow — the record becomes CLOSED. Have a fresh case sheet ready so you can actually close one on camera.

---

## Script

### [0:00–0:15] Opening
> [On screen: review.php — doctor review form with a case sheet in DOCTOR_REVIEW]

"In this video we'll complete the clinical review and formally close the case sheet. Closing finalizes the record for this visit and makes it read-only."

---

### [0:15–0:55] Completing the clinical fields
> [On screen: doctor review form — Assessment, Diagnosis, Plan sections]

"Before closing, make sure all your clinical findings are entered. The assessment field is your overall clinical impression. Diagnosis is the confirmed or working diagnosis. The plan field covers treatment decisions — medications, follow-up imaging, referrals."

> [Action: Fill in or review Assessment field — type a brief example: "Tension-type headache, likely stress-related"]

> [Action: Fill Diagnosis — "G44.2 Tension-type headache"]

> [Action: Fill Plan — "Analgesics as needed, hydration, follow up in 2 weeks if no improvement"]

---

### [0:55–1:20] Prescriptions and follow-up
> [Action: Scroll to Prescriptions field]

"If prescribing medication, enter it in the prescriptions field. This is a free-text field — include drug name, dose, frequency, and duration."

> [Action: Type a sample prescription: "Paracetamol 500mg — 1 tablet every 6 hours as needed, max 4 per day, 5-day supply"]

> [Action: Scroll to Follow-Up Notes]

"Follow-up notes are instructions for the patient and for the clinic — when to return, what to watch for, any specialist referrals."

> [Action: Type: "Return in 2 weeks. Urgent review if symptoms worsen or vision changes."]

---

### [1:20–1:45] Risk level and referral
> [Action: Scroll to the Summary / Disposition section]

"Before closing, confirm the risk level assessment and referral decision in the Summary section. This gives the clinic a quick snapshot of the outcome."

> [Action: Select risk level (Low / Medium / High) and referral option]

---

### [1:45–2:15] Closing the case sheet
> [Action: Scroll to the Close Case Sheet button or click the action at the top]

"When everything is complete, click Close Case Sheet. You'll be asked to confirm — once closed, the record moves to 'Closed' status."

> [Action: Click Close — confirm if prompted]

> [On screen: success message, redirect or status update showing CLOSED]

"The case sheet is now closed. It becomes read-only — no further edits can be made by either the doctor or the nurse. The full record is still accessible from the patient's profile for future reference."

---

### [2:15–2:40] What the nurse sees after close
> [Action: Either show the case sheet in the patient profile, or describe it]

"After a case sheet is closed, the triage nurse can still view it from the patient's Medical History tab — they'll see the full read-only summary including your clinical notes, vitals, and disposition. It's part of the permanent patient record."

---

### [2:40–3:00] Closing
"Closing a case sheet is the final step in the clinical workflow for a single visit. The patient's full history — including all closed case sheets — is always available on their profile for context on future visits."
