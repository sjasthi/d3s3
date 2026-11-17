-- Issue #6: seed public beneficiary testimonial videos (YouTube Shorts + Drive folder).
-- Tagging: asset_type=VIDEO (or OTHER for the Drive folder), category=Testimonials, is_public=1.
-- Safe to run more than once: skips rows that already use the same resource_url.

INSERT INTO assets
	(title, description, asset_type, category, storage_type, resource_url, is_public, is_active, created_at, updated_at)
SELECT
	'Beneficiary testimonial 1',
	'Public YouTube Short testimonial from a beneficiary.',
	'VIDEO', 'Testimonials', 'URL',
	'https://www.youtube.com/shorts/MjymewZkjSg',
	1, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM assets WHERE resource_url IN (
		'https://www.youtube.com/shorts/MjymewZkjSg',
		'https://youtube.com/shorts/MjymewZkjSg?si=F1KCOWkyFRxYyia4'
	)
);

INSERT INTO assets
	(title, description, asset_type, category, storage_type, resource_url, is_public, is_active, created_at, updated_at)
SELECT
	'Beneficiary testimonial 2',
	'Public YouTube Short testimonial from a beneficiary.',
	'VIDEO', 'Testimonials', 'URL',
	'https://www.youtube.com/shorts/rAL8-72aaAo',
	1, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM assets WHERE resource_url LIKE '%rAL8-72aaAo%'
);

INSERT INTO assets
	(title, description, asset_type, category, storage_type, resource_url, is_public, is_active, created_at, updated_at)
SELECT
	'Beneficiary testimonial 3',
	'Public YouTube Short testimonial from a beneficiary.',
	'VIDEO', 'Testimonials', 'URL',
	'https://www.youtube.com/shorts/p0wpuMDBVg0',
	1, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM assets WHERE resource_url LIKE '%p0wpuMDBVg0%'
);

INSERT INTO assets
	(title, description, asset_type, category, storage_type, resource_url, is_public, is_active, created_at, updated_at)
SELECT
	'Beneficiary testimonial 4',
	'Public YouTube Short testimonial from a beneficiary.',
	'VIDEO', 'Testimonials', 'URL',
	'https://www.youtube.com/shorts/L_7N571gdwg',
	1, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM assets WHERE resource_url LIKE '%L_7N571gdwg%'
);

INSERT INTO assets
	(title, description, asset_type, category, storage_type, resource_url, is_public, is_active, created_at, updated_at)
SELECT
	'Beneficiary testimonial 5',
	'Public YouTube Short testimonial from a beneficiary.',
	'VIDEO', 'Testimonials', 'URL',
	'https://www.youtube.com/shorts/qZNHnPkc-Q0',
	1, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM assets WHERE resource_url LIKE '%qZNHnPkc-Q0%'
);

INSERT INTO assets
	(title, description, asset_type, category, storage_type, resource_url, is_public, is_active, created_at, updated_at)
SELECT
	'Beneficiary testimonial 6',
	'Public YouTube Short testimonial from a beneficiary.',
	'VIDEO', 'Testimonials', 'URL',
	'https://www.youtube.com/shorts/PwoHA9fAIDs',
	1, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM assets WHERE resource_url LIKE '%PwoHA9fAIDs%'
);

INSERT INTO assets
	(title, description, asset_type, category, storage_type, resource_url, is_public, is_active, created_at, updated_at)
SELECT
	'Beneficiary testimonial 7',
	'Public YouTube Short testimonial from a beneficiary.',
	'VIDEO', 'Testimonials', 'URL',
	'https://www.youtube.com/shorts/XIYaWHod53g',
	1, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM assets WHERE resource_url LIKE '%XIYaWHod53g%'
);

INSERT INTO assets
	(title, description, asset_type, category, storage_type, resource_url, is_public, is_active, created_at, updated_at)
SELECT
	'Beneficiary testimonial 8',
	'Public YouTube Short testimonial from a beneficiary.',
	'VIDEO', 'Testimonials', 'URL',
	'https://www.youtube.com/shorts/3vSH16N5IqY',
	1, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM assets WHERE resource_url LIKE '%3vSH16N5IqY%'
);

INSERT INTO assets
	(title, description, asset_type, category, storage_type, resource_url, is_public, is_active, created_at, updated_at)
SELECT
	'Testimonials Drive folder',
	'Google Drive folder with beneficiary testimonial videos.',
	'OTHER', 'Testimonials', 'URL',
	'https://drive.google.com/drive/folders/1wINM6mV7Zm9uzhIetAbvoMtaNwgGGco3',
	1, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM assets
	 WHERE resource_url = 'https://drive.google.com/drive/folders/1wINM6mV7Zm9uzhIetAbvoMtaNwgGGco3'
);
