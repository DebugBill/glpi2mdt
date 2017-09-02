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

include ("../../../inc/includes.php");

Html::header(_('Features'), $_SERVER["PHP_SELF"]);

//Session::checkRight('plugin_glpi2mdt_configuration', READ);

$g2mComputer = new PluginGlpi2mdtComputer();

// Save computer settings if necessary
if ((isset($_POST['SAVE'])) and (isset($_POST['id']))) {
   $data = $_POST;
   $g2mComputer->updateValue($_POST);
   $g2mComputer->updateMDT($_POST['id']);

   // Only reload page if Save button was pressed
}

// Reload page 
Html::back();

