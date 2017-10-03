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
   *
   *  While we're there, if in Master-Master mode, update computer data just in case
   */
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $DB;
      $id = $item->getID();
      $result = $DB->query("SELECT value_char as mode FROM glpi_plugin_glpi2mdt_parameters 
                             WHERE scope='global' AND parameter='Mode';");
      $mode = $DB->fetch_array($result)['mode'];
      if ($mode == 'Master') {
         PluginGlpi2mdtCrontask::cronSyncMasterMaster(null, $id);
      }
      $result = $DB->query("SELECT value FROM glpi_plugin_glpi2mdt_settings 
                              WHERE type='C' AND category='C' AND `key`='OSInstall' AND id=$id");
      if (($DB->numrows($result) == 1) AND ($DB->fetch_array($result)['value'] == 'YES')) {
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

      // Build array of valid variables for post variables
      $variables = $this->globalconfig['variables'];

      if (isset($post['id']) and ($post['id'] > 0)) {
         $id = $post['id'];
         $apprank=0;
         // Delete all computer configuration entries
         $DB->queryOrDie("DELETE FROM glpi_plugin_glpi2mdt_settings WHERE id=$id and type='C'");
         foreach ($post as $key=>$value) {
            // only valid variables may be inserted. The full list is in the "descriptions" database
            if (isset($variables[$key])) {
               $query = "INSERT INTO glpi_plugin_glpi2mdt_settings 
                             (`id`, `category`, `type`, `key`, `value`, `is_in_sync`)
                             VALUES ($id, 'C','C', '$key', '$value', true)
                             ON DUPLICATE KEY UPDATE value='$value', is_in_sync=true";
               $DB->queryOrDie($query, "Cannot update settings database");
            }
            // Applications
            if ((substr($key, 0, 4) == 'App-') and ($value <> 'none') and (strlen($key) == 42) and ($value == 'on')) {
               $guid = substr($key, 4, strlen($key)-4);
               $apprank += 1;
               $query = "INSERT INTO glpi_plugin_glpi2mdt_settings 
                             (`id`, `category`, `type`, `key`, `value`, `is_in_sync`)
                             VALUES ($id, 'A','C', '$guid', '$apprank', true)
                             ON DUPLICATE KEY UPDATE value='$apprank', is_in_sync=true";
               $DB->queryOrDie($query, "Cannot update application settings database");
            }
            // Roles
            if ((substr($key, 0, 6) == 'Roles-') and ($value <> 'none') and ($value == 'on')) {
               $guid = substr($key, 6, strlen($key)-6);
               $roles = $DB->query("SELECT role FROM glpi_plugin_glpi2mdt_roles WHERE id='$guid';");
               $role = $DB->fetch_array($roles)['role'];
               $query = "INSERT INTO glpi_plugin_glpi2mdt_settings 
                             (`id`, `category`, `type`, `key`, `value`, `is_in_sync`)
                             VALUES ($id, 'R','C', '$guid', '$role', true)
                             ON DUPLICATE KEY UPDATE value='$role', is_in_sync=true";
               $DB->queryOrDie($query, "Cannot update roles settings database");
            }
            if (($key == 'OSInstallExpire')) {
               $timestamp = strtotime($value);
               if ($timestamp > 0) {
                  $query = "INSERT INTO glpi_plugin_glpi2mdt_settings 
                             (`id`, `category`, `type`, `key`, `value`, `is_in_sync`)
                             VALUES ($id, 'C','C', '$key', '$timestamp', true)
                             ON DUPLICATE KEY UPDATE value='$timestamp', is_in_sync=true";
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
      $globalconfig = $this->globalconfig;

      // Only update if user has rights to do so.
      if (!PluginGlpi2mdtComputer::canUpdate()) {
         return false;
      }

      // Build array of valid variables
      $variables = $this->globalconfig['variables'];

      //Get IDs to work on
      $mdt = $this->getMdtIds($id);
      $macs = $mdt['macs'];
      $values = $mdt['values'];
      $mdtids = $mdt['mdtids'];
      $name = $mdt['name'];
      $uuid = $mdt['uuid'];
      $serial = $mdt['serial'];
      $otherserial = $mdt['otherserial']; //asset tag
      $nbrows = $mdt['nbrows'];

      // Build password according to rules
      if ($globalconfig['Complexity'] == 'Trivial') {
         $adminpasscomposite = $name;
      } else if ($globalconfig['Complexity'] == 'Unique') {
         $adminpasscomposite = $globalconfig['LocalAdmin'].'-'.$name;
      } else { // Default case, Basic
         $adminpasscomposite = $globalconfig['LocalAdmin'];
      }

      // Check if the computer entries in MDT are the ones expected by GLPI.
      // If not, delete everything and recreate
      // If yes, depending on coupling mode, delete and recreate or simply update
      $query = ("SELECT ID FROM dbo.ComputerIdentity 
                  WHERE UUID='$uuid' AND Description='$name' AND SerialNumber='$serial' AND AssetTag='$otherserial' AND $macs");
      $result = $this->query($query);
      $nbrowsactual = $this->numrows($result);
      if ($nbrows <> $nbrowsactual) {
         $this->queryOrDie("DELETE FROM dbo.ComputerIdentity WHERE $mdtids");
         $this->queryOrDie("INSERT INTO dbo.ComputerIdentity (Description, UUID, SerialNumber, AssetTag, MacAddress) VALUES $values");
      }
      // Delete corresponding records in side tables depending on coupling mode
      if (($nbrows <> $nbrowsactual) OR ($globalconfig['Mode'] == 'Strict')) {
         $this->queryOrDie("DELETE FROM dbo.Settings WHERE Type='C' and $mdtids");
         $this->queryOrDie("DELETE FROM dbo.Settings_Applications WHERE Type='C' and $mdtids");
         $this->queryOrDie("DELETE FROM dbo.Settings_Administrators WHERE Type='C' and $mdtids");
         $this->queryOrDie("DELETE FROM dbo.Settings_Packages WHERE Type='C' and $mdtids");
         $this->queryOrDie("DELETE FROM dbo.Settings_Roles WHERE Type='C' and $mdtids");
      }
      // Retreive (newly created or not) entries ids in order to add the settings.
      $mdt = $this->getMdtIds($id);
      $macs = $mdt['macs'];
      $mdtids = $mdt['mdtids'];
      $arraymdtids = $mdt['arraymdtids'];
      $name = $mdt['name'];
      $uuid = $mdt['uuid'];
      $serial = $mdt['serial'];
      $otherserial = $mdt['otherserial']; //asset tag
      $nbrows = $mdt['nbrows'];

      foreach ($arraymdtids as $mdtid) {
         $values = "('C', $mdtid, '$name', '$name', '$name', '$adminpasscomposite') ";
         // Check if settings line does exist already. Insert if not, update if yes
         // (because "on duplicate" does not exist in MS-SQL)
         $exists = $this->queryOrDie("SELECT ID FROM dbo.Settings WHERE Type='C' AND ID=$mdtid;");
         if ($this->numrows($exists) == 1) {
            $query = "UPDATE dbo.Settings SET ComputerName='$name', OSDComputerName='$name', FullName='$name', AdminPassword='$adminpasscomposite'
                         WHERE Type='C' and ID=$mdtid";
         } else {
            $query = "INSERT INTO dbo.Settings (Type, ID, ComputerName, OSDComputerName, FullName, AdminPassword) VALUES $values;";
         }
         $this->queryOrDie($query);
      }

      // Update settings with additional variables
      $query = "SELECT `key`, `value` FROM glpi_plugin_glpi2mdt_settings WHERE id=$id AND category='C' AND type='C';";
      $result = $DB->queryOrDie($query, "Cannot select additional variables.<br>");
      while ($pair = $DB->fetch_array($result)) {
         $key = $pair['key'];
         $value = $pair['value'];
         if ($value == '*undef*') {
            $value='';
         }
         $query = "UPDATE dbo.Settings SET $key='$value' WHERE $mdtids;";
         // Check if key is a valid field for database "settings"
         if ($variables[$key]) {
            $this->queryOrDie("$query");
         }
      }
      // Update applications table
      $query = "SELECT `key`, `value` FROM glpi_plugin_glpi2mdt_settings WHERE id=$id AND category='A' AND type='C';";
      $result = $DB->queryOrDie($query, "Cannot select additional applications.");
      while ($pair = $DB->fetch_array($result)) {
         $key = $pair['key'];
         $value = $pair['value'];
         reset($arraymdtids);
         foreach ($arraymdtids as $mdtid) {
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
         reset($arraymdtids);
         foreach ($arraymdtids as $mdtid) {
            // GLPI2MDT does not manage ranks, so keep the existing one if any
            $rank = $this->queryOrDie("SELECT Sequence FROM dbo.Settings_Roles WHERE ID=$mdtid AND type='C' AND Role='$value';");
            if ($this->numrows($ranks) == 0) {
               // Add after existing roles in MDT, mainly for loose and master coupling modes
               $next = $this->queryOrDie("SELECT ISNULL(MAX(Sequence),0)+1 as next FROM dbo.Settings_Roles WHERE ID=$mdtid AND type='C';");
               $rank = $this->fetch_array($next)['next'];
               $this->queryOrDie("INSERT INTO dbo.Settings_Roles (Type, ID, Sequence, Role)
                      VALUES ('C', '$mdtid', '$rank', '$value');");
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
      $yesno['*undef*'] = __('Default', 'glpi2mdt');
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
      // first value in array is "default"
      $tasksequenceids['*undef*']=__("Default task sequence", 'glpi2mdt');
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

      $query = "SELECT id, role FROM glpi_plugin_glpi2mdt_roles 
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

         // Plugin version check
         $currentversion = PLUGIN_GLPI2MDT_VERSION;
         $version = $DB->query("SELECT value_char FROM glpi_plugin_glpi2mdt_parameters WHERE parameter='LAtestVersion' AND scope='global'");
         if ($DB-numrows == 1) {
            $latestversion = $DB->fetch_array($version)['value_char'];
         }
         if (version_compare($currentversion, $latestversion, '<')) {
            echo "<div class='center'><font color='red'>";
            echo sprintf(__('A new version of plugin glpi2mdt is available: v%s', 'glpi2mdt'), $latestversion);
            echo "</font></div>";
         }

         echo '</div>';
         echo '</form>';
      }
      return true;
   }
}


