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
     * This function is called from GLPI to allow the plugin to insert one or more items
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
     * Updates the MDT MSSQL database with information contained in GLPI's database
     *  Parameter is the GLPI object ID, here a computer
     */
   function updateMDT($id) {
      global $DB;
      $dbserver = $globalconfig['DBServer'];;
      $dbport = $globalconfig['DBPort'];
      $dblogin = $globalconfig['DBLogin'];
      $dbpassword= $globalconfig['DBPassword'];
      $dbschema = $config['DBSchema'];
      $mode = $globalconfig['Mode'];
      $fileshare = $globalconfig['FileShare'];
      $localadmin = $globalconfig['LocalAdmin'];
      $complexity = $globalconfig['Complexity'];

      // Get login parameters from database and connect to MSQSL server
      $glpi2mdtconfig = new PluginGlpi2mdtConfig;
      $glpi2mdtconfig->loadConf();
      $globalconfig = $glpi2mdtconfig->globalconfig;
      $link = mssql_connect($globalconfig['DBServer'], $globalconfig['DBLogin'], $globalconfig['DBPassword'])
       or die("<h1><font color='red'>Database login KO!</font></h1><br>");
      mssql_select_db($globalconfig['DBSchema'], $link)
       or die("<h1><font color='red'>Cannot switch to schema $dbschema on MSSQL server</font></h1><br>");

      // Get data for current computer
      $result = $DB->query("SELECT name, uuid, serial, otherserial
                              FROM glpi_computers    
                              WHERE id=$id AND is_deleted=false");
      $common = $DB->fetch_array($result);

      // Delete existing records in MDT bearing same name, uuid, serial or mac adresses
      $uuid = $common['uuid'];
      $name = $common['name'];
      $serial = $common['serial'];
      $assettag = $common['otherserial'];
      $query = "DELETE FROM $dbschema.dbo.ComputerIdentity 
                     WHERE (UUID<>'' AND UUID='$uuid')
                        OR (Description<>'' AND Description='$name')
                        OR (SerialNumber<>'' AND SerialNumber='$serial')";
      mssql_query("$query", $link) or die(mssql_get_last_message());
      $macs = $DB->query("SELECT UPPER(n.mac) as mac
                              FROM glpi_computers c, glpi_networkports n
                              WHERE c.id=$id AND c.id=n.items_id AND itemtype='Computer'
                                AND n.instantiation_type='NetworkPortEthernet' AND n.mac<>'' 
                                AND c.is_deleted=FALSE AND n.is_deleted=false");
      unset($values);
      while ($line = $DB->fetch_array($macs)) {
         $mac = $line['mac'];
         $query = "DELETE FROM dbo.ComputerIdentity WHERE MacAddress<>'' AND MacAddress='$mac'";
         mssql_query($query, $link) or die(mssql_get_last_message()."<br><br>".$query);
         $values = $values."('$name', '$uuid', '$serial', '$assettag', '$mac'), ";
      }
      // TODO: clean up related tables where orphan records may now reside
      if (isset($values)) {
         $values = substr($values, 0, -2);
      }
      $query = "INSERT INTO $dbschema.dbo.ComputerIdentity (Description, UUID, SerialNumber, AssetTag, MacAddress) VALUES $values";
      mssql_query("$query") or die(mssql_get_last_message()."<br><br>".$query);

      // Retreive newly created entries ids in order to add the settings. Name is enough as we cleaned bogus entries in the previous phase
      $query = "SELECT ID FROM dbo.ComputerIdentity WHERE Description='$name'";
      $result = mssql_query($query, $link) or die(mssql_get_last_message()."<br><br>".$query);
      $ids = "(";
      $values = '';
      $adminpasscomposite = $localadmin.'-'.$name;
      while ($row = mssql_fetch_array($result)) {
         $mdtid = $row['ID'];
         $ids = $ids.$mdtid.", ";
         $values = $values."('C', $mdtid, '$name', '$name', '$name', '$adminpasscomposite'), ";
      }
      $ids = substr($ids, 0, -2).") ";
      $values = substr($values, 0, -2);
      $query = "INSERT INTO dbo.Settings (Type, ID, ComputerName, OSDComputerName, FullName, AdminPassword) VALUES $values";
      mssql_query($query, $link) or die(mssql_get_last_message()."<br><br>".$query);

      // Update settings with additional parameters
      $result = $DB->query("SELECT `key`, `value` FROM glpi_plugin_glpi2mdt_settings WHERE id=$id AND type='C';");
      while ($pair = $DB->fetch_array($result)) {
         $key = $pair['key'];
         $value = $pair['value'];
         $query = "UPDATE dbo.Settings SET $key='$value' WHERE ID IN $ids;";
         mssql_query("$query") or die (mssql_get_last_message()."<br><br>".$query);
      }
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
         if ($key == 'OSInstall' and ($value == 'YES' or $value == 'NO')) {
            $osinstall = $value;
         } else {
            $osinstall = ''; }
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

         ?>
           <form action="../plugins/glpi2mdt/front/computer.form.php" method="post">
            <?php echo Html::hidden('id', array('value' => $id)); ?>
            <?php echo Html::hidden('_glpi_csrf_token', array('value' => Session::getNewCSRFToken())); ?>
            <div class="spaced" id="tabsbody">
                <table class="tab_cadre_fixe">
                    <tr class="tab_bg_1">
                        <?php
                          echo "<td>";
                          echo _e('Enable automatic installation', 'glpi2mdt');
                          echo "</td><td>";
                          $yesno['YES'] = __('YES', 'glpi2mdt');
                          $yesno['NO'] = __('NO', 'glpi2mdt'); 
                          Dropdown::showFromArray("OSInstall", $yesno,
                             array(
                             'value' => "$osinstall")
                          );
                          echo "</td>";
                        ?>
                          </tr>
                    </tr>
                    <tr class="tab_bg_1">
                        <?php
                          echo '<td>';
                          echo _e("Task sequence",'glpi2mdt');
                          echo ': &nbsp;&nbsp;&nbsp;</td>';
                          echo "<td>";
                          $result = $DB->query("SELECT id, name FROM glpi_plugin_glpi2mdt_task_sequences 
                                                  WHERE is_deleted=false AND hide=false AND enable=true");
                        while ($row = $DB->fetch_array($result)) {
                           $tasksequenceids[$row['id']]=$row['name'];
                        }
                          Dropdown::showFromArray("TaskSequenceID", $tasksequenceids,
                             array('value' => "$tasksequence"));
                          echo "</td>";
                        ?>
                    </tr>
                    <tr class="tab_bg_1">
                        <?php
                          echo '<td>';
                          echo _e('Application', 'glpi2mdt');
                          echo ': &nbsp;&nbsp;&nbsp;</td>';
                          echo "<td>";
                          $result = $DB->query("SELECT guid, shortname FROM glpi_plugin_glpi2mdt_applications 
                                                  WHERE is_deleted=false AND hide=false AND enable=true");
                        while ($row = $DB->fetch_array($result)) {
                           $allapplications[$row['guid']]=$row['shortname'];
                        }
                          Dropdown::showFromArray("applications", $allapplications,
                             array('value' => "$applications"));
                          echo "</td>";
                        ?>                        
                    </tr>
                    <tr class="tab_bg_1">
                        <td>
                            Roles: &nbsp;&nbsp;&nbsp;
                        </td><td>
                            <input type="text" name="<?php _e('roles', 'glpi2mdt') ?>"  <?php echo 'value="'.$roles.'"' ?> size="40" class="ui-autocomplete-input" autocomplete="off"> &nbsp;&nbsp;&nbsp;
                        </td>
                    </tr>
                    <tr class="tab_bg_1">
                           <td></td><td>
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


