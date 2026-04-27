# V11 — Admin: Employee Registration

**Phase:** 4 — Admin Workflows  
**Audience:** SUPER_ADMIN, ADMIN  
**Estimated duration:** ~2 minutes  
**Watch first:** V01  
**Prerequisites for viewer:** Basic navigation familiarity

---

## Production Setup

- **Login as:** SUPER_ADMIN or ADMIN account
- **Start screen:** `admin.php` (Admin Dashboard)
- **Test data needed:** None required — you'll create a new account live
- **Suggested demo:** Create a fictional staff member: "Dr. Ananya Reddy", role: DOCTOR
- **Note:** After creating the account, the new user will appear in User Management (V12). Consider creating this user before recording V12 so it shows up there naturally.

---

## Script

### [0:00–0:15] Opening
> [On screen: admin.php — Admin Dashboard]

"Creating new staff accounts is an administrator-only action in CareSystem. In this video I'll walk through registering a new employee."

---

### [0:15–0:35] Navigating to registration
> [Action: Click the "New User" button on the Admin Dashboard (emp_register.php)]

"From the Admin Dashboard, click New User. This opens the employee registration form. Only Super Admins and Admins can access this page — it's not self-service."

---

### [0:35–1:15] Filling the registration form
> [On screen: emp_register.php — registration form]

"Fill in the new employee's details: first name, last name, email address, and a username they'll use to log in."

> [Action: Fill in First Name: "Ananya", Last Name: "Reddy", Email: "ananya.reddy@demo.com", Username: "dr.reddy"]

"Next, select their role. The role determines what pages and data they can access. Choose carefully — you can always change this later from User Management."

> [Action: Select role: DOCTOR from the dropdown]

"Set a temporary password. The employee should change this on their first login."

> [Action: Enter a password (characters obscured)]

"The account is active by default. You can deactivate it at any time from User Management."

> [Action: Click Register / Create Account]

---

### [1:15–1:40] Confirmation
> [On screen: success message — "Account created successfully" or redirect to user list]

"The account is created. The new employee can now log in with the username and temporary password you set. Make sure to share these credentials securely — do not send passwords over unencrypted channels."

---

### [1:40–2:00] Closing
"Once the account is active, the employee will see the dashboard and navigation appropriate for their role. For managing existing accounts — changing roles, resetting passwords, or deactivating users — watch the next video on User Management."
