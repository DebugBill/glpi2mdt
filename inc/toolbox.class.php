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


/** @file
* @brief Various functions used throughout the  Glpi2mdt plugin
*
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}


/**
 * Glpi2mdtToolbox class
**/
class PluginGlpi2mdtToolbox extends PluginGlpi2mdtMdt {

   /**
   * Widget to show a lisst of checkboxes splitted by groups
   *
   * @param $items: a three columns array with ID and descriptions to populate the checkboxes. One line per box
   * @param $values : default values for boxes. Expects true/false
   * @param $title : title to be used for the table header
   * @param $prefix : prefix to be added to all ids to build POST variables
   * @return none, but outputs HTML
   */
   static function showMultiSelect($items, $values, $title, $prefix) {

      echo '<table class="tab_cadre_fixe" width="100%">';
      $group = '';
      echo '<tr class="headerRow"><th colspan="2">'.$title.'<br></th></tr>';
      foreach ($items as $guid=>$value) {
         // Are we starting a new group?
         if ($group <> $value['group']) {
            echo '<tr class="tab_bg_1"><td colspan="2">'.$value['group'].'</td></tr>';
         }
         echo '<tr class="tab_bg_1"><td colspan="1">';
         echo '<span class="form-group-checkbox"><input id="'.$prefix.$guid.'" name="'.$prefix.$guid.'"type="checkbox" ';
         if (isset($values[$guid])) {
            echo 'checked ';
         }
         if ($value['enable'] == false) {
            echo 'disabled>'; } else {
            echo '>';
            }
            echo "</span>";
            $group = $value['group'];
            echo '</td><td>'.$value['name'].'</td></tr>';
      }
      echo '</table>';
   }

}

