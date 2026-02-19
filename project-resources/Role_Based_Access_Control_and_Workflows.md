
# Operations & Access Control Specification

## 1. End-to-End Workflow Definitions

### 1.1 Case Sheet Workflow

The Case Sheet is a central object in the system that involves multiple roles from inception to closure. The workflow must support both forward progression and backward transitions.
(TODO: India Team to visit the field and get clarifiations on the workflow!  Different modes of communication - medical camp? phone? in-person consultation?  What are all the different paths/ways a Case Sheet can be closed? Who is closing the case sheet? Under what conditions (exit criteria)?  

#### Actors Involved
- Paramedic
- Triage Nurse
- Nurse
- Doctor
- Referral Hospital / Specialist
- Administrator

#### Workflow States

1. **Created**
   - Case sheet is created by Paramedic or Nurse during initial patient encounter.
   - Basic patient details, symptoms, vitals, and preliminary observations are recorded.

2. **Triage**
   - Triage Nurse reviews the case.
   - Priority level assigned.
   - May add additional vitals and notes.
   - Can send back to *Created* state for corrections or missing information.

3. **Clinical Review**
   - Nurse performs detailed assessment.
   - Updates treatment notes, medications, and care plan.
   - Can move forward to Doctor Review or send back to Triage.

4. **Doctor Review**
   - Doctor evaluates the case.
   - Adds diagnosis, prescriptions, and recommendations.
   - May:
     - Approve treatment and close the case.
     - Refer to hospital/specialist.
     - Send back to Nurse or Triage for more details.

5. **Referral (Optional)**
   - Case is referred to hospital or specialist.
   - Referral details, documents, and follow-ups are attached.
   - Status updated based on hospital feedback.

6. **Closure**
   - Case is closed after successful treatment or referral completion.
   - Final diagnosis and outcomes recorded.
   - Case becomes read-only.

#### State Transition Diagram (Logical)

Created → Triage → Clinical Review → Doctor Review → Closure  
             ↘                     ↖  
               ←———— Feedback / Rework ————→ Referral

---

### 1.2 Other Key Workflows

#### Patient Data Workflow
- Create → Validate → Update → Archive
- Role-driven access and validation checkpoints.

#### Assets (Educational Videos)
- Upload → Review → Approve → Publish → Archive

#### Events (Health Camps)
- Draft → Review → Approve → Publish → Execute → Report → Archive

#### Feedback & Messages
- Submit → Review → Respond → Close

---

## Role-Based Access Mapping (RBAC)

### Roles

- SUPER_ADMIN  
- ADMIN  
- DOCTOR  
- TRIAGE_NURSE  
- NURSE  
- PARAMEDIC  
- GRIEVANCE_OFFICER  
- EDUCATION_TEAM  
- DATA_ENTRY_OPERATOR  

### Managed Objects

- Assets (Educational Videos)
- Case Sheets
- Events
- Patient Data
- Users
- Patient Feedback
- Messages
- Tasks (TODO lists)

### Access Levels

- N = No Access  
- R = Read Only  
- RW = Read and Write  

### Access Matrix

| Role \ Object | Assets | Case Sheets | Events | Patient Data | Users | Feedback | Messages |
|----------------|---------|--------------|--------|--------------|--------|-----------|------------|
| SUPER_ADMIN | RW | RW | RW | RW | RW | RW | RW |
| ADMIN | RW | RW | RW | RW | RW | RW | RW |
| DOCTOR | R | RW | R | RW | N | R | RW |
| TRIAGE_NURSE | R | RW | R | RW | N | R | RW |
| NURSE | R | RW | R | RW | N | R | RW |
| PARAMEDIC | R | RW | R | RW | N | R | RW |
| GRIEVANCE_OFFICER | N | R | N | R | N | RW | RW |
| EDUCATION_TEAM | RW | N | RW | N | N | R | RW |
| DATA_ENTRY_OPERATOR | R | RW | R | RW | N | N | RW |

---

### Access Matrix

| Role \ Object | Assets | Case Sheets | Events | Patient Data | Users | Feedback | Messages | Tasks (To Do List) |
|----------------|---------|--------------|--------|--------------|--------|-----------|------------|-------------------|
| SUPER_ADMIN | RW | RW | RW | RW | RW | RW | RW | RW |
| ADMIN | RW | RW | RW | RW | RW | RW | RW | RW |
| DOCTOR | R | RW | R | RW | N | R | RW | RW |
| TRIAGE_NURSE | R | RW | R | RW | N | R | RW | RW |
| NURSE | R | RW | R | RW | N | R | RW | RW |
| PARAMEDIC | R | RW | R | RW | N | R | RW | RW |
| GRIEVANCE_OFFICER | N | R | N | R | N | RW | RW | RW |
| EDUCATION_TEAM | RW | N | RW | N | N | R | RW | RW |
| DATA_ENTRY_OPERATOR | R | RW | R | RW | N | N | RW | RW |


---

## 3. Summary

This document defines:
- End-to-end operational workflows.
- Detailed role-based access mapping.

This will serve as a baseline for system design, development, and QA validation.

##4. Later TBD

- Medical Disclaimers, NDAs etc.
- Any other legal procedures/documents
