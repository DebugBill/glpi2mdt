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

define('PLUGIN_GLPI2MDT_VERSION', '0.3.0');

// Minimal GLPI version, inclusive
define("PLUGIN_GLPI2MDT_MIN_GLPI", "9.5");
// Maximum GLPI version, exclusive
//define("PLUGIN_GLPI2MDT_MAX_GLPI", "9.6");

/**
 * Init hooks of the plugin.
 *
 * @return void
 */
function plugin_init_glpi2mdt() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['glpi2mdt'] = true;
   // Any update to a computer should trigger an update in MDT, just in case....
   $PLUGIN_HOOKS['item_update']['glpi2mdt']    = array('Computer' => 'updateMDT');

   $Plugin = new Plugin();

   if ($Plugin->isActivated('glpi2mdt')) {

      // Register classes into GLPI plugin factory if plugin is active

      // Add tab on Computers page
      Plugin::registerClass('PluginGlpi2mdtComputer', array('addtabon' => array('Computer')));
      Plugin::registerClass('PluginGlpi2mdtConfig');

      // Config page
      if (Session::haveRight('config', UPDATE)) {
         $PLUGIN_HOOKS['config_page']['glpi2mdt'] = 'front/config.form.php';
      }
      /*
       * Deploy submenu entries
       */
      if (Session::haveRight('plugin_glpi2mdt_configuration', READ)) {
         $PLUGIN_HOOKS['submenu_entry']['glpi2mdt']['config'] = 'front/config.form.php';
      }
   }
}


/**
 * Get the name and the version of the plugin
 *
 * @return array
 */
function plugin_version_glpi2mdt() {
   return [
		'name'           => 'GLPI 2 MDT',
		'shortname'      => 'glpi2mdt',
		'version'        => PLUGIN_GLPI2MDT_VERSION,
		'author'         => 'Blaise Thauvin',
		'homepage'       => 'https://github.com/DebugBill/glpi2mdt',
		'license'        => 'GPLv3+',
		'requirements'   => [
			'glpi' => [
				'min' => PLUGIN_GLPI2MDT_MIN_GLPI,
//				'max' =>dd PLUGIN_GLPI2MDT_MAX_GLPI,  Who knows if it will be compatible with next version. Maybe yes
				'dev' => true, //Required to allow 9.2-dev
			]
		]
	];
}

/**
 * Check pre-requisites before install
 *
 * @return boolean
 */
function plugin_glpi2mdt_check_prerequisites() {
   // GLPI 9.1.1 is the strict minimum in any case
   if (version_compare(GLPI_VERSION, '9.5', 'lt')) {
      if (method_exists('Plugin', 'messageIncompatible')) {
         echo Plugin::messageIncompatible('core', '9.5');
      } else {
         echo "This plugin requires GLPI >= 9.5";
      }
      return false;
   }

   // The plugin needs to access the MSSQL MDT database, PHP modules needed
   if (!extension_loaded("sqlsrv")) {
      echo __('Incompatible PHP Installation. Requires PHP module SQLSRV', 'glpi2mdt');
      return false;
   }
   // The plugin needs to process some XML files from the MDT deployment share, PHP module needed
   if (!extension_loaded("simpleXML")) {
      echo __('Incompatible PHP Installation. Requires module', 'glpi2mdt'). " simpleXML";
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
