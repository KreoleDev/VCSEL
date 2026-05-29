CREATE TABLE IF NOT EXISTS camera_testing (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    timestamp DATETIME NOT NULL,
    brightness DECIMAL(10, 2) NOT NULL,
    area DECIMAL(10, 2) NOT NULL,
    lot_number VARCHAR(50) NOT NULL DEFAULT 'LOT0000',
    serial_number VARCHAR(50) NOT NULL,
    acquired VARCHAR(20) NOT NULL DEFAULT 'Auto',
    tester_name VARCHAR(100) NOT NULL DEFAULT '',
    ext_user_id INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_camera_testing_serial_number (serial_number),
    INDEX idx_camera_testing_lot_number (lot_number),
    INDEX idx_camera_testing_timestamp (timestamp),
    INDEX idx_camera_testing_ext_user_id (ext_user_id)
);

DELIMITER //

CREATE PROCEDURE migrate_camera_testing_columns()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE()
      AND TABLE_NAME='camera_testing'
      AND COLUMN_NAME='tester_name'
  ) THEN
    ALTER TABLE camera_testing
      ADD COLUMN tester_name VARCHAR(100) NOT NULL DEFAULT '' AFTER acquired;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE()
      AND TABLE_NAME='camera_testing'
      AND COLUMN_NAME='ext_user_id'
  ) THEN
    ALTER TABLE camera_testing
      ADD COLUMN ext_user_id INT NULL AFTER tester_name;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA=DATABASE()
      AND TABLE_NAME='camera_testing'
      AND INDEX_NAME='idx_camera_testing_ext_user_id'
  ) THEN
    ALTER TABLE camera_testing
      ADD INDEX idx_camera_testing_ext_user_id (ext_user_id);
  END IF;
END//

DELIMITER ;

CALL migrate_camera_testing_columns();
DROP PROCEDURE migrate_camera_testing_columns;

INSERT INTO core_modules (module_id, title, path, ext_panel_id, sort_order)
SELECT 7014, 'Camera Testing', 'modules/addon/7014_camera_testing/index.php', panel_id, 99
FROM core_panels
WHERE title='Production'
ORDER BY sort_order
LIMIT 1
ON DUPLICATE KEY UPDATE
  title=VALUES(title),
  path=VALUES(path),
  ext_panel_id=VALUES(ext_panel_id),
  sort_order=VALUES(sort_order);

UPDATE core_module_rights
SET title='View'
WHERE ext_module_id=7014
  AND bit_position=0;

INSERT INTO core_module_rights (ext_module_id, bit_position, title)
SELECT 7014, 0, 'View'
WHERE NOT EXISTS (
  SELECT 1
  FROM core_module_rights
  WHERE ext_module_id=7014
    AND bit_position=0
);
