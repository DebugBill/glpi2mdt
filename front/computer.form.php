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
// Purpose of file: Form to manipulate additional computer data
// ----------------------------------------------------------------------
 
// Load GLPI 
define('GLPI_ROOT', '../../..');
include(GLPI_ROOT . '/inc/includes.php');
 
if ($_POST && isset($_POST['clone']) && isset($_POST['id'])) {
 
    // Check that a name has been passed
    if (!isset($_POST['name']) or empty($_POST['name'])) {
        Html::displayErrorAndDie('Please specified date');
    }
 
    // Load the Computer to be cloned 
    $Computer = new Computer();
    $Computer->getFromDB($_POST['id']);
 
    // Reset id and change the name
    $Computer->fields['id'] = 'NULL';
    $Computer->fields['name'] = $_POST['name'];
 
    // Save the new Computer to the DataBase
    $Computer->addToDB();
 
    // Redirect the user to the new Computer
    $url = explode("?", $_SERVER['HTTP_REFERER']);
    Html::redirect($url[0] . "?id=" . $Computer->getID());
 
}
