# Issue #1 — Assets list

CareSystem manages **metadata** for educational media. Bytes for videos stay on YouTube (or another host). Private audio can be uploaded into the app and streamed only through authenticated download.

| Asset | Hosting decision | How the app stores it |
| --- | --- | --- |
| Public videos | YouTube (or other public URL) | `asset_type = VIDEO`, `storage_type = URL`, `is_public = 1` |
| Private videos | Unlisted / private YouTube (or other restricted URL) | Same columns, `is_public = 0` (staff library + send-to-patient) |
| Public audio | Public URL (podcast host, Drive link, etc.) | `asset_type = AUDIO`, `storage_type = URL`, `is_public = 1` |
| Private audio | File under `uploads/assets/` (Apache blocked; download via `assets.php?action=download`) | `asset_type = AUDIO`, `storage_type = LOCAL`, `is_public = 0` |

## Why this split

- The app does **not** re-host YouTube. Staff paste the link in the Asset Library (`assets.php`).
- Private audio answers “where to host?” for clinic-owned clips: keep the file on the CareSystem server, deny direct URL access with `uploads/.htaccess`, and gate playback/download through role / patient-send checks.
- Default visibility is Public; uncheck **Public** for private assets.

## Related

- Asset library UI: `assets.php` / `app/views/admin/assets.php`
- Controller: `app/controllers/AssetController.php`
- Broader public press / testimonial videos: Issue #6 notes when present
