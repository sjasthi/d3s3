# V14 — Admin: Patient Portal Account Management

**Phase:** 4 — Admin Workflows  
**Audience:** SUPER_ADMIN, ADMIN  
**Estimated duration:** ~2 minutes  
**Watch first:** V01  
**Prerequisites for viewer:** Understands what the patient portal is (recommend watching V19 first for context)

---

## Production Setup

- **Login as:** SUPER_ADMIN or ADMIN
- **Start screen:** `patients.php` — patient list
- **Test data needed:** At least one patient WITHOUT a portal account (to demonstrate creating one), and one patient WITH an active portal account (to demonstrate reset/toggle). The seeded test patients (IDs 1–3) have portal accounts — use a different patient for the creation demo.
- **Note:** The portal account management controls live on the patient's profile page, not in a separate admin panel.

---

## Script

### [0:00–0:15] Opening
> [On screen: patients.php — patient list]

"Patient portal accounts are created and managed by administrators directly from the patient's profile. In this video I'll show how to create an account, reset a password, and toggle access."

---

### [0:15–0:35] Finding the patient
> [Action: Search for a patient without a portal account. Click their name to open the profile.]

> [On screen: patient_profile.php — Personal Info tab]

"Open the patient's profile from the patients list. The portal account controls are on the Personal Info tab."

---

### [0:35–1:05] Creating a portal account
> [Action: Scroll to the Portal Account section — show "This patient does not have a portal account yet" message]

"If the patient doesn't have an account yet, you'll see this message. Click Create Portal Account."

> [Action: Click "Create Portal Account" — modal opens]

> [On screen: create account modal — email, username, password fields]

"Fill in the email address they'll use to log in, a username, and a temporary password. The patient should change this password after their first login."

> [Action: Fill in email, username, and password]

> [Action: Click Create]

> [On screen: modal closes, portal account section now shows the account as Active]

"The account is created. The patient can now log in at the portal login page using these credentials."

---

### [1:05–1:30] Resetting a password
> [Action: On a patient that already has a portal account, click "Reset Password" button]

"If a patient forgets their password, click Reset Password. Set a new temporary password and share it with the patient securely."

> [Action: Enter a new password in the modal. Click Reset.]

---

### [1:30–1:50] Toggling account active / inactive
> [Action: Click the toggle or button to deactivate the portal account]

"If a patient requests their account be suspended, or if there's a data concern, you can deactivate the portal account without deleting it. Click the toggle to switch between Active and Inactive."

> [On screen: status changes to Inactive]

"A deactivated patient account prevents login but preserves all their portal history — messages, feedback submissions, and resource access."

---

### [1:50–2:00] Closing
"Portal account management is straightforward and always tied to the patient's profile. For a walkthrough of what the patient sees after logging in, watch the Patient Portal videos in this series."
