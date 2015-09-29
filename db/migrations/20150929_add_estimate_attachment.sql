ALTER TABLE `estimate_attachments`
ADD COLUMN `is_customer_signature` TINYINT(1) NOT NULL DEFAULT 0 AFTER `file_name`;
