# V23 — Staff: Responding to Patient Portal Messages

**Phase:** 6 — Patient Portal  
**Audience:** All staff with `patient_data` read access (DOCTOR, TRIAGE_NURSE, NURSE, PARAMEDIC, ADMIN, SUPER_ADMIN)  
**Estimated duration:** ~2 minutes  
**Watch first:** V01, V08 (Internal Messaging — for contrast)  
**Prerequisites for viewer:** Understands that patients can message the clinic through the portal (V21)

---

## Production Setup

- **Login as:** Any staff account with patient_data access (TRIAGE_NURSE or DOCTOR recommended)
- **Start screen:** `dashboard.php` — sidebar should show the Patient Messages badge if unread threads exist
- **Test data needed:** At least one portal message thread with an unread patient message (from seed data)
- **Note:** This is the staff-side counterpart to V21 (patient messaging). Make clear at the start that this is DIFFERENT from internal staff messaging (V08). The two inboxes are completely separate.

---

## Script

### [0:00–0:15] Opening
> [On screen: dashboard.php — sidebar showing "Patient Messages" with a red badge count]

"When a patient sends a message through their portal, staff see it here — there's a Patient Messages link in the sidebar with a badge showing the number of unread threads. This is separate from your internal staff inbox."

---

### [0:15–0:35] Navigating to patient messages
> [Action: Click "Patient Messages" in the sidebar]

> [On screen: portal_messages.php — list of patient message threads]

"The Patient Messages page lists all active threads — one row per patient conversation. Unread threads are highlighted. You can see the patient's name, the subject of the conversation, and when the last message was sent."

---

### [0:35–1:05] Reading a patient message
> [Action: Click on an unread thread]

> [On screen: thread detail — patient message(s) and reply history]

"Clicking a thread opens the full conversation. You'll see the patient's message at the top. The thread is chronological — if there have been previous exchanges, they're all visible here for context."

> [Action: Read the patient's message aloud or paraphrase it]

"This patient is asking about taking their prescription alongside an existing supplement. I'll compose a reply."

---

### [1:05–1:35] Replying to the patient
> [Action: Click in the reply text area at the bottom of the thread]

"Type your reply in the field at the bottom. Be clear and use plain language — the patient may not be familiar with clinical terminology."

> [Action: Type a reply: "Hello — thank you for your question. Paracetamol is generally safe to take alongside iron supplements, but we recommend taking them at least 2 hours apart to allow for proper absorption. Please call the clinic if you have any other concerns."]

> [Action: Click Send Reply]

> [On screen: reply appears in the thread — patient's message and the staff reply in sequence]

"The reply is sent. The patient will see it the next time they check their portal inbox. The thread is now marked as read for you."

---

### [1:35–1:50] The unread badge updates
> [Action: Navigate back to the sidebar — show that the badge count has decreased]

"The badge count on the sidebar updates automatically. When all threads are read, the badge disappears until a new patient message arrives."

---

### [1:50–2:00] Closing
"Patient portal messages are a quick way to handle clinical questions between visits. Keep responses professional, use plain language, and for anything complex or urgent, advise the patient to call or come in."
