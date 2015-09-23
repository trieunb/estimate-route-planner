ALTER TABLE `customers`
ADD COLUMN `bill_address_id` INT(11) NULL AFTER `company_name`,
ADD COLUMN `ship_address_id` INT(11) NULL AFTER `bill_country`,
ADD COLUMN `ship_address` TEXT NULL AFTER `ship_address_id`,
ADD COLUMN `ship_city` VARCHAR(255) NULL AFTER `ship_address`,
ADD COLUMN `ship_state` VARCHAR(255) NULL AFTER `ship_city`,
ADD COLUMN `ship_zip_code` VARCHAR(255) NULL AFTER `ship_state`,
ADD COLUMN `ship_country` VARCHAR(255) NULL AFTER `ship_zip_code`;
