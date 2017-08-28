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
// Purpose of file: Plugin general settings management
// ----------------------------------------------------------------------

// Class of the defined type

class PluginGlpi2mdtConfig extends  CommonDropdown
{
     /**
     * This function is called from GLPI to setup the dropdown
     */

 static function getTypeName($nb=0) {

      if ($nb > 0) {
         return __('Plugin iGlpi2mdt Dropdowns', 'glpi2mdt');
      }
      return __('Plugin Glpi2mdt Dropdowns', 'glpi2mdt');
   }

    /**
     * This function is called from GLPI to render the form when the user click
     *  on the menu item generated from getTabNameForItem()
     */
static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0)
    {
         ?>
        <form action="../plugins/glpi2mdt/front/config.form.php" method="post">
            <?php echo Html::hidden('id', array('value' => $item->getID())); ?>
            <?php echo Html::hidden('_glpi_csrf_token', array('value' => Session::getNewCSRFToken())); ?>
            <div class="spaced" id="tabsbody">
                <table class="tab_cadre_fixe">
                    <tr class="tab_bg_1">
                        <td>
                            Database server name: &nbsp;&nbsp;&nbsp;
                            <input type="text" name="dbserver" size="50" class="ui-autocomplete-input" autocomplete="off"> &nbsp;&nbsp;&nbsp;
                            <input type="submit" class="submit" value="DBSERVER" name="DBServer"/>
                        </td>
                    </tr>
                </table>
            </div>
        </form>
        <?php
        return true;
    }

}

