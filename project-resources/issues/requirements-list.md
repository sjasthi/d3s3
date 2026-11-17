# Issue #3 — Requirements (needs, wants, wishes)

Traceability for the original requirements list against the CareSystem application. Status values: **Met**, **Partial**, **Explore**.

| # | Requirement | Status | Where it lives now |
| --- | --- | --- | --- |
| 1 | Preserve the original public site | Met | The org site [d3saradaservicesociety.org](https://d3saradaservicesociety.org/) stays the public brochure site. CareSystem (`index.php`) is the staff + patient portal app and does not replace it. Public press / testimonial / photo links are asset metadata (see Assets / Issue #6). |
| 2 | iPad icon-like interface for admins | Met | AdminLTE card / tile layout on `dashboard.php` and `admin.php`. Responsive Bootstrap layout works on tablet. |
| 3 | Scan papers / pictures into DB entries | Partial | Consent forms, lab-report uploads, and case-sheet attachments (e.g. ultrasound images via `case_file.php` / `case_attachments`) store files and link them to records. **OCR / AI form scan** (auto-fill fields from a photo) remains an explore item — not automated today. |
| 4 | Multi-lingual (Telugu and English) | Met | English / తెలుగు toggle (`data-lang`) on staff screens; `app/config/lang.php` + `lang/labels_en.csv` / `lang/labels_te.csv`. |
| 5 | Patient records: audio, video, images, formal records | Met | Case sheets (`case_sheets`), private case file uploads, asset library (`assets.php`), patient portal health record. Formal clinical notes live on the case sheet; media is uploaded or linked as metadata. |
| 6 | Admin reports: trends & success | Met | `analytics.php` — overview, caseload, outcomes, satisfaction, trends, printable report. |
| 7 | Visitor-level high-level numbers | Partial | Public asset listings (press / testimonials / photos when those pages are present) and the org website. CareSystem does not publish a separate anonymous visitor metrics dashboard; high-level visitor content is the public brochure + public assets. |
| 8 | Events / calendar / schedule | Met | `calendar.php` (camps / seminars via `events`), `appointments.php` for clinical schedule. |
| 9 | Contact / triaging | Met | Intake (`intake.php`), triage nurse role + queue, `messages.php` for contact threads, appointments for follow-up. |
| 10 | Stack: HTML, CSS, JS, jQuery, Bootstrap, PHP, MySQL | Met | Running XAMPP / Apache + MariaDB (MySQL-compatible) app. Vendor assets under `assets/`. |
| 11 | Deployment — Bluehost | Met | Shared PHP hosting target. See `sql/README.md` (Bluehost production notes) and `project-resources/getting-started/QUICKSTART.md` (local XAMPP). |

## Gaps called out for later work

- **#3 Explore:** AI / OCR scan of paper forms into structured case-sheet fields.
- **#7 Partial:** Dedicated public “visitor numbers” page inside CareSystem (if still desired beyond the org site).

## Related docs

- Database domains: [db-tables.md](db-tables.md) (Issue #5)
- Module owners (VVIT): [../Module_Ownership.md](../Module_Ownership.md)
- Original high-level data note: [../high_level_requirements.txt](../high_level_requirements.txt)
