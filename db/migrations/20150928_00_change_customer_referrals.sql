ALTER TABLE `referrals`
DROP COLUMN `name`,
ADD COLUMN `customer_id` BIGINT(20) NULL AFTER `id`,
CHANGE COLUMN `primary_phone` `primary_phone_number` VARCHAR(100) NULL DEFAULT NULL,
ADD COLUMN `country` VARCHAR(100) NULL AFTER `zip_code`;
