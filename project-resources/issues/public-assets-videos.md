# Issue #6 — Public assets (videos)

Assets are tagged in the existing `assets` table:

| Tag idea from the issue | How it is stored |
| --- | --- |
| public / private | `assets.is_public` (1 = public, 0 = staff / sent-only) |
| testimonial | `assets.category = 'Testimonials'` |
| video | `assets.asset_type = VIDEO`, `storage_type = URL` |

YouTube Shorts and the Drive folder stay hosted externally. CareSystem only stores the URL.

## Where they appear
- Staff: `testimonials.php` and Asset Library (`assets.php`), filter category Testimonials
- Patient portal: public assets on Resources (`patient_portal.php?page=resources`)
- If Issue #9 is applied: also under **Photos and press** (`press.php`)

## Seed data
Migration `036_public_testimonial_videos.sql` inserts the eight Shorts from the issue plus the Drive folder, skipping duplicates by URL.
