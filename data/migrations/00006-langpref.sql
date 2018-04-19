ALTER TABLE `users` 
ADD COLUMN `language` VARCHAR(2) NOT NULL DEFAULT 'en' AFTER `recovery_expires`;
