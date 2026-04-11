-- Migration 027: Assets Feature Upgrade
--
-- 1. Extend asset_type enum: add AUDIO and FORM types
-- 2. Add local_file_path column for uploaded files
-- 3. Create patient_assets table (staff sends an asset to a specific patient)

-- ── 1. Extend asset_type ────────────────────────────────────────────────────
ALTER TABLE assets
    MODIFY COLUMN asset_type
        ENUM('VIDEO','PDF','IMAGE','DOCUMENT','AUDIO','FORM','OTHER') NOT NULL;

-- ── 2. Add local file path ───────────────────────────────────────────────────
ALTER TABLE assets
    ADD COLUMN local_file_path VARCHAR(500) NULL
        COMMENT 'Relative path under uploads/assets/ for LOCAL storage type (e.g. 2026/04/uuid.pdf)'
    AFTER resource_url;

-- ── 3. Patient-asset delivery table ─────────────────────────────────────────
-- Records when a staff member sends an asset to a specific patient's portal.
-- Public assets (assets.is_public=1) are visible to all portal patients regardless.
CREATE TABLE IF NOT EXISTS patient_assets (
    patient_asset_id    BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    asset_id            BIGINT UNSIGNED   NOT NULL,
    patient_id          INT UNSIGNED      NOT NULL,
    sent_by_user_id     INT UNSIGNED      NULL,
    sent_at             DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_read             TINYINT(1)        NOT NULL DEFAULT 0,
    note                TEXT              NULL     COMMENT 'Optional note from staff to patient',

    PRIMARY KEY (patient_asset_id),
    UNIQUE KEY uq_patient_asset  (asset_id, patient_id),
    KEY idx_pa_patient           (patient_id),
    KEY idx_pa_unread            (patient_id, is_read),

    CONSTRAINT fk_pa_asset   FOREIGN KEY (asset_id)        REFERENCES assets   (asset_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pa_patient FOREIGN KEY (patient_id)      REFERENCES patients (patient_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pa_sender  FOREIGN KEY (sent_by_user_id) REFERENCES users    (user_id)
        ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Assets sent by staff to specific patients via the portal';
