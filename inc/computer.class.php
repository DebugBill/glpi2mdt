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
      return self::createTabEntry(__('Auto Install'), 'glpi2mdt');
   }

   /**
   * Update GLPI database
   */
   function updateValue($post) {
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
               $DB->queryOrDie($query, "Cannot update settings database");
            }
            if (($key == 'Applications') and ($value <> 'none')) {
               $query = "INSERT INTO glpi_dev.glpi_plugin_glpi2mdt_settings 
                             (`id`, `category`, `type`, `key`, `value`)
                             VALUES ($id, 'A','C', '$key', '$value')
                             ON DUPLICATE KEY UPDATE value='$value'";
               $DB->queryOrDie($query, "Cannot update settings database");
            }
            if (($key == 'Roles') and ($value <> 'none')) {
               $query = "INSERT INTO glpi_dev.glpi_plugin_glpi2mdt_settings 
                             (`id`, `category`, `type`, `key`, `value`)
                             VALUES ($id, 'R','C', '$key', '$value')
                             ON DUPLICATE KEY UPDATE value='$value'";
               $DB->queryOrDie($query, "Cannot update settings database");
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

      // Only update if user has rights to do so.
      if (!PluginGlpi2mdtComputer::canUpdate()) {
         return false;
      }

      // Build array of valid parameters
      $result = $DB->query("SELECT column_name FROM glpi_plugin_glpi2mdt_descriptions WHERE is_deleted=false");
      while ($line = $DB->fetch_array($result)) {
         $parameters[$line['column_name']] = true;
      }

      $globalconfig = $this->globalconfig;
      $dbschema =  $globalconfig['DBSchema'];

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
      $result = $DB->queryOrDie("SELECT UPPER(n.mac) as mac
                              FROM glpi_computers c, glpi_networkports n
                              WHERE c.id=$id AND c.id=n.items_id AND itemtype='Computer'
                                AND n.instantiation_type='NetworkPortEthernet' AND n.mac<>'' 
                                AND c.is_deleted=FALSE AND n.is_deleted=false");
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
      $result = $this->queryOrDie("$query", "Can't read IDs to delete");

      // build a list of IDs
      $ids = "ID IN (";
      while ($line = $this->fetch_array($result)) {
         $ids=$ids."'".$line['ID']."', ";
      }
      $ids = substr($ids, 0, -2).")";
      if ($ids ==  "ID IN)") {
         $ids="ID = ''";
      }
      $query = "DELETE FROM $dbschema.dbo.ComputerIdentity WHERE $ids";
      $this->queryOrDie($query);

      // Delete corresponding records in side tables
      $this->queryOrDie("DELETE FROM $dbschema.dbo.Settings WHERE Type='C' and $ids");
      $this->queryOrDie("DELETE FROM $dbschema.dbo.Settings_Applications WHERE Type='C' and $ids");
      $this->queryOrDie("DELETE FROM $dbschema.dbo.Settings_Administrators WHERE Type='C' and $ids");
      $this->queryOrDie("DELETE FROM $dbschema.dbo.Settings_Packages WHERE Type='C' and $ids");
      $this->queryOrDie("DELETE FROM $dbschema.dbo.Settings_Roles WHERE Type='C' and $ids");

      $query = "INSERT INTO $dbschema.dbo.ComputerIdentity (Description, UUID, SerialNumber, AssetTag, MacAddress) VALUES $values";
      $this->queryOrDie("$query");

      // Retreive newly created entries ids in order to add the settings.
      //Name is enough as we cleaned bogus entries in the previous phase
      $query = "SELECT ID FROM dbo.ComputerIdentity WHERE Description='$name'";
      $result = $this->queryOrDie($query);
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
      $this->queryOrDie($query);

      // Update settings with additional parameters
      $query = "SELECT `key`, `value` FROM glpi_plugin_glpi2mdt_settings WHERE id=$id AND category='C' AND type='C';";
      $result = $DB->query($query)
           or die("Cannot select additional parameters.<br>". $query."<br><br>".$DB->error());
      while ($pair = $DB->fetch_array($result)) {
         $key = $pair['key'];
         $value = $pair['value'];
         $query = "UPDATE dbo.Settings SET $key='$value' WHERE ID IN $ids;";
         if ($parameter[$key]) {
            $this->queryOrDie("$query");
         }
      }

      // Update applications table
      $query = "SELECT `key`, `value` FROM glpi_plugin_glpi2mdt_settings WHERE id=$id AND category='A' AND type='C';";
      $result = $DB->query($query)
            or die("Cannot select additional applications.<br>". $query."<br><br>".$DB->error());
      $query = "DELETE FROM $dbschema.dbo.Settings_Applications WHERE type='C' AND ID='$id'";
      $this->queryOrDie("$query");
      $seq = 0;
      while ($pair = $DB->fetch_array($result)) {
         $seq += 1;
         $key = $pair['key'];
         $value = $pair['value'];
         foreach ($arrayid as $mdtid) {
            $query = "INSERT INTO $dbschema.dbo.Settings_Applications (Type, ID, Sequence, Applications)
                   VALUES ('C', '$mdtid', $seq, '$value');";
            $this->queryOrDie("$query");
         }
      }
   }

   /**
     * This function is called from GLPI to render the form when the user click
     *  on the menu item generated from getTabNameForItem()
   */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $DB;

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

      echo '<form action="../plugins/glpi2mdt/front/computer.form.php" method="post">';
      echo Html::hidden('id', array('value' => $id));
      echo Html::hidden('_glpi_csrf_token', array('value' => Session::getNewCSRFToken()));
      echo '<div class="spaced" id="tabsbody">';
      echo '       <table class="tab_cadre_fixe">';

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
      echo '<tr class="tab_bg_1">';
      echo '<td>';
      echo __('Application', 'glpi2mdt');
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
      echo '</tr>';


      // Roles
      echo '<tr class="tab_bg_1">';
      echo '<td>';
      echo __('Roles', 'glpi2mdt');
      echo ': &nbsp;&nbsp;&nbsp;</td>';
      echo "<td>";
      $allroles['none'] = __('None', 'glpi2mdt');
      $result = $DB->query("SELECT id, role FROM glpi_plugin_glpi2mdt_roles 
                          WHERE is_deleted=false");
      while ($row = $DB->fetch_array($result)) {
         $allroles[$row['id']]=$row['role'];
      }
      Dropdown::showFromArray("Roles", $allroles,
      array('value' => "$roles"));
      echo "</td>";
      echo '</tr>';

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


