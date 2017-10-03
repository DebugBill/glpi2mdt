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
 * Plugin install process, create databases and crontasks
 *
 * @return boolean
 */
function plugin_glpi2mdt_install() {
   global $DB;
   $dbversion = 1;

   // Global plugin settings
   if (!TableExists("glpi_plugin_glpi2mdt_parameters")) {
      $query = "CREATE TABLE `glpi_plugin_glpi2mdt_parameters` (
                   `id` int(11) NOT NULL AUTO_INCREMENT,
                   `parameter` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
                   `scope` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'global',
                   `value_num` int(11) DEFAULT NULL,
                   `value_char` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
                   `is_deleted` boolean NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                UNIQUE KEY `Constraint` (`parameter`,`scope`),
                INDEX `is_deleted` (`is_deleted` ASC)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->query($query) or die("error creating glpi_plugin_glpi2mdt_parameters ". $DB->error());

      $query = "INSERT INTO `glpi_plugin_glpi2mdt_parameters`
                       (`id`, `parameter`, `scope`, `value_num`, `is_deleted`)
                       VALUES (1, 'DBVersion', 'global', $dbversion, false)";
      $DB->query($query) or die("error updating glpi_plugin_glpi2mdt_parameters ". $DB->error());
   }

   // Individual settings for computers, models and roles
   if (!TableExists("glpi_plugin_glpi2mdt_settings")) {
      $query = "CREATE TABLE `glpi_plugin_glpi2mdt_settings` (
                 `id` int(11) NOT NULL auto_increment,
                 `category` varchar(1) COLLATE utf8_unicode_ci NOT NULL,
                 `type` varchar(1) COLLATE utf8_unicode_ci NOT NULL,
                 `key` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
                 `value` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
                 `is_in_sync` tinyint(1) NOT NULL DEFAULT '1',
                PRIMARY KEY (`id`, `type`,`category`,`key`),
                KEY `is_in_sync` (`is_in_sync`),
                KEY `type` (`type`),
                KEY `category` (`category`)
                ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

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
                INDEX `is_deleted` (`is_deleted` ASC),
                INDEX `is_in_sync`(`is_in_sync` ASC)
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
                PRIMARY KEY (`guid`),
                INDEX `is_deleted` (`is_deleted` ASC),
                INDEX `is_in_sync`(`is_in_sync` ASC)
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
                PRIMARY KEY (`guid`),
                INDEX `is_deleted` (`is_deleted` ASC),
                INDEX `is_in_sync`(`is_in_sync` ASC)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->query($query) or die("error creating glpi_plugin_glpi2mdt_application_groups ". $DB->error());
   }

   if (!TableExists("glpi_plugin_glpi2mdt_application_group_links")) {
      $query = "CREATE TABLE `glpi_plugin_glpi2mdt_application_group_links` (  
                   `group_guid` varchar(38) COLLATE utf8_unicode_ci NOT NULL,  
                   `application_guid` varchar(38) COLLATE utf8_unicode_ci NOT NULL,  
                   `is_deleted` boolean NOT NULL DEFAULT false,  
                   `is_in_sync` boolean NOT NULL DEFAULT true,  
                PRIMARY KEY (`group_guid`, `application_guid`),
                INDEX `is_deleted` (`is_deleted` ASC),
                INDEX `is_in_sync`(`is_in_sync` ASC)
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
                PRIMARY KEY (`id`),
                INDEX `is_deleted` (`is_deleted` ASC),
                INDEX `is_in_sync`(`is_in_sync` ASC)
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
                PRIMARY KEY (`guid`),
                INDEX `is_deleted` (`is_deleted` ASC),
                INDEX `is_in_sync`(`is_in_sync` ASC)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

      $DB->query($query) or die("error creating glpi_plugin_glpi2mdt_task_sequence_groups ". $DB->error());
   }

   if (!TableExists("glpi_plugin_glpi2mdt_task_sequence_group_links")) {
      $query = "CREATE TABLE `glpi_plugin_glpi2mdt_task_sequence_group_links` (  
                   `group_guid` varchar(38) COLLATE utf8_unicode_ci NOT NULL,  
                   `sequence_guid` varchar(38) COLLATE utf8_unicode_ci NOT NULL,  
                   `is_deleted` boolean NOT NULL DEFAULT '0',  
                   `is_in_sync` boolean NOT NULL DEFAULT '1',  
                PRIMARY KEY (`group_guid`, `sequence_guid`),
                INDEX `is_deleted` (`is_deleted` ASC),
                INDEX `is_in_sync`(`is_in_sync` ASC)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

      $DB->query($query) or die("error creating glpi_plugin_glpi2mdt_task_sequence_group_links ". $DB->error());
   }

   // Make and Models association
   if (!TableExists("glpi_plugin_glpi2mdt_models")) {
      $query = "CREATE TABLE `glpi_plugin_glpi2mdt_models` (
                 `id` int(11) NOT NULL,
                 `make` varchar(50) NOT NULL,
                 `name` varchar(50) DEFAULT NULL,
                 `tech_code` varchar(50) NOT NULL,
                 `is_in_sync` tinyint(4) NOT NULL DEFAULT '1',
                 `is_deleted` tinyint(4) NOT NULL DEFAULT '0',
                 `glpi_plugin_glpi2mdt_modelscol` varchar(45) DEFAULT NULL,
                PRIMARY KEY (`make`, `tech_code`),
                UNIQUE KEY (`id`),
                INDEX `is_deleted` (`is_deleted` ASC),
                INDEX `is_in_sync`(`is_in_sync` ASC)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->query($query) or die("error creating glpi_plugin_glpi2mdt_models ". $DB->error());
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
                PRIMARY KEY (`column_name`),
                INDEX `is_deleted` (`is_deleted` ASC),
                INDEX `is_in_sync`(`is_in_sync` ASC)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->query($query) or die("error creating glpi_plugin_glpi2mdt_descriptions ". $DB->error());
   }
   // Remove cron tasks
   Crontask::Unregister('Glpi2mdtCrontask');

   // Create or update crontask for checking new plugin updates and reporting usage
   CronTask::Register('PluginGlpi2mdtCrontask', 'checkGlpi2mdtUpdate', (3600 * 24),
                         array('mode' => 1, 'allowmode' => 3, 'logs_lifetime' => 30,
                               'comment' => 'Daily task checking for updates'));

   // Create or update crontask for updating base data from MDT files and database
   CronTask::Register('PluginGlpi2mdtCrontask', 'updateBaseconfigFromMDT', 300,
                         array('mode' => 1, 'allowmode' => 3, 'logs_lifetime' => 30,
                               'comment' => 'Update base data from MDT XML files and MS-SQL DB'));

   // Create or update crontask for syncrhonizing data between MDT and GLPI (Master-Master mode)
   CronTask::Register('PluginGlpi2mdtCrontask', 'syncMasterMaster', 3600,
                         array('mode' => 1, 'allowmode' => 3, 'logs_lifetime' => 30,
                               'comment' => 'Synchronize data between MDT and GLPI in Master-Master mode'));

   // Create or update crontask for disabling "OS Install" flag when expired
   CronTask::Register('PluginGlpi2mdtCrontask', 'expireOSInstallFlag', 300,
                         array('mode' => 1, 'allowmode' => 3, 'logs_lifetime' => 30,
                               'comment' => 'Disable "OS Install" flag when expired'));

   // Update database if necessary
   $DB->query("UPDATE glpi_plugin_glpi2mdt_parameters SET parameter='DBVersion' WHERE scope='global' AND parameter='database_version';");
   $result = $DB->query("SELECT sum(value_num) as version FROM glpi_plugin_glpi2mdt_parameters WHERE scope='global' AND parameter='DBVersion'");
   if ($DB->numrows($result) == 0) {
      die(__("Glpi2mdt database is corrupted. Please uninstall and reinstall the plugin", 'glpi2mdt'));
   }
   if ($DB->numrows($result) == 1) {
      $currentdbversion = $DB->fetch_array($result)['version'];
   }

   // Upgrade to version 2 of the database
   if ($currentdbversion == 1) {
      return true;
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

   // Delete tables (this will erase configuration data)
   $result = $DB->query("SHOW TABLES LIKE 'glpi_plugin_glpi2mdt_%';");
   while ($row = $DB->fetch_row($result)) {
      $DB->query("DROP TABLE $row[0]") or die("error deleting table $row[0] ".$DB->error());
   }

   // Remove cron tasks
   Crontask::Unregister('Glpi2mdtCrontask');

   return true;
}


/**
* This function is called by GLPI when an update is made to a computer
* It triggers an update of MDT just in case...
*
* @param  $item, object reference to a computer
* @return nothing
*/
function updateMDT($item) {
   $id = $item->getID();
   $computer = new PluginGlpi2mdtComputer;
   $computer->updateMDT($id);
   unset($computer);
}
