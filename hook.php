<?php
/*
 -------------------------------------------------------------------------
 glpi2mdt plugin for GLPI
 Copyright (C) 2017 by the glpi2mdt Development Team.

 https://github.com/DebugBill/glpi2mdt
 -------------------------------------------------------------------------

 LICENSE

 This file is part of glpi2mdt.

 glpi2mdt is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 glpi2mdt is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with glpi2mdt. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_glpi2mdt_install() {
   global $DB;

   // Global plugin settings
   if (!TableExists("glpi_plugin_glpi2mdt_parameters")) {
      $query = "CREATE TABLE `glpi_plugin_glpi2mdt_parameters` (
                   `id` int(11) NOT NULL AUTO_INCREMENT,
                   `parameter` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
                   `scope` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'global',
                   `value_num` decimal(9,2) DEFAULT NULL,
                   `value_char` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
                   `is_deleted` boolean NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                UNIQUE KEY `Constraint` (`parameter`,`scope`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->query($query) or die("error creating glpi_plugin_glpi2mdt_parameters ". $DB->error());

      $query = "INSERT INTO `glpi_plugin_glpi2mdt_parameters`
                       (`id`, `parameter`, `scope`, `value_num`, `is_deleted`)
                       VALUES (1, 'database_version', 'global', 1, false)";
      $DB->query($query) or die("error updating glpi_plugin_glpi2mdt_parameters ". $DB->error());
   }
   
   // Individual settings for computers, models and roles
   if (!TableExists("glpi_plugin_glpi2mdt_settings")) {
      $query = "CREATE TABLE `glpi_plugin_glpi2mdt_settings` (
                   `id` int(11) NOT NULL,
                   `type` varchar(1) COLLATE utf8_unicode_ci NOT NULL,
                   `key` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
                   `value` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
                   `is_in_sync` boolean NOT NULL DEFAULT true,
                PRIMARY KEY (`id`, `type`, `key`)
                ) ENGINE=MyISAM AUTO_INCREMENT=34 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

       $DB->query($query) or die("error creating glpi_plugin_glpi2mdt_settings ". $DB->error());
   }

   // Available roles extracted from MDT database
   if (!TableExists("glpi_plugin_glpi2mdt_roles")) {
      $query = "CREATE TABLE `glpi_plugin_glpi2mdt_roles` (
                  `id` int(11) NOT NULL,
                  `role` varchar(255) collate utf8_unicode_ci default NULL,
                  `is_deleted` boolean NOT NULL default true,
                  `is_in_sync` boolean NOT NULL default false,
                PRIMARY KEY (`id`),
                INDEX `Constraint` (`is_deleted` ASC)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->query($query) or die("error creating glpi_plugin_glpi2mdt_roles ". $DB->error());
   }
   
   // Available applications, extracted from XML file on installation share
   if (!TableExists("glpi_plugin_glpi2mdt_applications")) {
      $query = "CREATE TABLE `glpi_plugin_glpi2mdt_applications` (  
                   `guid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,  
                   `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,  
                   `shortname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,  
                   `version` varchar(255) COLLATE utf8_unicode_ci NOT NULL,  
                   `hide` boolean NOT NULL DEFAULT false,  
                   `enable` boolean NOT NULL DEFAULT true,  
                   `is_deleted` boolean NOT NULL DEFAULT false,  
                   `is_in_sync` boolean NOT NULL DEFAULT true,  
                PRIMARY KEY (`guid`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";


      $DB->query($query) or die("error creating glpi_plugin_glpi2mdt_applications ". $DB->error());
   }

   if (!TableExists("glpi_plugin_glpi2mdt_application_groups")) {
      $query = "CREATE TABLE `glpi_plugin_glpi2mdt_application_groups` (  
                   `guid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,  
                   `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                   `hide` boolean NOT NULL DEFAULT false,  
                   `enable` boolean NOT NULL DEFAULT '1',  
                   `is_deleted` boolean NOT NULL DEFAULT false,  
                   `is_in_sync` boolean NOT NULL DEFAULT true,  
                PRIMARY KEY (`guid`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

   $DB->query($query) or die("error creating glpi_plugin_glpi2mdt_application_groups ". $DB->error());
   }

   if (!TableExists("glpi_plugin_glpi2mdt_application_group_links")) {
      $query = "CREATE TABLE `glpi_plugin_glpi2mdt_task_application_group_links` (  
                   `group_guid` varchar(38) COLLATE utf8_unicode_ci NOT NULL,  
                   `application_guid` varchar(38) COLLATE utf8_unicode_ci NOT NULL,  
                   `is_deleted` boolean NOT NULL DEFAULT false,  
                   `is_in_sync` boolean NOT NULL DEFAULT true,  
                PRIMARY KEY (`group_guid`, `application_guid`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

      $DB->query($query) or die("error creating glpi_plugin_glpi2mdt_application_group_links ". $DB->error());
   }

   // Available task sequences, extracted from XML file on installation share
   if (!TableExists("glpi_plugin_glpi2mdt_task_sequences")) {
      $query = "CREATE TABLE `glpi_plugin_glpi2mdt_task_sequences` (  
                   `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                   `guid` varchar(38) COLLATE utf8_unicode_ci NOT NULL,  
                   `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,  
                   `hide` boolean NOT NULL DEFAULT false,  
                   `enable` boolean NOT NULL DEFAULT true,  
                   `is_deleted` boolean NOT NULL DEFAULT false,  
                   `is_in_sync` boolean NOT NULL DEFAULT true,  
                PRIMARY KEY (`id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

      $DB->query($query) or die("error creating glpi_plugin_glpi2mdt_task_sequences ". $DB->error());
   }

   if (!TableExists("glpi_plugin_glpi2mdt_task_sequence_groups")) {
      $query = "CREATE TABLE `glpi_plugin_glpi2mdt_task_sequence_groups` (  
                   `guid` varchar(38) COLLATE utf8_unicode_ci NOT NULL,  
                   `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,  
                   `hide` boolean NOT NULL DEFAULT false,  
                   `enable` boolean NOT NULL DEFAULT true,  
                   `is_deleted` boolean NOT NULL DEFAULT false,  
                   `is_in_sync` boolean NOT NULL DEFAULT true,  
                PRIMARY KEY (`guid`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

      $DB->query($query) or die("error creating glpi_plugin_glpi2mdt_task_sequence_groups ". $DB->error());
   }

   if (!TableExists("glpi_plugin_glpi2mdt_task_sequence_group_links")) {
      $query = "CREATE TABLE `glpi_plugin_glpi2mdt_task_sequence_group_links` (  
                   `group_guid` varchar(38) COLLATE utf8_unicode_ci NOT NULL,  
                   `sequence_guid` varchar(38) COLLATE utf8_unicode_ci NOT NULL,  
                   `is_deleted` boolean NOT NULL DEFAULT '0',  
                   `is_in_sync` boolean NOT NULL DEFAULT '1',  
                PRIMARY KEY (`group_guid`, `sequence_guid`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

      $DB->query($query) or die("error creating glpi_plugin_glpi2mdt_task_sequence_group_links ". $DB->error());
   }

   // All valid parameters for MDT objects
   if (!TableExists("glpi_plugin_glpi2mdt_descriptions")) {
      $query = "CREATE TABLE `glpi_plugin_glpi2mdt_descriptions` (
                  `column_name` varchar(255) collate utf8_unicode_ci NOT NULL,
                  `category_order` integer collate utf8_unicode_ci NOT NULL default 0,
                  `category` varchar(255) default '',
                  `description` varchar(255) collate utf8_unicode_ci default '',
                  `is_deleted` boolean NOT NULL DEFAULT false,
                  `is_in_sync` boolean NOT NULL DEFAULT true,
                PRIMARY KEY (`column_name`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->query($query) or die("error creating glpi_plugin_glpi2mdt_descriptions ". $DB->error());
   }
   return true;
}

/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_glpi2mdt_uninstall() {
   global $DB;

   $result = $DB->query("SHOW TABLES LIKE 'glpi_plugin_glpi2mdt_%';");
   foreach ($rows = $DB->fetch_array($result) as $column=>$table) {
      $DB->query("DROP TABLE $table");  // or die("error deleting table $table ".$DB->error());
   }
   return true;
}
