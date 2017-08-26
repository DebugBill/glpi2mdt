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
 the Free Software Foundation; either version 2 of the License, or
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
 * REQUIRED
 *
 * @return void
 */
function plugin_init_glpi2mdt() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['glpi2mdt'] = true;
}


/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_glpi2mdt() {
   return [
      'name'           => 'glpi2mdt',
      'version'        => PLUGIN_GLPI2MDT_VERSION,
      'author'         => '<a href="http://www.teclib.com">Teclib\'</a>',
      'license'        => '',
      'homepage'       => '',
      'minGlpiVersion' => '9.1'
   ];
}

/**
 * Check pre-requisites before install
 * OPTIONNAL, but recommanded
 *
 * @return boolean
 */
function plugin_glpi2mdt_check_prerequisites() {
   // Strict version check (could be less strict, or could allow various version)
   if (version_compare(GLPI_VERSION, '9.1', 'lt')) {
      if (method_exists('Plugin', 'messageIncompatible')) {
         echo Plugin::messageIncompatible('core', '9.1');
      } else {
         echo "This plugin requires GLPI >= 9.1";
      }
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
