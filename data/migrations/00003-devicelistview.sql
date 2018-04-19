CREATE OR REPLACE VIEW `view_devices_list` AS
SELECT
	`devices`.`iddevices` AS `id`,
	`categories`.`name` AS `category_name`,
	`categories`.`idcategories` AS `idcategory`,
	`devices`.`brand` AS `brand`,
	`devices`.`model` AS `model`,
	`devices`.`problem` AS `problem`,
	`groups`.`idgroups` AS `idgroup`,
  `groups`.`name` AS `group_name`,
  `events`.`location` AS `event_location`,
  `events`.`latitude` AS `event_latitude`,
  `events`.`longitude` AS `event_longitude`,
  UNIX_TIMESTAMP(`events`.`event_date`) AS `event_date`,
  `users`.`name` AS `restarter`,
	`devices`.`repair_status` AS `repair_status`,
	`devices`.`created_at` AS `sorter`
FROM `devices`
	JOIN `categories` ON `categories`.`idcategories` = `devices`.`category`
	JOIN `events` ON `events`.`idevents` = `devices`.`event`
  JOIN `groups` ON `groups`.`idgroups` = `events`.`group`
  JOIN `users` ON `users`.`idusers` = `devices`.`repaired_by`
;
