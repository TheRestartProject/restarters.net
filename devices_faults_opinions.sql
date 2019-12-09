ALTER TABLE `devices_faults_opinions_v1` CHANGE `created_at` `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `devices_faults_opinions_v1` ADD `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `session_id`;

