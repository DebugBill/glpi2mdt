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

   if (!TableExists("glpi_plugin_glpi2mdt_parameters")) {
      $query = "CREATE TABLE `glpi_plugin_glpi2mdt_parameters` (
                  `id` int(11) NOT NULL auto_increment,
                  `parameter` varchar(50) collate utf8_unicode_ci default NULL,
                  `scope` varchar(50) collate utf8_unicode_ci NOT NULL default 'global',
                  `value_num` decimal(9,2) signed default null,
                  `value_char` varchar(50) collate utf8_unicode_ci default NULL,
                  `is_deleted` boolean NOT NULL default false,
                PRIMARY KEY (`id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->query($query) or die("error creating glpi_plugin_glpi2mdt_parameters ". $DB->error());

      $query = "INSERT INTO `glpi_plugin_glpi2mdt_parameters`
                       (`id`, `parameter`, `scope`, `value_num`, `is_deleted`)
                       VALUES (1, 'database_version', 'global', 1, false)";
      $DB->query($query) or die("error updating glpi_plugin_glpi2mdt_parameters ". $DB->error());
   }
   if (!TableExists("glpi_plugin_glpi2mdt_roles")) {
      $query = "CREATE TABLE `glpi_plugin_glpi2mdt_roles` (
                  `id` int(11) NOT NULL auto_increment,
                  `parameter` varchar(50) collate utf8_unicode_ci default NULL,
                  `scope` varchar(50) collate utf8_unicode_ci NOT NULL default 'global',
                  `value_num` decimal(9,2) signed default null,
                  `value_char` varchar(50) collate utf8_unicode_ci default NULL,
                  `is_deleted` boolean NOT NULL default false,
                PRIMARY KEY (`id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->query($query) or die("error creating glpi_plugin_glpi2mdt_roles ". $DB->error());
   }
   if (!TableExists("glpi_plugin_glpi2mdt_applications")) {
      $query = "CREATE TABLE `glpi_plugin_glpi2mdt_applications` (
                  
                  `id` int(11) NOT NULL auto_increment,
                  `parameter` varchar(50) collate utf8_unicode_ci default NULL,
                  `scope` varchar(50) collate utf8_unicode_ci NOT NULL default 'global',
                  `value_num` decimal(9,2) signed default null,
                  `value_char` varchar(50) collate utf8_unicode_ci default NULL,
                  `is_deleted` boolean NOT NULL default false,
                PRIMARY KEY (`id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->query($query) or die("error creating glpi_plugin_glpi2mdt_applications ". $DB->error());
   }
   if (!TableExists("glpi_plugin_glpi2mdt_taskslist")) {
      $query = "CREATE TABLE `glpi_plugin_glpi2mdt_taskslists` (
                  
                  `id` int(11) NOT NULL auto_increment,
                  `parameter` varchar(50) collate utf8_unicode_ci default NULL,
                  `scope` varchar(50) collate utf8_unicode_ci NOT NULL default 'global',
                  `value_num` decimal(9,2) signed default null,
                  `value_char` varchar(50) collate utf8_unicode_ci default NULL,
                  `is_deleted` boolean NOT NULL default false,
                PRIMARY KEY (`id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->query($query) or die("error creating glpi_plugin_glpi2mdt_taskslists ". $DB->error());
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

   if (TableExists("glpi_plugin_glpi2mdt_parameters")) {
      $query = "DROP TABLE `glpi_plugin_glpi2mdt_parameters`";
      $DB->query($query) or die("error deleting glpi_plugin_glpi2mdt_parameters");
   }
   if (TableExists("glpi_plugin_glpi2mdt_roles")) {
      $query = "DROP TABLE `glpi_plugin_glpi2mdt_roles`";
      $DB->query($query) or die("error deleting glpi_plugin_glpi2mdt_roles");
   }
   if (TableExists("glpi_plugin_glpi2mdt_applications")) {
      $query = "DROP TABLE `glpi_plugin_glpi2mdt_applications`";
      $DB->query($query) or die("error deleting glpi_plugin_glpi2mdt_applications");
   }
   if (TableExists("glpi_plugin_glpi2mdt_taskslists")) {
      $query = "DROP TABLE `glpi_plugin_glpi2mdt_taskslists`";
      $DB->query($query) or die("error deleting glpi_plugin_glpi2mdt_taskslists");
   }
   return true;
}
