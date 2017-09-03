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

// ----------------------------------------------------------------------
// Original Author of file: Blaise Thauvin
// Purpose of file: Form to manipulation global configuration parameters
// ----------------------------------------------------------------------

include ("../../../inc/includes.php");

Html::header("TITRE", $_SERVER['PHP_SELF'], "config", "plugins");

//Session::checkRight('plugin_glpi2mdt_configuration', READ);

$g2mConfig = new PluginGlpi2mdtConfig();

// Save configuration data
if (isset($_POST['SAVE'])) {
   $data = $_POST;
   foreach ($data as $key=>$value) {
      $g2mConfig->updateValue($key, $value);
   }
   // Only reload page if Save button was pressed
   Html::back();
}

$g2mConfig->loadConf();
$g2mConfig->show();

// Test connection (will save first ...)
if (isset($_POST['TEST'])) {
   $g2mConfig->showTestConnection();
}

// Initialise data (will NOT save first but use curently stored credentials)
if (isset($_POST['INIT'])) {
   $g2mConfig->showInitialise();
}

// Check for new version of the plugin
if (isset($_POST['UPDATE'])) {
   PluginGlpi2mdtToolbox::checkNewVersionAvailable(false, false);
}
Html::footer();

