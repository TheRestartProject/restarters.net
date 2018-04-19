ALTER TABLE `users`
ADD COLUMN `recovery` VARCHAR(45) NULL AFTER `role`,
ADD COLUMN `recovery_expires` TIMESTAMP NULL AFTER `recovery`;
