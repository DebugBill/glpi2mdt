<?php
/*
 -------------------------------------------------------------------------
 GLPI to MDT plugin for GLPI
 Copyright (C) 2017 by Blaise Thauvin.

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
 along with Glpi2mdt. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Blaise Thauvin
// Purpose of file: Plugin initiator
// ----------------------------------------------------------------------

class PluginGlpi2mdtConfig extends CommonDBTM {

   static protected $notable = true;

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         if ($item->getType() == 'Config') {
            return __('Glpi2mdt plugin');
         }
      }
      return '';
   }

   static function configUpdate($input) {
      $input['configuration'] = 1 - $input['configuration'];
      return $input;
   }

   function showFormGlpi2mdt() {
      global $CFG_GLPI;

      if (!Session::haveRight("config", UPDATE)) {
         return false;
      }

      $my_config = Config::getConfigurationValues('plugin:Glpi2mdt');

      echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL('Config')."\" method='post'>";
      echo "<div class='center' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>" . __('Glpi2mdt setup') . "</th></tr>";
      echo "<td >" . __('My boolean choice :') . "</td>";
      echo "<td colspan='3'>";
      echo "<input type='hidden' name='config_class' value='".__CLASS__."'>";
      echo "<input type='hidden' name='config_context' value='plugin:Glpi2mdt'>";
      Dropdown::showYesNo("configuration", $my_config['configuration']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='4' class='center'>";
      echo "<input type='submit' name='update' class='submit' value=\""._sx('button', 'Save')."\">";
      echo "</td></tr>";

      echo "</table></div>";
      Html::closeForm();
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType() == 'Config') {
         $config = new self();
         $config->showFormGlpi2mdt();
      }
   }

}
