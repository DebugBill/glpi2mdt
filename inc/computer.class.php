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
// Purpose of file: 
// ----------------------------------------------------------------------

// Class of the defined type

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginGlpi2mdtComputer extends CommonDBTM {

   static function showInfo() {

      echo '<table class="tab_glpi" width="100%">';
      echo '<tr>';
      echo '<th>'.__('More information').'</th>';
      echo '</tr>';
      echo '<tr class="tab_bg_1">';
      echo '<td>';
      echo __('Test successful');
      echo '</td>';
      echo '</tr>';
      echo '</table>';
   }

   // implement "item_can" hook (9.2) for Computer
   static function restrict(Computer $comp) {
      // no right to see computer from group 1
      if (isset($comp->right)) {
         // call from ConnDBTM::can method, filter for current item
         if ($comp->getField('groups_id') == 1) {
            $comp->right = false;
         }
      } else {
         // called from Search::addDefaultWhere method, return additional condition
         $comp->add_where = "glpi_computers.groups_id != 1";
      }
   }
}

