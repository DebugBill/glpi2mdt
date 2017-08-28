<?php
/*
 -------------------------------------------------------------------------
 glpi2mdt plugin for GLPI
 Copyright (C) 2017 by Blaise Thauvin

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

define('PLUGIN_GLPI2MDT_VERSION', '0.0.1');

/**
 * Init hooks of the plugin.
 *
 * @return void
 */
function plugin_init_glpi2mdt() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['glpi2mdt'] = true;

   // Config page
   if (Session::haveRight('config', UPDATE)) {
      $PLUGIN_HOOKS['config_page']['glpi2mdt'] = 'config.php';
   }

   // Add tab on Computers page
   Plugin::registerClass('PluginGlpi2mdtComputer', array('addtabon' => array('Computer')));
  
   // Add menu item to plugins/configuration
   Plugin::registerClass('PluginGlpi2mdtConfig', array('addtabon' => array('Computer')));
}


/**
 * Get the name and the version of the plugin
 *
 * @return array
 */
function plugin_version_glpi2mdt() {
   return array('name'           => 'GLPI 2 MDT',
                'version'        => PLUGIN_GLPI2MDT_VERSION,
                'author'         => 'Blaise Thauvin',
                'license'        => 'GPLv3+',
                'homepage'       => 'https://github.com/DebugBill/glpi2mdt',
                'minGlpiVersion' => '9.1.1');// For compatibility / no install in version < 9.1.1

}

/**
 * Check pre-requisites before install
 *
 * @return boolean
 */
function plugin_glpi2mdt_check_prerequisites() {
   // GLPI 9.1.1 is the strict minimum in any case
   if (version_compare(GLPI_VERSION, '9.1', 'lt')) {
      if (method_exists('Plugin', 'messageIncompatible')) {
         echo Plugin::messageIncompatible('core', '9.1');
      } else {
         echo "This plugin requires GLPI >= 9.1";
      }
      return false;
   }

   // Just warns for GLPI != 9.1.6 as not tested but should work
   // if (version_compare(GLPI_VERSION, '9.1.56', 'ne')) {
   // echo "This plugin is tested only with GLPI 9.1.6. Use with caution.<br>";
   // }

   // The plugin needs to access the MSSQL MDT database
   if (!extension_loaded("mssql")) {
      echo __('Incompatible PHP Installation. Requires module',
              'glpi2mdt'). " mssql";
      return false;
   }
   return true;
}

/**
 * Check configuration process
 *
 * @param boolean $verbose Whether to display message on failure. Defaults to false
 *
 * @return boolean
 */
function plugin_glpi2mdt_check_config($verbose = false) {
   if (true) { // Your configuration check
      return true;
   }

   if ($verbose) {
      _e('Installed / not configured', 'glpi2mdt');
   }
   return false;
}
