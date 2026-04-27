# V06 — Ordering & Completing Lab Tests

**Phase:** 2 — Core Clinical  
**Audience:** TRIAGE_NURSE, NURSE (ordering); NURSE, TRIAGE_NURSE, DOCTOR (completing)  
**Estimated duration:** ~3 minutes  
**Watch first:** V01, V02  
**Prerequisites for viewer:** Understands the intake form basics

---

## Production Setup

- **Login as:** Start as TRIAGE_NURSE (to show ordering). The completing step can be done with the same or a different account that has labwork access.
- **Start screen:** `intake.php` with an active case sheet open (INTAKE_IN_PROGRESS)
- **Test data needed:** An open intake case sheet. The Labwork page needs at least one pending lab order to demonstrate completing.
- **Note:** Show the lab ordering modal — it's visually impressive (50+ categorized tests). Take a moment on it.

---

## Script

### [0:00–0:15] Opening
> [On screen: intake.php — active case sheet, currently on any tab]

"CareSystem includes an integrated lab ordering and tracking system. In this video I'll show how to order a lab test during intake, and then how lab staff complete the order once results are ready."

---

### [0:15–0:40] Navigating to the Labs tab
> [Action: Click to Tab 6 — Labs]

"Inside any active intake, the Labs tab shows all lab tests ordered for this visit. To order a new test, click the Order Lab Test button."

> [Action: Click "Order Lab Test" button — modal opens]

---

### [0:40–1:20] The order modal
> [On screen: Lab order modal with categorized test list]

"The ordering modal gives you access to over 50 test types organized by category — haematology, biochemistry, hormones, microbiology, and more. Scroll through or use the search to find the test you need."

> [Action: Scroll through the categories briefly to show breadth]

"I'll select a Complete Blood Count from the Haematology section."

> [Action: Click CBC / Complete Blood Count to select it. It should highlight or check.]

"If there's anything the lab needs to know — sample timing, urgency, patient notes — enter that in the notes field below."

> [Action: Type a brief note: "Fasting sample — collected at 8 AM"]

> [Action: Click the submit/order button]

"The test is ordered and now appears in the Labs tab for this case sheet."

> [On screen: Labs tab updated showing the pending order]

---

### [1:20–1:45] Viewing lab orders in the intake
> [On screen: Labs tab with the new order listed]

"You can see the test name, who ordered it, when it was ordered, and current status — Pending. Any notes you entered are saved here too. If multiple tests are ordered, they all appear in this table."

---

### [1:45–2:05] Switching to the Labwork page
> [Action: Navigate to the sidebar → Labwork, or go to `lab_results.php`]

"Now let's switch perspective to the lab side. From the sidebar, click Labwork to open the lab queue."

> [On screen: lab_results.php — pending lab orders queue]

---

### [2:05–2:35] Completing a lab order
> [On screen: Labwork queue showing pending orders — FIFO order]

"The Labwork page shows all pending lab orders across all patients, in the order they were submitted. This is the queue the lab team works through."

> [Action: Locate the order for the patient from the demo. Click the Complete button.]

"I'll click Complete on our test. A modal opens for entering the results."

> [On screen: complete order modal]

"Enter the result values and any relevant observations in the notes field."

> [Action: Type sample results: "WBC: 7.2 ×10³/µL (Normal). RBC: 4.8 ×10⁶/µL. Hgb: 13.5 g/dL. No abnormalities detected."]

> [Action: Click Complete / Submit]

---

### [2:35–2:50] Order marked complete
> [On screen: order disappears from queue or status changes to Completed]

"The order is marked Completed and moves out of the pending queue. Back on the patient's case sheet, the lab tab will now show the order as Completed with the result notes attached."

---

### [2:50–3:00] Closing
"Lab orders are tied to the specific case sheet they were created on, so the doctor reviewing that visit will see the results alongside the intake notes during their review."
