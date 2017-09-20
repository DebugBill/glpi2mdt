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

class PluginGlpi2mdtComputer extends PluginGlpi2mdtMdt {

   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = 'computer';

   /**
   * This function is called from GLPI to allow the plugin to insert one or more items
   *  inside the left menu of a Itemtype.
   */
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $DB;
      $id = $item->getID();
      $result = $DB->query("SELECT value FROM glpi_plugin_glpi2mdt_settings 
                              WHERE type='C' AND category='C' AND `key`='OSInstall' AND id=$id");
      $row = $DB->fetch_array($result);
      if ($row['value'] == "YES") {
         return self::createTabEntry(__('Auto Install', 'glpi2mdt'), __('YES'));
      } else {
         return self::createTabEntry(__('Auto Install', 'glpi2mdt'), __('NO'));
      }
   }


   /**
   * This function is called by the computer form for glpi2mdt when pressing "save"
   * It parses the post variables and stores them in the GLPI database
   *
   * @param  $post, the full list of post items
   * @return nothing
   */
   function updateValue($post) {
      global $DB;

      // Only update if user has rights to do so.
      if (!PluginGlpi2mdtComputer::canUpdate()) {
         return false;
      }

      // Build array of valid parameters for post variables
      $result = $DB->query("SELECT column_name FROM glpi_plugin_glpi2mdt_descriptions WHERE is_deleted=false");
      while ($line = $DB->fetch_array($result)) {
         $parameters[$line['column_name']] = true;
      }

      if (isset($post['id']) and ($post['id'] > 0)) {
         $id = $post['id'];
         $apprank=0;
         // Delete all computer configuration entries
         $DB->queryOrDie("DELETE FROM glpi_plugin_glpi2mdt_settings WHERE id=$id and type='C'");
         foreach ($post as $key=>$value) {
            // only valid parameters may be inserted. The full list is in the "descriptions" database
            if (isset($parameters[$key])) {
               $query = "INSERT INTO glpi_plugin_glpi2mdt_settings 
                             (`id`, `category`, `type`, `key`, `value`)
                             VALUES ($id, 'C','C', '$key', '$value')
                             ON DUPLICATE KEY UPDATE value='$value'";
               $DB->queryOrDie($query, "Cannot update settings database");
            }
            // Applications
            if ((substr($key, 0, 4) == 'App-') and ($value <> 'none') and (strlen($key) == 42) and ($value == 'on')) {
               $guid = substr($key, 4, strlen($key)-4);
               $apprank += 1;
               $query = "INSERT INTO glpi_plugin_glpi2mdt_settings 
                             (`id`, `category`, `type`, `key`, `value`)
                             VALUES ($id, 'A','C', '$guid', '$apprank')
                             ON DUPLICATE KEY UPDATE value='$apprank'";
               $DB->queryOrDie($query, "Cannot update application settings database");
            }
            // Roles
            if ((substr($key, 0, 6) == 'Roles-') and ($value <> 'none') and ($value == 'on')) {
               $guid = substr($key, 6, strlen($key)-6);
               $rolerank += 1;
               $query = "INSERT INTO glpi_plugin_glpi2mdt_settings 
                             (`id`, `category`, `type`, `key`, `value`)
                             VALUES ($id, 'R','C', '$guid', '$rolerank')
                             ON DUPLICATE KEY UPDATE value='$rolerank'";
               $DB->queryOrDie($query, "Cannot update roles settings database");
            }
            if (($key == 'OSInstallExpire')) {
               $timestamp = strtotime($value);
               if ($timestamp > 0) {
                  $query = "INSERT INTO glpi_plugin_glpi2mdt_settings 
                             (`id`, `category`, `type`, `key`, `value`)
                             VALUES ($id, 'C','C', '$key', '$timestamp')
                             ON DUPLICATE KEY UPDATE value='$timestamp'";
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
   * @param  Expire: will only reset "OSInstall" flag set to true and coupling mode is not "strict master slave"
   * @return nothing
   */
   function updateMDT($id) {
      global $DB;

      // Only update if user has rights to do so.
      if (!PluginGlpi2mdtComputer::canUpdate()) {
         return false;
      }

      // Build array of valid parameters
      $result = $DB->query("SELECT column_name FROM glpi_plugin_glpi2mdt_descriptions WHERE is_deleted=false");
      while ($line = $DB->fetch_array($result)) {
         $parameters[$line['column_name']] = true;
      }

      // Build password according to rules
      if ($globalconfig['Complexity'] == 'Trivial') {
         $adminpasscomposite = $name;
      } else if ($globalconfig['Complexity'] == 'Unique') {
         $adminpasscomposite = $globalconfig['LocalAdmin'].'-'.$name;
      } else { // Default case, Basic
         $adminpasscomposite = $globalconfig['LocalAdmin'];
      }

      $globalconfig = $this->globalconfig;

      // Get data for current computer
      $result = $DB->query("SELECT name, uuid, serial, otherserial FROM glpi_computers WHERE id=$id AND is_deleted=false");
      $common = $DB->fetch_array($result);

      // Build list of IDs of existing records in MDT bearing same name, uuid, serial or mac adresses
      // as the computer being updated (this might clean up other bogus entries and remove duplicate names
      $uuid = $common['uuid'];
      $name = $common['name'];
      $serial = $common['serial'];
      $assettag = $common['otherserial'];

      // Build list of mac addresses to search for
      $result = $DB->queryOrDie("SELECT UPPER(n.mac) as mac
                              FROM glpi_computers c, glpi_networkports n
                              WHERE c.id=$id AND c.id=n.items_id AND itemtype='Computer'
                                AND n.instantiation_type='NetworkPortEthernet' AND n.mac<>'' 
                                AND c.is_deleted=FALSE AND n.is_deleted=false");
      $macs="MacAddress IN (";
      unset($values);
      $nblines = 0;
      while ($line = $DB->fetch_array($result)) {
         $mac = $line['mac'];
         $macs=$macs."'".$mac."', ";
         $values = $values."('$name', '$uuid', '$serial', '$assettag', '$mac'), ";
         $nblines +=1;
      }
      // There should be one line per mac address in MDT, and at least one if no mac is provided.
      if ($nblines == 0) {
         $nblines = 1;
      }
      $macs = substr($macs, 0, -2).") ";
      $values =  substr($values, 0, -2)." ";
      if ($macs ==  "MacAddress IN ()") {
         $macs='false';
         $values= "('$name', '$uuid', '$serial', '$assettag', '')";
      }
      // Get list of ids
      $query = "SELECT ID FROM dbo.ComputerIdentity 
                  WHERE (UUID<>'' AND UUID='$uuid')
                     OR (Description<>'' AND Description='$name')
                     OR (SerialNumber<>'' AND SerialNumber='$serial') 
                     OR $macs";
      $result = $this->queryOrDie("$query", "Can't read IDs to delete");

      // build a list of MDT IDs corresponding to the computer in GLPI (there can be several
      // because of multiple mac addresses or duplicate names, serials....
      $ids = "ID IN (";
      while ($line = $this->fetch_array($result)) {
         $ids=$ids."'".$line['ID']."', ";
      }
      $ids = substr($ids, 0, -2).")";
      if ($ids ==  "ID IN)") {
         $ids="ID = ''";
      }
      // Check if the computer entries in MDT are the ones expected by GLPI.
      // If not, delete everything and recreate
      // If yes, depending on coupling mode, delete and recreate or simply update
      $query = ("SELECT ID FROM dbo.ComputerIdentity 
                  WHERE (UUID='$uuid') AND Description='$name' AND SerialNumber='$serial' AND $macs");
      $result = $this->query($query);
      $nblinesactual = $this->numrows($result);
      if ($nblines <> $nblinesactual) {
         $this->queryOrDie("DELETE FROM dbo.ComputerIdentity WHERE $ids");
         $this->queryOrDie("INSERT INTO dbo.ComputerIdentity (Description, UUID, SerialNumber, AssetTag, MacAddress) VALUES $values");
      }
      // Delete corresponding records in side tables depending on coupling mode
      if ($nblines <> $nblinesactual OR $globalconfig['Mode'] == 'Strict') {
         $this->queryOrDie("DELETE FROM dbo.Settings WHERE Type='C' and $ids");
         $this->queryOrDie("DELETE FROM dbo.Settings_Applications WHERE Type='C' and $ids");
         $this->queryOrDie("DELETE FROM dbo.Settings_Administrators WHERE Type='C' and $ids");
         $this->queryOrDie("DELETE FROM dbo.Settings_Packages WHERE Type='C' and $ids");
         $this->queryOrDie("DELETE FROM dbo.Settings_Roles WHERE Type='C' and $ids");
      }
      // Retreive (newly created or not) entries ids in order to add the settings.
      //Name is enough as we cleaned bogus entries in the previous phase
      $query = "SELECT ID FROM dbo.ComputerIdentity WHERE Description='$name'";
      $result = $this->queryOrDie($query);
      $ids = "(";

      while ($row = $this->fetch_array($result)) {
         $mdtid = $row['ID'];
         $ids = $ids.$row['ID'].", ";
         $arrayid[$row['ID']] = $row['ID'];
         $values = "('C', $mdtid, '$name', '$name', '$name', '$adminpasscomposite') ";
         // Check if settings line does exist already. Insert if not, update if yes
         // (because "on duplicate" does not exist in MS-SQL 
         $exists = $this->queryOrDie("SELECT ID FROM dbo.Settings WHERE Type='C' AND ID=$mdtid;");
         if ($this->numrows($exists) == 1) {
            $query = "UPDATE dbo.Settings SET ComputerName='$name', OSDComputerName='$name', FullName='$name', AdminPassword='$adminpasscomposite'
                         WHERE Type='C' and ID=$mdtid";
         } else {
            $query = "INSERT INTO dbo.Settings (Type, ID, ComputerName, OSDComputerName, FullName, AdminPassword) VALUES $values;";
         }
         $this->queryOrDie($query);
      }
      $ids = substr($ids, 0, -2).") ";

      // Update settings with additional parameters
      $query = "SELECT `key`, `value` FROM glpi_plugin_glpi2mdt_settings WHERE id=$id AND category='C' AND type='C';";
      $result = $DB->queryOrDie($query, "Cannot select additional parameters.<br>");
      while ($pair = $DB->fetch_array($result)) {
         $key = $pair['key'];
         $value = $pair['value'];
         $query = "UPDATE dbo.Settings SET $key='$value' WHERE ID IN $ids;";
         // Check if key is a valid field for database "settings"
         if ($parameters[$key]) {
            $this->queryOrDie("$query");
         }
      }
      // Update applications table
      $query = "SELECT `key`, `value` FROM glpi_plugin_glpi2mdt_settings WHERE id=$id AND category='A' AND type='C';";
      $result = $DB->queryOrDie($query, "Cannot select additional applications.");
      while ($pair = $DB->fetch_array($result)) {
         $key = $pair['key'];
         $value = $pair['value'];
         foreach ($arrayid as $mdtid) {
            // GLPI2MDT does not manage ranks, so keep the existing one if any
            $ranks = $this->queryOrDie("SELECT Sequence FROM dbo.Settings_Applications WHERE ID=$mdtid AND type='C' AND Applications='$key';");
            if ($this->numrows($ranks) == 0) {
               $this->queryOrDie("INSERT INTO dbo.Settings_Applications (Type, ID, Sequence, Applications)
                      VALUES ('C', '$mdtid', $value, '$key');");
            }
         }
      }
      // Update Roles table
      $query = "SELECT `key`, `value` FROM glpi_plugin_glpi2mdt_settings WHERE id=$id AND category='R' AND type='C';";
      $result = $DB->queryOrDie($query, "Cannot select additional roles.");
      while ($pair = $DB->fetch_array($result)) {
         $key = $pair['key'];
         $value = $pair['value'];
         foreach ($arrayid as $mdtid) {
            // GLPI2MDT does not manage ranks, so keep the existing one if any
            $ranks = $this->queryOrDie("SELECT Sequence FROM dbo.Settings_Roles WHERE ID=$mdtid AND type='C' AND Role='$key';");
            if ($this->numrows($ranks) == 0) {
               $this->queryOrDie("INSERT INTO dbo.Settings_Roles (Type, ID, Sequence, Role)
                      VALUES ('C', '$mdtid', $value, '$key');");
            }
         }
      }
   }

   /**
     * This function is called from GLPI to render the form when the user clicks
     *  on the menu item generated from getTabNameForItem()
   */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $DB;

      $id = $item->getID();
      $osinstall = 'NO';
      $osinstallexpire = date('Y-m-d H:i', 300*ceil(time()/300) + (3600*24));

      $query = "SELECT `category`, `key`, `value` FROM glpi_plugin_glpi2mdt_settings WHERE type='C' AND id='$id'";
      $result = $DB->query($query);
      while ($row=$DB->fetch_array($result)) {
         if ($row['category'] == 'C') {
            $settings[$row['key']] = $row['value'];
         }
         if ($row['category'] == 'A') {
            $appvalues[$row['key']] = true;
         }
         if ($row['category'] == 'R') {
            $rolevalues[$row['key']] = true;
         }
      }

      if (isset($settings['OSInstall'])) {
         $osinstall = $settings['OSInstall'];
      }
      if (isset($settings['TaskSequenceID'])) {
         $tasksequence = $settings['TaskSequenceID'];
      }
      if (isset($settings['OSInstallExpire'])) {
         $osinstallexpire = date('Y-m-d H:i', $settings['OSInstallExpire']);
      }

      echo '<form action="../plugins/glpi2mdt/front/computer.form.php" method="post">';
      echo Html::hidden('id', array('value' => $id));
      echo Html::hidden('_glpi_csrf_token', array('value' => Session::getNewCSRFToken()));
      echo '<div class="spaced" id="tabsbody">';
      echo '<table class="tab_cadre_fixe" width="100%">';
      echo '<tr class="headerRow"><th colspan="3">'.__('Automatic installation', 'glpi2mdt').'<br></th></tr>';
      // Enable OS install
      echo '          <tr class="tab_bg_1">';
      echo "<td>";
      echo __('Enable automatic installation', 'glpi2mdt');
      echo "</td><td>";
      $yesno['YES'] = __('YES', 'glpi2mdt');
      $yesno['NO'] = __('NO', 'glpi2mdt');
      Dropdown::showFromArray("OSInstall", $yesno,
        array(
       'value' => "$osinstall")
       );
      echo '</td>';

      // Reset after...
      echo '<td>';
      echo __('Reset after (empty for permanent):', 'glpi2mdt');
      Html::showDateTimeField("OSInstallExpire", array('value'      => $osinstallexpire,
       'timestep'   => 5,
       'mindate'    => date('Y-m-d H:i:s'),
       'maybeempty' => true));
      echo '</td></tr>';

      // Task sequences
      echo '<tr class="tab_bg_1">';
      echo '<td>';
      echo __("Task sequence", 'glpi2mdt');
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
      echo '</tr>';

      // Applications

      $query = "SELECT a.guid, a.shortname, g.name, a.enable
                   FROM glpi_plugin_glpi2mdt_applications a, 
                        glpi_plugin_glpi2mdt_application_groups g, 
                        glpi_plugin_glpi2mdt_application_group_links l
                   WHERE g.guid = l.group_guid AND l.application_guid=a.guid 
                    AND a.is_deleted=false AND a.hide=false 
                    AND g.is_deleted=false AND g.hide=false AND g.enable=true
                    AND l.is_deleted=false";
      $result = $DB->query($query);
      while ($row = $DB->fetch_assoc($result)) {
         $groupapplications[$row['guid']]['name']=$row['shortname'];
         $groupapplications[$row['guid']]['group']=$row['name'];
         $groupapplications[$row['guid']]['enable']=$row['enable'];
      }
      PluginGlpi2mdtToolbox::showMultiSelect($groupapplications, $appvalues, __('Applications', 'glpi2mdt'), "App-");

      // Roles

      $query = "SELECT id as id, role FROM glpi_plugin_glpi2mdt_roles 
                          WHERE is_deleted=false";
      $result = $DB->query($query);
      while ($row = $DB->fetch_assoc($result)) {
         $roles[$row['id']]['name']=$row['role'];
         $roles[$row['id']]['group']='';
         $roles[$row['id']]['enable']=true;
      }
      PluginGlpi2mdtToolbox::showMultiSelect($roles, $rolevalues, __('Roles', 'glpi2mdt'), "Roles-");

      // Show the save button only if user has rights to do so.
      if (PluginGlpi2mdtComputer::canUpdate()) {
         echo '<tr class="tab_bg_1">';
         echo '<td></td><td>';
         echo '<input type="submit" class="submit" value="Save" name="SAVE"/>';
         echo '</td>';
         echo '</tr>';
         echo '</tr>';
         echo '</table>';
         echo '</div>';
         echo '</form>';
      }
      return true;
   }
}


