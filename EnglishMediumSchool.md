# English Medium School (Play – A2) — Vertical Pack Requirement

## Status

**Planning document only. Nothing here is approved for implementation.**

This is a requirement/gap-analysis note for the future "School/College" vertical pack referenced in `EasyAccountsERP.md` Section 89 (Multi-Vertical Reuse Strategy) and Section 83 Phase 13+. Per Section 89, vertical packs are out of scope until the core Accounts Module (Phases 1–12) is complete, tested, and portability-audited. This document exists so the requirement is written down now; it does not authorize starting the build.

No code should be written from this document without a separate, explicit go-ahead.

---

## 1. Assumption Requiring Confirmation

"Play – A2" is read here as the **full British-curriculum age range** used by Bangladeshi English Medium schools, not just early years:

Play Group → Nursery → KG1 → KG2 → Class 1–5 (Primary) → Class 6–8 (Junior Secondary) → Class 9–10 (O-Level / IGCSE) → AS Level → **A2 Level**.

That means the school being modeled spans roughly ages 3–18, not just early years. **This must be confirmed before anything is designed in detail**, because it changes the fee structure, exam structure, and staff/subject complexity significantly (a Play-only nursery is a very different, much smaller system than a Play-to-A2 full school). If "A2" was actually meant as something else (e.g. up to Class 2), say so and this doc gets revised.

---

## 2. Governing Principle (from Section 89 — do not violate)

* Core tables (`customers`, `invoices`, `journal_entries`, `vendors`, etc.) are **never modified**.
* Student = a `Customer` record (guardian is the billing party). Fee Bill = an `Invoice`. Teacher/Staff salary = an `Expense`/future `Payroll` record.
* All school-specific data (student roll, class, section, guardian relation, attendance, marks) lives in **new, separate tables** that link to core tables by foreign key — never as new columns bolted onto `customers` or `invoices`.
* No vertical-specific logic inside core Services/Actions. The school module consumes core APIs/models only.
* Labels can be overridden for display ("Fee Bill" instead of "Invoice", "Student/Guardian" instead of "Customer") via config, per the Section 89 terminology table — the underlying entity and database table name stay the same.

---

## 3. Confirmed-Core Features (needed regardless of Play-only vs Play–A2)

These are the parts every version of a school (small or full) needs. Per standing scope discipline, only these should be built first; everything else waits for explicit confirmation.

### 3.1 Academic Structure
* Academic Session/Year (e.g. 2026–2027)
* Class/Grade list (Play, Nursery, KG1, KG2, Class 1–10, AS, A2 — configurable, not hardcoded, since curriculum stage names vary by school)
* Section per class (A, B, C…)
* Subject list per class (needed even minimally, for report cards)

### 3.2 Student & Guardian
* Student profile: name, DOB, gender, photo, admission date, class, section, roll number, blood group, address
* Guardian/parent profile (father/mother/guardian contact, occupation, NID) — this is the billing `Customer`
* One guardian can have multiple students (siblings) — the `Customer` (guardian) can be linked to multiple student records
* Admission number generation (reuse the existing configurable per-document-type numbering feature already built for invoices/estimates)

### 3.3 Fee Management (built on top of core Invoice/Sales, not a parallel system)
* Fee heads: Admission Fee, Monthly Tuition Fee, Session/Annual Fee, Exam Fee, Re-admission Fee (configurable list, not hardcoded)
* Fee structure per class (different classes usually have different tuition amounts)
* Monthly fee invoice generation per student (reuse the existing Recurring Invoicing feature already built in the core — one recurring invoice per student, billed to the guardian `Customer`)
* Fee collection / receipt (reuse existing Sales Receipt / Invoice Payment flow)
* Due/outstanding fee report per student, per class
* Discount/waiver on fee (sibling discount, staff-child discount, scholarship) — needs a discount mechanism on the invoice line, check what the core Invoice already supports before designing anything new

### 3.4 Attendance
* Daily student attendance (present/absent/late) per class/section
* Simple attendance summary report per student/class/month

### 3.5 Result / Report Card
* Exam/term definition (1st Term, Mid Term, Final, or Cambridge Checkpoint-style depending on stage)
* Marks entry per subject per student per exam
* Report card generation (PDF, reuse existing PDF export infrastructure already used for invoices)
* For Play/Nursery/KG specifically: usually no numeric exam — a simple "progress/development report" (skills checklist) is used instead of marks. Needs confirmation of whether this early-years variant is in scope now or later.

### 3.6 Staff
* Teacher/staff profile (name, designation, subject, class assigned, joining date, salary)
* **Gap:** the core does not currently have an Employee or Payroll module (confirmed absent — see Section 69/89 of `EasyAccountsERP.md`, listed only as a "potential future module", not built). Staff salary today can only be recorded as a generic core `Expense`. A minimal Employee list + monthly salary Expense is enough for Phase 1; a full Payroll module (attendance-linked salary, deductions, payslips) is a separate, larger, unconfirmed feature.

### 3.7 Reports
* Class-wise student strength
* Fee collection report (daily/monthly, by class)
* Due/outstanding fee report
* Income vs. Expense for the school (already exists in the core — no new work needed, just filtered view)
* Attendance report
* Result/progress report

### 3.8 Roles
* Admin (full access)
* Accountant (fee collection, reports — the existing Accountant role in the core already fits this)
* Front Desk/Admission Officer (admission, basic student info, no accounting access)
* Teacher (attendance + marks entry only, for own class/subject — new role, does not exist in the core today)

---

## 4. Explicitly Out of Scope Unless Confirmed (do not build speculatively)

Per standing rule: skip these unless the user confirms an actual need, even though they're common in school-management products:

* Transport/bus route management and transport fee
* Hostel/dormitory management
* Library management (book issue/return)
* Online parent portal (parents logging in to see fees/results themselves)
* SMS/notification gateway (fee due reminders, absence alerts)
* ID card generation/printing
* Online payment gateway for guardians (the core already has SSLCommerz for invoice payment links — reusable later if confirmed, not new work)
* Biometric/RFID attendance device integration
* Homework/assignment/syllabus tracking
* Cambridge/Edexcel exam-board specific result formats (O-Level/A-Level certificate mapping)
* Multi-branch/multi-campus (the core is explicitly non-multi-tenant; one deployment = one school)
* Full Payroll module (attendance-linked pay, tax deduction, payslip) — Phase 1 uses a plain Expense entry instead

---

## 5. Suggested Phasing (for discussion, not a commitment)

1. **Phase A** — Academic structure + Student/Guardian admission + Fee structure + Recurring fee invoicing + Fee collection + Due report. (Directly reuses existing Customer/Invoice/Recurring-Invoice/Payment core — least new code.)
2. **Phase B** — Attendance + basic Teacher/Staff list + salary as Expense.
3. **Phase C** — Exam/Result/Report Card (numeric, for Class 1 and above).
4. **Phase D** (only if confirmed) — Early-years progress report (Play/Nursery/KG), any items from Section 4.

---

## 6. Open Questions to Answer Before Any Build Starts

1. Confirm the actual grade range meant by "Play – A2" (see Section 1).
2. Roughly how many students/classes at launch? (affects whether performance/pagination needs special attention)
3. Is this for EasyIT's own use, or a client deployment? (affects whether demo/seed data, onboarding docs, etc. matter)
4. Monthly tuition fee: same for all students in a class, or negotiated per-student (scholarships/discounts common in real schools)?
5. Is a Teacher login (attendance/marks entry) needed from day one, or can Phase A go live with Admin/Accountant only entering everything?
6. For O-Level/A-Level, are numeric marks/GPA enough, or is a specific grading scale (Cambridge A*-U, etc.) required?
