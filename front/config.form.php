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
 
// Load GLPI 
define('GLPI_ROOT', '../../..');
include(GLPI_ROOT . '/inc/includes.php');
 
if ($_POST && isset($_POST['DBServer']) && isset($_POST['id'])) {
 
    // Check that a server name has been passed
    if (!isset($_POST['DBServer']) or empty($_POST['DBServer'])) {
        Html::displayErrorAndDie('Please specifiy server');
    }
 
    // Store configuration parameters
   global $DB;
   $DBServer = $_POST['DBServer'];
   $query = "INSERT INTO `glpi_plugin_glpi2mdt_parameters`
                       (`parameter`, `scope`, `value_char`, `is_deleted`)
                       VALUES ('DBServer', 'global', '$DBServer', false)";
   $DB->query($query) or die("Error saving database server name ". $DB->error()); 
}
