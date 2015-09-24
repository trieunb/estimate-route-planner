ALTER TABLE `estimates`
ADD COLUMN `job_country` VARCHAR(255) NULL AFTER `job_zip_code`,
ADD COLUMN `job_address_id` INT(11) NULL AFTER `job_customer_id`;
