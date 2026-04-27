# V12 — Admin: Managing Users & Roles

**Phase:** 4 — Admin Workflows  
**Audience:** SUPER_ADMIN, ADMIN  
**Estimated duration:** ~3 minutes  
**Watch first:** V01, V11  
**Prerequisites for viewer:** Has watched V11 (Employee Registration)

---

## Production Setup

- **Login as:** SUPER_ADMIN or ADMIN
- **Start screen:** `users.php` (User Management) or navigate there from Admin Dashboard
- **Test data needed:** Several existing staff accounts in various roles. Ideally the user created in V11 (Dr. Ananya Reddy) is visible here.
- **Note:** Show deactivate vs delete distinction. CareSystem deactivates accounts (preserves data/audit trail) rather than deleting them.

---

## Script

### [0:00–0:15] Opening
> [On screen: users.php — user list]

"User Management is where you oversee all staff accounts — edit roles, reset passwords, and control who has access to the system."

---

### [0:15–0:40] The user list
> [On screen: user list table — names, roles, status, last login]

"The user list shows every staff account — their name, role, whether the account is active, and when they last logged in. You can search by name or filter by role."

> [Action: Type a name in the search field to filter. Clear it. Filter by role: DOCTOR.]

"Filtering by role is useful when you're onboarding a new team or need to audit who has a particular access level."

---

### [0:40–1:10] Editing a user — changing role
> [Action: Click Edit on one of the user rows (e.g., Dr. Reddy from V11)]

> [On screen: user edit form]

"Clicking Edit opens the user's profile. You can update their name, email, and most importantly — their role."

> [Action: Point to the role dropdown]

"Role changes take effect immediately on the user's next page load. If a staff member has taken on new responsibilities — a nurse promoted to charge nurse, for example — update their role here."

> [Action: Change role to a different value as a demo, then change it back]

---

### [1:10–1:40] Resetting a password
> [Action: Find the password reset option in the edit form]

"If an employee is locked out or has forgotten their password, you can set a new temporary password from here. The employee should change it after their next login."

> [Action: Enter a new password in the reset field]

"Passwords are never shown in plain text — only you and the employee know the new temporary password."

---

### [1:40–2:10] Deactivating an account
> [Action: Find the Active / Inactive toggle or button]

"If an employee leaves or goes on extended leave, deactivate their account rather than deleting it. Deactivating immediately blocks login while preserving all their activity in the audit trail — case sheets they created, messages they sent, tasks they completed are all still intact."

> [Action: Toggle the account to Inactive — show the confirmation or updated status]

"A deactivated account shows as Inactive in the list. The user cannot log in. Reactivate it at any time by toggling it back."

---

### [2:10–2:40] Why we deactivate, not delete
"Deleting an account would break foreign key references — records created by that user would lose their creator attribution. Deactivating is always the right approach in an active clinical system."

---

### [2:40–3:00] Closing
"User Management gives administrators full control over who can access CareSystem and at what level. If you need to create a new account from scratch, refer back to Video 11 on Employee Registration."
