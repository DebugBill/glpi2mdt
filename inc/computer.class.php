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
// Purpose of file: Class to manipulate additional computer data
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginGlpi2mdtComputer extends CommonGLPI
{
     /**
     * This function is called from GLPI to allow the plugin to insert one or more item
     *  inside the left menu of a Itemtype.
     */
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      return self::createTabEntry('Auto Install');
   }

     /**
     * Update GLPI database
     */
    function updateValue($post) {
       global $DB; 
 
       $result = $DB->query("SELECT column_name FROM glpi_plugin_glpi2mdt_descriptions WHERE is_deleted=false");
       while ($line = $DB->fetch_array($result)) {
          $parameters[$line['column_name']]=true;
       }
       if (isset($post['id']) and ($post['id'] > 0)) {
          $id = $post['id'];
          foreach ($post as $key=>$value) {
             // only valid parameters may be inserted. The full list is in the "descriptions" database
             if (isset($parameters[$key])) {
                $query = "INSERT INTO glpi_dev.glpi_plugin_glpi2mdt_settings 
                             (`id`, `type`, `key`, `value`)
                             VALUES ($id, 'C', '$key', '$value')
                             ON DUPLICATE KEY UPDATE value='$value'";
                $DB->query($query) or die("Cannot update settings databse, query is $query");
            }
         }
      }
    }

     /**
     * Updates the MDT MSSQL database with information containted in GLPI's database
     */
     function updateMDT($id) {
        global $DB;

     }



    /**
     * This function is called from GLPI to render the form when the user click
     *  on the menu item generated from getTabNameForItem()
     */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $DB;
      // Load current settings from database
      $id = $item->getID();
      $osinstall = 'NO';
      $tasksequence = '';
      $query = "SELECT `key`, `value` FROM glpi_dev.glpi_plugin_glpi2mdt_settings WHERE type='C' and id='$id'";
      $result = $DB->query($query);
      while ($row=$DB->fetch_array($result)) {
         $key = $row['key'];
         $value = $row['value'];
         if ($key == 'OSInstall' and $value == 'YES') {
            $osinstall = 'YES';
         }
         if ($key == 'TaskSequenceID') {
            $tasksequence = $value;
         }
         if ($key == 'roles') {
            $roles = $value;
         }
         if ($key == 'applications') {
            $applications = $value;
         }

      }
         ?>
           <form action="../plugins/glpi2mdt/front/computer.form.php" method="post">
            <?php echo Html::hidden('id', array('value' => $id)); ?>
            <?php echo Html::hidden('_glpi_csrf_token', array('value' => Session::getNewCSRFToken())); ?>
            <div class="spaced" id="tabsbody">
                <table class="tab_cadre_fixe">
                    <tr class="tab_bg_1">
                        <?php
                          echo "<td>".__('Enable automatic installation', 'glpi2mdt')." :</td>";
                          echo "<td>";
                          Dropdown::showFromArray("OSInstall", array(
                             'YES' => "YES",
                             'NO' => "NO"), array(
                             'value' => "$osinstall")
                          );
                          echo "</td>";
                           ?>
                          </tr>
                    </tr>
                    <tr class="tab_bg_1">
                        <td>
                            Task sequence: &nbsp;&nbsp;&nbsp;
                        </td><td>
                            <input type="text" name="TaskSequenceID" <?php echo 'value="'.$tasksequence.'"' ?> size="40" class="ui-autocomplete-input" autocomplete="off"> &nbsp;&nbsp;&nbsp;
                        </td>
                    </tr>
                    <tr class="tab_bg_1">
                        <td>
                            Applications: &nbsp;&nbsp;&nbsp;
                        </td><td>
                            <input type="text" name="applications"  <?php echo 'value="'.$applications.'"' ?>size="40" class="ui-autocomplete-input" autocomplete="off"> &nbsp;&nbsp;&nbsp;
                        </td>
                    </tr>
                    <tr class="tab_bg_1">
                        <td>
                            Roles: &nbsp;&nbsp;&nbsp;
                        </td><td>
                            <input type="text" name="roles"  <?php echo 'value="'.$roles.'"' ?> size="40" class="ui-autocomplete-input" autocomplete="off"> &nbsp;&nbsp;&nbsp;
                        </td>
                    </tr>
                    <tr class="tab_bg_1">
                           <td>
                            <input type="submit" class="submit" value="Save" name="SAVE"/>
                           </td>
                          </tr>
                    </tr>
                </table>
            </div>
           </form>
            <?php
              return true;
   }

}

