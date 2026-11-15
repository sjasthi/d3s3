-- Issue #9: seed public Press Coverage and Photos assets (Google Drive folders).
-- Safe to run more than once: skips rows that already use the same resource_url.

INSERT INTO assets
	(title, description, asset_type, category, storage_type, resource_url, is_public, is_active, created_at, updated_at)
SELECT
	'Press Coverage folder',
	'Google Drive folder with press coverage for D3 Sarada Service Society.',
	'OTHER',
	'Press Coverage',
	'URL',
	'https://drive.google.com/drive/folders/1q-oZUlt7Wx9vck8uWmz4PGkOy8kQsWyf',
	1,
	1,
	NOW(),
	NOW()
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM assets
	 WHERE resource_url = 'https://drive.google.com/drive/folders/1q-oZUlt7Wx9vck8uWmz4PGkOy8kQsWyf'
);

INSERT INTO assets
	(title, description, asset_type, category, storage_type, resource_url, is_public, is_active, created_at, updated_at)
SELECT
	'Operations Photos folder',
	'Google Drive folder with photographs reflecting clinic and camp operations.',
	'IMAGE',
	'Photos',
	'URL',
	'https://drive.google.com/drive/folders/1UugOb70IHuFjY54yrWWxNJhx7o5kGjyQ',
	1,
	1,
	NOW(),
	NOW()
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM assets
	 WHERE resource_url = 'https://drive.google.com/drive/folders/1UugOb70IHuFjY54yrWWxNJhx7o5kGjyQ'
);
