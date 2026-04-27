# V20 — Patient Portal: Health Record & Lab Results

**Phase:** 6 — Patient Portal  
**Audience:** Patients  
**Estimated duration:** ~3 minutes  
**Watch first:** V19  
**Prerequisites for viewer:** Has watched V19 (Getting Started), can log into the portal

---

## Production Setup

- **Login as:** Portal test patient (seed data — patient with at least 1–2 closed case sheets and at least one completed lab order)
- **Start screen:** Patient portal dashboard (`patient_portal.php?page=dashboard`)
- **Test data needed:** Closed case sheets with clinical notes, at least one COMPLETED lab order attached to a case sheet for this patient
- **Tone:** Reassuring and clear. Patients viewing their own clinical records may feel anxious — keep language plain and positive.

---

## Script

### [0:00–0:10] Opening
> [On screen: portal dashboard]

"In this video I'll show how to view your health record and lab results in the patient portal."

---

### [0:10–0:40] Health Record page
> [Action: Click "Health Record" in the top nav]

> [On screen: portal/health_record.php]

"The Health Record page shows a summary of your visits to the clinic. Each entry represents one appointment — you can see the date, the type of visit, and the key details from that visit."

> [Action: Point to a visit entry — show date, status, chief complaint]

"This is a read-only view — you're seeing the same information that's in your clinical record. You cannot edit it here."

---

### [0:40–1:15] Expanding a visit record
> [Action: Click to expand a visit entry (if expandable) or click to view detail]

"Clicking on a visit shows you more detail — the doctor's assessment, any diagnosis, the treatment plan, and follow-up instructions from that appointment."

> [On screen: expanded or modal view of a case sheet]

"If you have questions about anything you see here — a diagnosis you don't recognise or a medication listed — use the messaging feature to ask your care team directly. We'll cover that in the next video."

---

### [1:15–1:50] Lab Results page
> [Action: Click "Lab Results" in the top nav]

> [On screen: portal/lab_results.php]

"The Lab Results page shows all the laboratory tests ordered for you, along with their status and results."

> [Action: Point to a completed test entry — test name, ordered date, result notes]

"Completed tests show the result notes entered by the lab. Pending tests are still being processed — check back later or message the clinic if you have a question about a pending test."

---

### [1:50–2:20] Understanding your results
"Lab results can look unfamiliar — abbreviations, reference ranges, and medical terminology. A few things to remember:"

"First, the portal shows results as recorded. Your doctor will discuss abnormal findings with you directly — don't rely on the portal as the only source of medical advice."

"Second, if you see a result that worries you, the best next step is to message your care team through the Messages section, or call the clinic directly."

---

### [2:20–2:40] No results yet?
> [Action: Point to an empty state if applicable, or describe it]

"If you've never had labs ordered, or your results haven't been entered yet, the page will show an empty list. Results appear here once the lab team has recorded them in the system."

---

### [2:40–3:00] Closing
"Your health record and lab results give you visibility into your own care history. Next, I'll show how to send a message to your care team directly through the portal."
