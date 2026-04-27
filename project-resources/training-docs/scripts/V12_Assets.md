# V13 — Admin: Asset Library & Patient Delivery

**Phase:** 4 — Admin Workflows  
**Audience:** SUPER_ADMIN, ADMIN  
**Estimated duration:** ~3 minutes  
**Watch first:** V01  
**Prerequisites for viewer:** Basic navigation familiarity

---

## Production Setup

- **Login as:** ADMIN or SUPER_ADMIN
- **Start screen:** `assets.php`
- **Test data needed:** A few existing assets in the library (ideally one of each type — document, video, form). Have a small PDF or image file ready to upload live. Have an existing patient to send an asset to.
- **Note:** The "Send to Patient" flow uses an AJAX patient search. Make sure the search works locally.

---

## Script

### [0:00–0:15] Opening
> [On screen: assets.php — asset library list]

"The Asset Library is where you manage educational materials, forms, and other resources that can be shared with patients. In this video I'll show how to add an asset and deliver it to a patient."

---

### [0:15–0:45] Asset library overview
> [On screen: asset list with type/category columns and filter bar]

"The library shows all uploaded assets — their type, category, whether they're public, and how many patients they've been sent to. You can filter by type, category, or search by name."

> [Action: Use the search or type filter briefly to show the filtering capability]

---

### [0:45–1:20] Uploading a new asset
> [Action: Click "Upload Asset" or "New Asset" button]

> [On screen: upload form]

"Click Upload Asset. Give it a name, select the type — Document, Video, Audio, or Form — and a category that makes sense for your library organisation."

> [Action: Fill in Name: "Blood Pressure Self-Monitoring Guide", Type: DOCUMENT, Category: "Patient Education"]

"Toggle Public on if this asset should appear in the patient portal's public resource library — visible to all portal users without being specifically sent. Leave it off if you want to only send it to specific patients."

> [Action: Click the file picker and select a demo PDF]

> [Action: Click Upload / Save]

> [On screen: asset appears in the list]

"The file is stored securely on the server. Patients cannot access it by a direct URL — they can only view it if it's sent to them or if it's in the public library."

---

### [1:20–1:55] Sending an asset to a patient
> [Action: Find the asset in the list. Click "Send to Patient" button — modal opens]

"To deliver an asset directly to a patient, click Send to Patient. A modal opens with a patient search."

> [Action: Type the patient's name in the search field. Select the patient.]

"Select the patient. Optionally add a personalised note explaining why you're sending this."

> [Action: Type a note: "Based on your recent visit, we recommend reviewing this guide before your next appointment."]

> [Action: Click Send]

"The patient will see this asset in their portal under Resources, with an unread badge until they open it."

---

### [1:55–2:20] The send-count badge
> [On screen: back in the asset list — the send count badge on that asset has incremented]

"Back in the library, the badge on the asset now shows how many patients it's been sent to. This helps you track distribution without needing a separate report."

---

### [2:20–2:40] Removing a sent asset
> [Action: If applicable — show the option to remove a sent asset from a patient]

"If an asset was sent in error, you can remove it from a patient's portal. This removes their access to that specific delivery but doesn't delete the asset from the library."

---

### [2:40–3:00] Closing
"The asset library keeps patient education materials organised and deliverable in a few clicks. From the patient's side, they'll see these resources highlighted in the portal — we'll cover that in the patient portal videos."
