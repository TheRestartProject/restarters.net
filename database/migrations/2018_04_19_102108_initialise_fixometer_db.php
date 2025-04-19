<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {

        //Settings
        DB::statement('SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";');
        DB::statement('SET time_zone = "+00:00";');

        //Create Tables
        //Categories
        DB::statement('CREATE TABLE IF NOT EXISTS `categories` (
                           `idcategories` int(11) NOT NULL,
                           `name` varchar(255) NOT NULL,
                           `weight` float DEFAULT NULL,
                           `footprint` float DEFAULT NULL,
                           `footprint_reliability` int(11) DEFAULT NULL,
                           `lifecycle` int(11) DEFAULT NULL,
                           `lifecycle_reliability` int(11) DEFAULT NULL,
                           `extendend_lifecycle` int(11) DEFAULT NULL,
                           `extendend_lifecycle_reliability` int(11) DEFAULT NULL,
                           `revision` int(11) NOT NULL,
                           `cluster` int(11) DEFAULT NULL
                         ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
           );

        //Category Revisions
        DB::statement('CREATE TABLE IF NOT EXISTS `category_revisions` (
                           `idcategory_revisions` int(11) NOT NULL,
                           `revision` varchar(255) NOT NULL,
                           `created_at` timestamp NULL DEFAULT NULL,
                           `modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                         ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
           );

        //Clusters
        DB::statement('CREATE TABLE IF NOT EXISTS `clusters` (
                           `idclusters` int(11) NOT NULL,
                           `name` varchar(255) NOT NULL
                         ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
           );

        //Devices
        DB::statement('CREATE TABLE IF NOT EXISTS `devices` (
                           `iddevices` int(11) NOT NULL,
                           `event` int(11) NOT NULL,
                           `category` int(11) NOT NULL,
                           `category_creation` int(11) NOT NULL,
                           `estimate` varchar(10) DEFAULT NULL,
                           `brand` varchar(255) DEFAULT NULL,
                           `model` varchar(255) DEFAULT NULL,
                           `problem` text,
                           `spare_parts` tinyint(1) NOT NULL DEFAULT "0",
                           `repair_status` int(11) NOT NULL,
                           `professional_help` tinyint(1) NOT NULL DEFAULT "0",
                           `more_time_needed` tinyint(1) NOT NULL DEFAULT "0",
                           `do_it_yourself` tinyint(1) NOT NULL DEFAULT "0",
                           `repaired_by` int(11) DEFAULT NULL,
                           `created_at` timestamp NULL DEFAULT NULL,
                           `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                         ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
           );

        //Events
        DB::statement('CREATE TABLE IF NOT EXISTS `events` (
                           `idevents` int(11) NOT NULL,
                           `group` int(11) NOT NULL,
                           `event_date` date NOT NULL DEFAULT "1970-01-01",
                           `start` time NOT NULL,
                           `end` time NOT NULL,
                           `location` varchar(255) NOT NULL,
                           `latitude` float NOT NULL,
                           `longitude` float NOT NULL,
                           `free_text` text,
                           `pax` int(11) DEFAULT NULL,
                           `volunteers` int(11) DEFAULT NULL,
                           `hours` float DEFAULT NULL,
                           `wordpress_post_id` int(11) DEFAULT NULL,
                           `created_at` timestamp NULL DEFAULT NULL,
                           `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                         ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
           );

        //Event Users
        DB::statement('CREATE TABLE IF NOT EXISTS `events_users` (
                           `idevents_users` int(11) NOT NULL,
                           `event` int(11) NOT NULL,
                           `user` int(11) NOT NULL
                         ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
           );

        //Groups
        DB::statement('CREATE TABLE IF NOT EXISTS `groups` (
                           `idgroups` int(11) NOT NULL,
                           `name` varchar(255) NOT NULL,
                           `location` varchar(255) DEFAULT NULL,
                           `latitude` varchar(25) DEFAULT NULL,
                           `longitude` varchar(25) DEFAULT NULL,
                           `area` varchar(255) DEFAULT NULL,
                           `frequency` int(11) DEFAULT NULL,
                           `free_text` text,
                           `wordpress_post_id` int(11) DEFAULT NULL,
                           `created_at` timestamp NULL DEFAULT NULL,
                           `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                         ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
           );

        //Images
        DB::statement('CREATE TABLE IF NOT EXISTS `images` (
                           `idimages` int(11) NOT NULL,
                           `path` varchar(255) NOT NULL,
                           `alt_text` text,
                           `width` int(11) DEFAULT NULL,
                           `height` int(11) DEFAULT NULL,
                           `created_at` timestamp NULL DEFAULT NULL,
                           `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                         ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
           );

        //Links
        DB::statement('CREATE TABLE IF NOT EXISTS `links` (
                           `idlinks` int(11) NOT NULL,
                           `title` varchar(255) DEFAULT NULL,
                           `url` varchar(255) NOT NULL,
                           `created_at` timestamp NULL DEFAULT NULL,
                           `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                         ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
           );

        //Permissions
        DB::statement('CREATE TABLE IF NOT EXISTS `permissions` (
                           `idpermissions` int(11) NOT NULL,
                           `permission` varchar(150) NOT NULL COMMENT "Manage Users; Manage Restart Party; Manage devices"
                         ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
           );

        //Roles
        DB::statement('CREATE TABLE IF NOT EXISTS `roles` (
                           `idroles` int(11) NOT NULL,
                           `role` varchar(45) NOT NULL COMMENT "Needed to assign blocks of permissions to groups of users. 1 = Admin; 2 = Hosts; 3 = Volunteer"
                         ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
           );

        //Roles Permissions
        DB::statement('CREATE TABLE IF NOT EXISTS `roles_permissions` (
                           `idroles_permissions` int(11) NOT NULL,
                           `role` int(11) NOT NULL,
                           `permission` int(11) NOT NULL
                         ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
           );

        //Sessions
        DB::statement('CREATE TABLE IF NOT EXISTS `sessions` (
                           `idsessions` int(11) NOT NULL,
                           `session` varchar(255) NOT NULL,
                           `user` int(11) NOT NULL,
                           `created_at` timestamp NULL DEFAULT NULL,
                           `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                         ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
           );

        //Users
        DB::statement('CREATE TABLE IF NOT EXISTS `users` (
                           `idusers` int(11) NOT NULL,
                           `email` varchar(255) NOT NULL,
                           `password` varchar(60) NOT NULL,
                           `name` varchar(255) NOT NULL,
                           `role` int(11) NOT NULL DEFAULT "3",
                           `created_at` timestamp NULL DEFAULT NULL,
                           `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                         ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
           );

        //User Groups
        DB::statement('CREATE TABLE IF NOT EXISTS `users_groups` (
                           `idusers_groups` int(11) NOT NULL,
                           `user` int(11) NOT NULL,
                           `group` int(11) NOT NULL
                         ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
           );

        //Waste Emission Ratio
        // DB::statement('CREATE TABLE IF NOT EXISTS `view_waste_emission_ratio` (
        //                 `Ratio` double(21,4)
        //               );'
        // );

        //XREF
        DB::statement('CREATE TABLE IF NOT EXISTS `xref` (
                           `idxref` int(11) NOT NULL,
                           `object` int(11) NOT NULL,
                           `object_type` int(11) NOT NULL,
                           `reference` int(11) NOT NULL,
                           `reference_type` int(11) NOT NULL
                         ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
           );

        //Drop table
        DB::statement('DROP VIEW IF EXISTS `view_waste_emission_ratio`;');

        //EO Table Creation

        //Add DB User
        DB::statement('CREATE VIEW `view_waste_emission_ratio` AS select (round((sum(`categories`.`footprint`) * 0.5),0) / round(sum(`categories`.`weight`),0)) AS `Ratio` from (`devices` join `categories` on((`categories`.`idcategories` = `devices`.`category`))) where (`devices`.`repair_status` = 1);'
         );

        //Alter Tables

        //Add

        //Categories
        DB::statement('ALTER TABLE `categories`
                             ADD PRIMARY KEY (`idcategories`),
                             ADD KEY `idxCategoryRevisions` (`revision`),
                             ADD KEY `idxCategoryCluster` (`cluster`);'
             );

        //Category Revisions
        DB::statement('ALTER TABLE `category_revisions`
                             ADD PRIMARY KEY (`idcategory_revisions`);'
             );

        //Clusters
        DB::statement('ALTER TABLE `clusters`
                             ADD PRIMARY KEY (`idclusters`);'
             );

        //Devices
        DB::statement('ALTER TABLE `devices`
                             ADD PRIMARY KEY (`iddevices`),
                             ADD KEY `idxDeviceEvent` (`event`),
                             ADD KEY `idxDeviceCategory` (`category`),
                             ADD KEY `idxDeviceCategoryCreation` (`category_creation`),
                             ADD KEY `idxDeviceUser` (`repaired_by`);'
             );

        //Events
        DB::statement('ALTER TABLE `events`
                             ADD PRIMARY KEY (`idevents`),
                             ADD KEY `idxEventsGroups` (`group`);'
             );

        //Event Users
        DB::statement('ALTER TABLE `events_users`
                             ADD PRIMARY KEY (`idevents_users`),
                             ADD KEY `idxEventsUsersEvent` (`event`),
                             ADD KEY `idxEventsUsersUser` (`user`);'
             );

        //Groups
        DB::statement('ALTER TABLE `groups`
                             ADD PRIMARY KEY (`idgroups`),
                             ADD UNIQUE KEY `name_UNIQUE` (`name`);'
             );

        //Images
        DB::statement('ALTER TABLE `images`
                             ADD PRIMARY KEY (`idimages`);'
             );

        //Links
        DB::statement('ALTER TABLE `links`
                             ADD PRIMARY KEY (`idlinks`);'
             );

        //Permissions
        DB::statement('ALTER TABLE `permissions`
                             ADD PRIMARY KEY (`idpermissions`);'
             );

        //Roles
        DB::statement('ALTER TABLE `roles`
                             ADD PRIMARY KEY (`idroles`);'
             );

        //Roles Permissions
        DB::statement('ALTER TABLE `roles_permissions`
                             ADD PRIMARY KEY (`idroles_permissions`),
                             ADD KEY `idxRolePermissionRole` (`role`),
                             ADD KEY `idxRolePermissionPermission` (`permission`);'
             );

        //Sessions
        DB::statement('ALTER TABLE `sessions`
                             ADD PRIMARY KEY (`idsessions`),
                             ADD UNIQUE KEY `session_UNIQUE` (`session`),
                             ADD KEY `idxSessionsUsers` (`user`);'
             );

        //Users
        DB::statement('ALTER TABLE `users`
                             ADD PRIMARY KEY (`idusers`),
                             ADD UNIQUE KEY `email_UNIQUE` (`email`),
                             ADD KEY `idxUserRole` (`role`);'
             );

        //User Groups
        DB::statement('ALTER TABLE `users_groups`
                             ADD PRIMARY KEY (`idusers_groups`),
                             ADD KEY `idxUserUsers` (`user`),
                             ADD KEY `idxGroupGroups` (`group`);'
             );

        //XREF
        DB::statement('ALTER TABLE `xref`
                             ADD PRIMARY KEY (`idxref`),
                             ADD KEY `idxObject` (`object`),
                             ADD KEY `idxObjectType` (`object_type`),
                             ADD KEY `idxReference` (`reference`),
                             ADD KEY `idxReferenceType` (`reference_type`);'
             );

        //EO Add

        //Modify

        //Categories
        DB::statement('ALTER TABLE `categories`
                             MODIFY `idcategories` int(11) NOT NULL AUTO_INCREMENT;'
             );

        //Category Revisions
        DB::statement('ALTER TABLE `category_revisions`
                             MODIFY `idcategory_revisions` int(11) NOT NULL AUTO_INCREMENT;'
             );

        //Clusters
        DB::statement('ALTER TABLE `clusters`
                             MODIFY `idclusters` int(11) NOT NULL AUTO_INCREMENT;'
             );

        //Devices
        DB::statement('ALTER TABLE `devices`
                             MODIFY `iddevices` int(11) NOT NULL AUTO_INCREMENT;'
             );

        //Events
        DB::statement('ALTER TABLE `events`
                             MODIFY `idevents` int(11) NOT NULL AUTO_INCREMENT;'
             );

        //Event Users
        DB::statement('ALTER TABLE `events_users`
                             MODIFY `idevents_users` int(11) NOT NULL AUTO_INCREMENT;'
             );

        //Groups
        DB::statement('ALTER TABLE `groups`
                             MODIFY `idgroups` int(11) NOT NULL AUTO_INCREMENT;'
             );

        //Images
        DB::statement('ALTER TABLE `images`
                             MODIFY `idimages` int(11) NOT NULL AUTO_INCREMENT;'
             );

        //Links
        DB::statement('ALTER TABLE `links`
                             MODIFY `idlinks` int(11) NOT NULL AUTO_INCREMENT;'
             );

        //Permissions
        DB::statement('ALTER TABLE `permissions`
                             MODIFY `idpermissions` int(11) NOT NULL AUTO_INCREMENT;'
             );

        //Roles
        DB::statement('ALTER TABLE `roles`
                             MODIFY `idroles` int(11) NOT NULL AUTO_INCREMENT;'
             );

        //Roles Permissions
        DB::statement('ALTER TABLE `roles_permissions`
                             MODIFY `idroles_permissions` int(11) NOT NULL AUTO_INCREMENT;'
             );

        //Sessions
        DB::statement('ALTER TABLE `sessions`
                             MODIFY `idsessions` int(11) NOT NULL AUTO_INCREMENT;'
             );

        //Users
        DB::statement('ALTER TABLE `users`
                             MODIFY `idusers` int(11) NOT NULL AUTO_INCREMENT;'
             );

        //User Groups
        DB::statement('ALTER TABLE `users_groups`
                             MODIFY `idusers_groups` int(11) NOT NULL AUTO_INCREMENT;'
             );

        //XREF
        DB::statement('ALTER TABLE `xref`
                             MODIFY `idxref` int(11) NOT NULL AUTO_INCREMENT;'
             );

        //EO Modify

        //Foreign Keys

        //Categories
        DB::statement('ALTER TABLE `categories`
                             ADD CONSTRAINT `fkCategoryCluster` FOREIGN KEY (`cluster`) REFERENCES `clusters` (`idclusters`) ON DELETE NO ACTION ON UPDATE NO ACTION,
                             ADD CONSTRAINT `fkCategoryRevisions` FOREIGN KEY (`revision`) REFERENCES `category_revisions` (`idcategory_revisions`) ON DELETE NO ACTION ON UPDATE NO ACTION;'
             );

        //Devices
        DB::statement('ALTER TABLE `devices`
                             ADD CONSTRAINT `fkDeviceCategory` FOREIGN KEY (`category`) REFERENCES `categories` (`idcategories`) ON DELETE NO ACTION ON UPDATE NO ACTION,
                             ADD CONSTRAINT `fkDeviceCategoryCreation` FOREIGN KEY (`category_creation`) REFERENCES `categories` (`idcategories`) ON DELETE NO ACTION ON UPDATE NO ACTION,
                             ADD CONSTRAINT `fkDeviceEvent` FOREIGN KEY (`event`) REFERENCES `events` (`idevents`) ON DELETE NO ACTION ON UPDATE NO ACTION,
                             ADD CONSTRAINT `fkDeviceUser` FOREIGN KEY (`repaired_by`) REFERENCES `users` (`idusers`) ON DELETE NO ACTION ON UPDATE NO ACTION;'
             );

        //Events
        DB::statement('ALTER TABLE `events`
                             ADD CONSTRAINT `fkEventsGroups` FOREIGN KEY (`group`) REFERENCES `groups` (`idgroups`) ON DELETE NO ACTION ON UPDATE NO ACTION;'
             );

        //Event Users
        DB::statement('ALTER TABLE `events_users`
                             ADD CONSTRAINT `fkEventsUsersEvent` FOREIGN KEY (`event`) REFERENCES `events` (`idevents`) ON DELETE NO ACTION ON UPDATE NO ACTION,
                             ADD CONSTRAINT `fkEventsUsersUser` FOREIGN KEY (`user`) REFERENCES `users` (`idusers`) ON DELETE NO ACTION ON UPDATE NO ACTION;'
             );

        //Role Permissions
        DB::statement('ALTER TABLE `roles_permissions`
                             ADD CONSTRAINT `fkRolePermissionPermission` FOREIGN KEY (`permission`) REFERENCES `permissions` (`idpermissions`) ON DELETE NO ACTION ON UPDATE NO ACTION,
                             ADD CONSTRAINT `fkRolePermissionRole` FOREIGN KEY (`role`) REFERENCES `roles` (`idroles`) ON DELETE NO ACTION ON UPDATE NO ACTION;'
             );

        //Sessions
        DB::statement('ALTER TABLE `sessions`
                             ADD CONSTRAINT `fkSessionsUsers` FOREIGN KEY (`user`) REFERENCES `users` (`idusers`) ON DELETE NO ACTION ON UPDATE NO ACTION;'
             );

        //Users
        DB::statement('ALTER TABLE `users`
                             ADD CONSTRAINT `fkUserRole` FOREIGN KEY (`role`) REFERENCES `roles` (`idroles`) ON DELETE NO ACTION ON UPDATE NO ACTION;'
             );

        //User Groups
        DB::statement('ALTER TABLE `users_groups`
                             ADD CONSTRAINT `fkGroupGroups` FOREIGN KEY (`group`) REFERENCES `groups` (`idgroups`) ON DELETE CASCADE ON UPDATE NO ACTION,
                             ADD CONSTRAINT `fkUserUsers` FOREIGN KEY (`user`) REFERENCES `users` (`idusers`) ON DELETE CASCADE ON UPDATE NO ACTION;'
             );

        //EO Foreign Keys

        //EO Alter Tables

        //Insert Into Tables

        //Category Revisions
        DB::statement('INSERT INTO `category_revisions` (`idcategory_revisions`, `revision`, `created_at`, `modified_at`) VALUES
                           (1, "First Revision", NULL, "2015-05-19 08:15:03");'
           );

        //Clusters
        DB::statement('INSERT INTO `clusters` (`idclusters`, `name`) VALUES
                           (1, "Computers and Home Office"),
                           (2, "Electronic Gadgets"),
                           (3, "Home Entertainment"),
                           (4, "Kitchen and Household Items");'
           );

        //Permissions
        DB::statement('INSERT INTO `permissions` (`idpermissions`, `permission`) VALUES
                           (1, "Create User"),
                           (2, "Edit User"),
                           (3, "Delete User"),
                           (4, "Create Party");'
           );

        //Roles
        DB::statement('INSERT INTO `roles` (`idroles`, `role`) VALUES
                           (1, "Root"),
                           (2, "Administrator"),
                           (3, "Host"),
                           (4, "Restarter"),
                           (5, "Guest");'
           );

        //Categories
        DB::statement('INSERT INTO `categories` (`idcategories`, `name`, `weight`, `footprint`, `footprint_reliability`, `lifecycle`, `lifecycle_reliability`, `extendend_lifecycle`, `extendend_lifecycle_reliability`, `revision`, `cluster`) VALUES
                           (11, "Desktop computer", 9.15, 398.4, 5, NULL, NULL, NULL, NULL, 1, 1),
                           (12, "Flat screen 15-17\"", 2.7, 72.4, 2, NULL, NULL, NULL, NULL, 1, 1),
                           (13, "Flat screen 19-20\"", 3.72, 102.93, 5, NULL, NULL, NULL, NULL, 1, 1),
                           (14, "Flat screen 22-24\"", 5, 167.8, 5, NULL, NULL, NULL, NULL, 1, 1),
                           (15, "Laptop large", 2.755, 322.79, 5, NULL, NULL, NULL, NULL, 1, 1),
                           (16, "Laptop medium", 2.26, 258.25, 5, NULL, NULL, NULL, NULL, 1, 1),
                           (17, "Laptop small", 2.14, 142.18, 4, NULL, NULL, NULL, NULL, 1, 1),
                           (18, "Paper shredder", 7, 47.7, 2, NULL, NULL, NULL, NULL, 1, 1),
                           (19, "PC Accessory", 1.185, 18.87, 4, NULL, NULL, NULL, NULL, 1, 1),
                           (20, "Printer/scanner", 7.05, 47.7, 4, NULL, NULL, NULL, NULL, 1, 1),
                           (21, "Digital Compact Camera", 0.113, 6.13, 4, NULL, NULL, NULL, NULL, 1, 2),
                           (22, "DLSR / Video Camera", 0.27, 4.05, 4, NULL, NULL, NULL, NULL, 1, 2),
                           (23, "Handheld entertainment device", 0.149, 13, 4, NULL, NULL, NULL, NULL, 1, 2),
                           (24, "Headphones", 0.26, 4.05, 3, NULL, NULL, NULL, NULL, 1, 2),
                           (25, "Mobile", 0.14, 35.82, 4, NULL, NULL, NULL, NULL, 1, 2),
                           (26, "Tablet", 0.51, 107.76, 5, NULL, NULL, NULL, NULL, 1, 2),
                           (27, "Flat screen 26-30\"", 10.6, 284.25, 1, NULL, NULL, NULL, NULL, 1, 3),
                           (28, "Flat screen 32-37\"", 18.7, 359.4, 3, NULL, NULL, NULL, NULL, 1, 3),
                           (29, "Hi-Fi integrated", 10.9, 109.5, 3, NULL, NULL, NULL, NULL, 1, 3),
                           (30, "Hi-Fi separates", 10.9, 109.5, 4, NULL, NULL, NULL, NULL, 1, 3),
                           (31, "Musical instrument", 10.9, 109.5, 3, NULL, NULL, NULL, NULL, 1, 3),
                           (32, "Portable radio", 2.5, 66, 2, NULL, NULL, NULL, NULL, 1, 3),
                           (33, "Projector", 2.35, 46, 4, NULL, NULL, NULL, NULL, 1, 3),
                           (34, "TV and gaming-related accessories", 1, 25, 4, NULL, NULL, NULL, NULL, 1, 3),
                           (35, "Aircon/Dehumidifier", 18.5, 109.53, 2, NULL, NULL, NULL, NULL, 1, 4),
                           (36, "Decorative or safety lights", 0.015, 13.43, 4, NULL, NULL, NULL, NULL, 1, 4),
                           (37, "Fan", 0.88, 4.52, 2, NULL, NULL, NULL, NULL, 1, 4),
                           (38, "Hair & Beauty item", 0.69, 6, 4, NULL, NULL, NULL, NULL, 1, 4),
                           (39, "Kettle", 1.4, 17.1, 4, NULL, NULL, NULL, NULL, 1, 4),
                           (40, "Lamp", 0.703, 4.62, 2, NULL, NULL, NULL, NULL, 1, 4),
                           (41, "Power tool", 2.84, 26.6, 3, NULL, NULL, NULL, NULL, 1, 4),
                           (42, "Small kitchen item", 2.7, 15.8, 4, NULL, NULL, NULL, NULL, 1, 4),
                           (43, "Toaster", 1.04, 5, 2, NULL, NULL, NULL, NULL, 1, 4),
                           (44, "Toy", 1.27, 15, 4, NULL, NULL, NULL, NULL, 1, 4),
                           (45, "Vacuum", 7.78, 41, 4, NULL, NULL, NULL, NULL, 1, 4),
                           (46, "Misc", 1, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL);'
           );

        //EO Insert Into Tables
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        //
    }
};
