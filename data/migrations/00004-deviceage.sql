ALTER TABLE `devices`
ADD COLUMN `age` VARCHAR(255) NULL COMMENT '// kept as  free text to capture data type after research' AFTER `model`;
