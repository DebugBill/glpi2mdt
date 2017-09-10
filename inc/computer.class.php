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

class PluginGlpi2mdtComputer extends CommonGLPI {
   /**
   * This function is called from GLPI to allow the plugin to insert one or more items
   *  inside the left menu of a Itemtype.
   */
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      return self::createTabEntry(__('Auto Install'), 'glpi2mdt');
   }

   /**
   * Update GLPI database
   */
   function updateValue($post) {
      global $DB;

      // Build array of valid parameters
      $result = $DB->query("SELECT column_name FROM glpi_plugin_glpi2mdt_descriptions WHERE is_deleted=false");
      while ($line = $DB->fetch_array($result)) {
         $parameters[$line['column_name']] = true;
      }

      if (isset($post['id']) and ($post['id'] > 0)) {
         $id = $post['id'];
         $DB->query("DELETE FROM glpi_plugin_glpi2mdt_settings WHERE id=$id and type='C'");
         foreach ($post as $key=>$value) {
            // only valid parameters may be inserted. The full list is in the "descriptions" database
            if (isset($parameters[$key])) {
               $query = "INSERT INTO glpi_dev.glpi_plugin_glpi2mdt_settings 
                             (`id`, `category`, `type`, `key`, `value`)
                             VALUES ($id, 'C','C', '$key', '$value')
                             ON DUPLICATE KEY UPDATE value='$value'";
               $DB->query($query) or die("Cannot update settings databse, query is $query");
            }
            if (($key == 'Applications') and ($value <> 'none')) {
               $query = "INSERT INTO glpi_dev.glpi_plugin_glpi2mdt_settings 
                             (`id`, `category`, `type`, `key`, `value`)
                             VALUES ($id, 'A','C', '$key', '$value')
                             ON DUPLICATE KEY UPDATE value='$value'";
               $DB->query($query) or die("Cannot update settings databse, query is $query");
            }
            if (($key == 'OSInstallExpire')) {
               $timestamp = strtotime($value);
               if ($timestamp > 0) {
                  $query = "INSERT INTO glpi_dev.glpi_plugin_glpi2mdt_settings 
                             (`id`, `category`, `type`, `key`, `value`)
                             VALUES ($id, 'C','C', '$key', '$timestamp')
                             ON DUPLICATE KEY UPDATE value='$timestamp'";
               } else {
                  $query = "DELETE FROM glpi_dev.glpi_plugin_glpi2mdt_settings WHERE id=$id AND category='C' AND type='C' AND key='$key';";
               }
               $DB->query($query);
            }
         }
      }
   }

   /**
   * Updates the MDT MSSQL database with information contained in GLPI's database
   *
   * @param  GLPI object ID, here a computer
   * @param  Expire: will only reset "OSInstall flag set to true and coupling mode is not "strict master slave"
   * @return string type for the cron list page
   */
   function updateMDT($id) {
      global $DB;

      // Build array of valid parameters
      $result = $DB->query("SELECT column_name FROM glpi_plugin_glpi2mdt_descriptions WHERE is_deleted=false");
      while ($line = $DB->fetch_array($result)) {
         $parameters[$line['column_name']] = true;
      }

      // Get login parameters from database and connect to MSQSL server
      $glpi2mdtconfig = new PluginGlpi2mdtConfig;
      $glpi2mdtconfig->loadConf();
      $globalconfig = $glpi2mdtconfig->globalconfig;
      $dbschema =  $globalconfig['DBSchema'];

      $link = mssql_connect($globalconfig['DBServer'], $globalconfig['DBLogin'], $globalconfig['DBPassword'])
      or die("<h1><font color='red'>Database login KO!</font></h1><br>");
      mssql_select_db($globalconfig['DBSchema'], $link)
      or die("<h1><font color='red'>Cannot switch to schema $dbschema on MSSQL server</font></h1><br>");

      // Get data for current computer
      $result = $DB->query("SELECT name, uuid, serial, otherserial FROM glpi_computers WHERE id=$id AND is_deleted=false");
      $common = $DB->fetch_array($result);

      // Build list of IDs of existing records in MDT bearing same name, uuid, serial or mac adresses
      //  as the computer being updated (this might clean up other bogus entries and remove duplicate names
      $uuid = $common['uuid'];
      $name = $common['name'];
      $serial = $common['serial'];
      $assettag = $common['otherserial'];

      // Build list of mac addresses to search for
      $result = $DB->query("SELECT UPPER(n.mac) as mac
                              FROM glpi_computers c, glpi_networkports n
                              WHERE c.id=$id AND c.id=n.items_id AND itemtype='Computer'
                                AND n.instantiation_type='NetworkPortEthernet' AND n.mac<>'' 
                                AND c.is_deleted=FALSE AND n.is_deleted=false")
                  or die(mssql_get_last_message());
      $macs="MacAddress IN (";
      unset($values);
      while ($line = $DB->fetch_array($result)) {
         $mac = $line['mac'];
         $macs=$macs."'".$mac."', ";
         $values = $values."('$name', '$uuid', '$serial', '$assettag', '$mac'), ";
      }
      $macs = substr($macs, 0, -2).") ";
      $values =  substr($values, 0, -2)." ";
      if ($macs ==  "MacAddress IN ()") {
         $macs='false';
         $values= "('$name', '$uuid', '$serial', '$assettag', '')";
      }
      // Get list of ids
      $query = "SELECT ID FROM $dbschema.dbo.ComputerIdentity 
                  WHERE (UUID<>'' AND UUID='$uuid')
                     OR (Description<>'' AND Description='$name')
                     OR (SerialNumber<>'' AND SerialNumber='$serial') 
                     OR $macs";
      $result = mssql_query("$query", $link) or die("Can't read IDs to delete<br><br>".$query."<br><br>".mssql_get_last_message());

      // build a list of IDs
      $ids = "ID IN (";
      while ($line = mssql_fetch_array($result)) {
         $ids=$ids."'".$line['ID']."', ";
      }
      $ids = substr($ids, 0, -2).")";
      if ($ids ==  "ID IN)") {
         $ids="ID = ''";
      }
      $query = "DELETE FROM $dbschema.dbo.ComputerIdentity WHERE $ids";
      mssql_query($query, $link) or die(mssql_get_last_message()."<br><br>".$query);

      // Delete corresponding records in side tables
      mssql_query("DELETE FROM $dbschema.dbo.Settings WHERE Type='C' and $ids", $link) or die(mssql_get_last_message()."<br><br>");
      mssql_query("DELETE FROM $dbschema.dbo.Settings_Applications WHERE Type='C' and $ids", $link) or die(mssql_get_last_message()."<br><br>");
      mssql_query("DELETE FROM $dbschema.dbo.Settings_Administrators WHERE Type='C' and $ids", $link) or die(mssql_get_last_message()."<br><br>");
      mssql_query("DELETE FROM $dbschema.dbo.Settings_Packages WHERE Type='C' and $ids", $link) or die(mssql_get_last_message()."<br><br>");
      mssql_query("DELETE FROM $dbschema.dbo.Settings_Roles WHERE Type='C' and $ids", $link) or die(mssql_get_last_message()."<br><br>");

      $query = "INSERT INTO $dbschema.dbo.ComputerIdentity (Description, UUID, SerialNumber, AssetTag, MacAddress) VALUES $values";
      mssql_query("$query") or die(mssql_get_last_message()."<br><br>".$query);

      // Retreive newly created entries ids in order to add the settings. Name is enough as we cleaned bogus entries in the previous phase
      $query = "SELECT ID FROM dbo.ComputerIdentity WHERE Description='$name'";
      $result = mssql_query($query, $link) or die(mssql_get_last_message()."<br><br>".$query);
      $ids = "(";
      $values = '';

      while ($row = mssql_fetch_array($result)) {
         $mdtid = $row['ID'];
         $ids = $ids.$mdtid.", ";
         $values = $values."('C', $mdtid, '$name', '$name', '$name', '$adminpasscomposite'), ";
         $arrayid[$row['ID']] = $row['ID'];
      }
      $ids = substr($ids, 0, -2).") ";
      $values = substr($values, 0, -2);

         // Build password according to rules
      if ($globalconfig['Complexity'] == 'Trivial') {
         $adminpasscomposite = $name;
      } else if ($globalconfig['Complexity'] == 'Unique') {
         $adminpasscomposite = $globalconfig['LocalAdmin'].'-'.$name;
      } else { // Default case, Basic
         $adminpasscomposite = $globalconfig['LocalAdmin'];
      }

      $query = "INSERT INTO dbo.Settings (Type, ID, ComputerName, OSDComputerName, FullName, AdminPassword) VALUES $values";
      mssql_query($query, $link) or die(mssql_get_last_message()."<br><br>".$query);

      // Update settings with additional parameters
      $query = "SELECT `key`, `value` FROM glpi_plugin_glpi2mdt_settings WHERE id=$id AND category='C' AND type='C';";
      $result = $DB->query($query)
           or die("Cannot select additional parameters.<br>". $query."<br><br>".$DB->error());
      while ($pair = $DB->fetch_array($result)) {
         $key = $pair['key'];
         $value = $pair['value'];
         $query = "UPDATE dbo.Settings SET $key='$value' WHERE ID IN $ids;";
         if ($parameter[$key]) {
            mssql_query("$query") or die (mssql_get_last_message()."<br><br>".$query);
         }
      }

      // Update applications table
      $query = "SELECT `key`, `value` FROM glpi_plugin_glpi2mdt_settings WHERE id=$id AND category='A' AND type='C';";
      $result = $DB->query($query)
            or die("Cannot select additional applications.<br>". $query."<br><br>".$DB->error());
      $query = "DELETE FROM $dbschema.dbo.Settings_Applications WHERE type='C' AND ID='$id'";
      mssql_query("$query") or die (mssql_get_last_message()."<br><br>".$query);
      $seq = 0;
      while ($pair = $DB->fetch_array($result)) {
         $seq += 1;
         $key = $pair['key'];
         $value = $pair['value'];
         foreach ($arrayid as $mdtid) {
            $query = "INSERT INTO $dbschema.dbo.Settings_Applications (Type, ID, Sequence, Applications)
                   VALUES ('C', '$mdtid', $seq, '$value');";
            mssql_query("$query") or die (mssql_get_last_message()."<br><br>".$query);
         }
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
      $osinstallexpire = date('Y-m-d H:i', 300*ceil(time()/300) + (3600*24));

      $query = "SELECT `key`, `value` FROM glpi_dev.glpi_plugin_glpi2mdt_settings WHERE type='C' AND id='$id'";
      $result = $DB->query($query);
      while ($row=$DB->fetch_array($result)) {
         $settings[$row['key']] = $row['value'];
      }

      if (isset($settings['OSInstall'])) {
         $osinstall = $settings['OSInstall'];
      }
      if (isset($settings['TaskSequenceID'])) {
         $tasksequence = $settings['TaskSequenceID'];
      }
      if (isset($settings['Roles'])) {
         $roles = $settings['Roles'];
      }
      if (isset($settings['Applications'])) {
         $applications = $settings['Applications'];
      }
      if (isset($settings['OSInstallExpire'])) {
         $osinstallexpire = date('Y-m-d H:i', $settings['OSInstallExpire']);
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
                       echo "</td><td>";
                       echo __('Reset after (empty for permanent):', 'glpi2mdt');
                       Html::showDateTimeField("OSInstallExpire", array('value'      => $osinstallexpire,
                                               'timestep'   => 5,
                                               'mindate'    => date('Y-m-d H:i:s'),
                                               'maybeempty' => true));
                        ?>
                          </td>
                          </tr>
                       </tr>
                       <tr class="tab_bg_1">
                        <?php
                        echo '<td>';
                        echo _e("Task sequence", 'glpi2mdt');
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
                        $allapplications['none'] = __('None', 'glpi2mdt');
                        $result = $DB->query("SELECT guid, shortname FROM glpi_plugin_glpi2mdt_applications 
                                                  WHERE is_deleted=false AND hide=false AND enable=true");
                        while ($row = $DB->fetch_array($result)) {
                           $allapplications[$row['guid']]=$row['shortname'];
                        }
                        Dropdown::showFromArray("Applications", $allapplications,
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


