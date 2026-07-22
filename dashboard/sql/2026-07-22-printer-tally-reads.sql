CREATE TABLE IF NOT EXISTS printer_tally_reads (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    read_at DATETIME NOT NULL,
    user_id VARCHAR(100) NULL,
    user_name VARCHAR(150) NULL,

    printer_name VARCHAR(150) NULL,
    usb_vendor_id INT NULL,
    usb_product_id INT NULL,
    usb_device_serial VARCHAR(100) NULL,
    manufacturer_serial_number CHAR(14) NULL,

    dot_count BIGINT NULL,
    form_count BIGINT NULL,
    void_count BIGINT NULL,
    burst_count BIGINT NULL,
    vault_install_count BIGINT NULL,
    total_time_on_hours BIGINT NULL,
    printer_resets BIGINT NULL,
    firmware_updates_count BIGINT NULL,
    external_sheets_loaded BIGINT NULL,
    ribbon_count BIGINT NULL,
    last_ribbon_change_dot_count BIGINT NULL,

    raw_serial_ascii VARCHAR(100) NULL,
    raw_tally_ascii VARCHAR(500) NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_printer_tally_reads_read_at (read_at),
    INDEX idx_printer_tally_reads_manufacturer_serial_number (manufacturer_serial_number),
    INDEX idx_printer_tally_reads_user_id (user_id)
);

INSERT INTO core_modules (module_id, title, path, ext_panel_id, sort_order)
SELECT 7015, 'Tally Reader', 'modules/addon/7015_tally_reader/index.php', panel_id, 100
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
WHERE ext_module_id=7015
  AND bit_position=0;

INSERT INTO core_module_rights (ext_module_id, bit_position, title)
SELECT 7015, 0, 'View'
WHERE NOT EXISTS (
  SELECT 1
  FROM core_module_rights
  WHERE ext_module_id=7015
    AND bit_position=0
);

UPDATE core_module_rights
SET title='Write'
WHERE ext_module_id=7015
  AND bit_position=1;

INSERT INTO core_module_rights (ext_module_id, bit_position, title)
SELECT 7015, 1, 'Write'
WHERE NOT EXISTS (
  SELECT 1
  FROM core_module_rights
  WHERE ext_module_id=7015
    AND bit_position=1
);
