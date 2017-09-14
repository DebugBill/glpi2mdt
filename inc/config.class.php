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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginGlpi2mdtConfig extends PluginGlpi2mdtMdt {

   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = 'config';


   /**
    * Store configuration parameters
    *
    * @param $key                  string: global parameter name to be checked against validkeys array
    *
    * @param $value                string or number: corresponding value for the parameter
    *
    * @return                      nothing but dies if failing to write into the database
   **/
   function updateValue($key, $value) {
      global $DB;
      $validkeys = $this->validkeys;
      if ($validkeys[$key] == 'txt') {
         $query = "INSERT INTO glpi_plugin_glpi2mdt_parameters
                          (`parameter`, `scope`, `value_char`, `is_deleted`)
                          VALUES ('$key', 'global', '$value', false)
                   ON DUPLICATE KEY UPDATE value_char='$value', value_num=NULL, is_deleted=false";
         $DB->queryOrDie($query, "Database error");
      }
      if ($validkeys[$key] == 'num' and ($value > 0 or $value == '')) {
         $query = "INSERT INTO glpi_plugin_glpi2mdt_parameters
                          (`parameter`, `scope`, `value_num`, `is_deleted`)
                          VALUES ('$key', 'global', '$value', false)
                   ON DUPLICATE KEY UPDATE value_num='$value', value_char=NULL, is_deleted=false";
         $DB->queryOrDie($query, "Database error");
      }
   }


   /**
    * Shows form to set plugin configuration parameters
    *
    * @param none
    *
    * @return   outputs HTML form
   **/
   function show() {

      $yesno['YES'] = __('YES', 'glpi2mdt');
      $yesno['NO'] = __('NO', 'glpi2mdt');
         ?>
           <form action="../front/config.form.php" method="post">
            <?php echo Html::hidden('_glpi_csrf_token', array('value' => Session::getNewCSRFToken())); ?>
            <div class="spaced" id="tabsbody">
                <table class="tab_cadre_fixe">
                    <tr class="tab_bg_1">
                        <td>
                              <?php echo _e('Database server name', 'glpi2mdt'); ?> : &nbsp;&nbsp;&nbsp;
                        </td><td>
                              <?php echo '<input type="text" name="DBServer" value="'.$this->globalconfig['DBServer'].'" size="50" class="ui-autocomplete-input" 
                                           autocomplete="off" required pattern="[a-Z0-9.]" placeholder="myMDTserver.mydomain.local"> &nbsp;&nbsp;&nbsp;' ?>
                        </td>
                    </tr>
                    <tr class="tab_bg_1">
                        <td>
                              <?php echo _e('Database server port', 'glpi2mdt'); ?> : &nbsp;&nbsp;&nbsp;
                        </td><td>
                              <?php echo '<input type="number" name="DBPort" value="'.$this->globalconfig['DBPort'].'" size="5" class="ui-autocomplete-input" 
                                           autocomplete="off" inputmode="numeric" placeholder="1433" min="1024" max="65535" required> &nbsp;&nbsp;&nbsp;' ?>
                        </td>
                    </tr>
                    <tr class="tab_bg_1">
                        <td>
                              <?php echo _e('Login', 'glpi2mdt'); ?> : &nbsp;&nbsp;&nbsp;
                        </td><td>
                              <?php echo '<input type="text" name="DBLogin" value="'.$this->globalconfig['DBLogin'].'" size="50" class="ui-autocomplete-input" 
                                           autocomplete="off" required pattern="[a-Z0-9]"> &nbsp;&nbsp;&nbsp;' ?>
                        </td>
                    </tr>
                    <tr class="tab_bg_1">
                        <td>
                              <?php echo _e('Password', 'glpi2mdt'); ?> : &nbsp;&nbsp;&nbsp;
                        </td><td>
                              <?php echo '<input type="password" name="DBPassword" value="'.$this->globalconfig['DBPassword'].'" size="50" class="ui-autocomplete-input" 
                                           autocomplete="off" required> &nbsp;&nbsp;&nbsp;' ?>
                        </td>
                    </tr>
                    <tr class="tab_bg_1">
                        <td>
                              <?php echo _e('Schema', 'glpi2mdt'); ?> : &nbsp;&nbsp;&nbsp;
                        </td><td>
                              <?php echo '<input type="text" name="DBSchema" value="'.$this->globalconfig['DBSchema'].'" size="50" class="ui-autocomplete-input" 
                                           autocomplete="off" required pattern="[a-Z0-9]" placeholder="MDT"> &nbsp;&nbsp;&nbsp;' ?>
                        </td>
                    </tr>
                    <tr class="tab_bg_1">
                        <td>
                              <?php echo _e('Local path to deployment share control directory', 'glpi2mdt'); ?> : &nbsp;&nbsp;&nbsp;
                        </td><td>
                              <?php echo '<input type="text" name="FileShare" value="'.$this->globalconfig['FileShare'].'" size="50" class="ui-autocomplete-input" 
                                          autocomplete="off" required placeholder="/mnt/smb-share/Deployment-share/Control"> &nbsp;&nbsp;&nbsp;' ?>
                        </td>
                    </tr>
                    <tr class="tab_bg_1">
                        <td>
                              <?php echo _e('Local admin password', 'glpi2mdt'); ?> : &nbsp;&nbsp;&nbsp;
                        </td><td>
                              <?php echo '<input type="password" name="LocalAdmin" value="'.$this->globalconfig['LocalAdmin'].'" size="50" class="ui-autocomplete-input" 
                                          autocomplete="off" required> &nbsp;&nbsp;&nbsp;' ?>
                        </td>
                    </tr>
                    <tr class="tab_bg_1">
                          <td>
                        <?php
                          echo _e('Local admin password complexity', 'glpi2mdt');
                          echo "</td><td>";
                          Dropdown::showFromArray("Complexity", array(
                             'Basic' => __('Same password on all machines', 'glpi2mdt'),
                             'Trivial' => __('Password is hostname', 'glpi2mdt'),
                             'Unique' => __('append \'-%hostname%\' to password', 'glpi2mdt')), array(
                             'value' => $this->globalconfig['Complexity'])
                          );
                          echo "</td>";
                           ?>
                          </tr>                    
                    <tr class="tab_bg_1">
                          <td>
                           <?php
                           echo _e('Link mode', 'glpi2mdt');
                           echo "</td><td>";
                           Dropdown::showFromArray("Mode", array(
                             'Strict' => __('Strict Master-Slave', 'glpi2mdt'),
                             'Loose' => __('Loose Master-Slave', 'glpi2mdt'),
                             'Master' => __('Master-Master', 'glpi2mdt')), array(
                             'value' => $this->globalconfig['Mode']));
                           echo "</td>";
                           ?>
                          </tr>
                   <tr class="tab_bg_1">
                          <td>
                        <?php
                          echo _e('Automatically check for new versions', 'glpi2mdt');
                          echo "</td><td>";
                          Dropdown::showFromArray("CheckNewVersion", $yesno,
                             array('value' => $this->globalconfig['CheckNewVersion'])
                          );
                          echo "</td>";
                        ?>
                          </tr>
                   <tr class="tab_bg_1">
                          <td>
                        <?php
                          echo _e('Report usage data (anonymous data to help in designing the plugin)', 'glpi2mdt');
                          echo "</td><td>";
                          Dropdown::showFromArray("ReportUsage", $yesno,
                             array('value' => $this->globalconfig['ReportUsage'])
                          );
                          echo '</td>';
                          echo '</tr>';
                        if (PluginGlpi2mdtConfig::canUpdate()) {
                           echo '<tr class="tab_bg_1">';
                           echo '<td>';
                           echo '<input type="submit" class="submit" value="'. __('Save', 'glpi2mdt').'" name="SAVE"/>';
                           echo '</td>';
                           echo '</td><td>';
                           echo '<input type="submit" class="submit" value="'. __('Check new version', 'glpi2mdt').'" name="UPDATE"/>';
                           echo '</td>';
                           echo '</tr>';
                           echo '<tr class="tab_bg_1">';
                           echo '<td>';
                           echo '<input type="submit" class="submit" value="'. __('Test connection', 'glpi2mdt').'" name="TEST"/>';
                           echo '</td><td>';
                           echo '<input type="submit" class="submit" value="'. __('Initialise data', 'glpi2mdt').'" name="INIT"/>';
                           echo '</td>';
                           echo '</tr>';
                        }
                        echo '</table>';
                        echo '</div>';
                        echo '</form>';
                        // Show alert if a new version is available
                        $currentversion = PLUGIN_GLPI2MDT_VERSION;
                        $latestversion = $this->globalconfig['LatestVersion'];
                        if (version_compare($currentversion, $latest_version, '<')) {
                           sprintf(__('A new version of plugin glpi2mdt is available: v%s'), $latestversion);
                        }
                        return true;
   }


   /**
    * Initialise data, load local tables from MDT MSSQL server
    *
    * @param Flag for manual run or started by cron
    *
    * @return   standard cron outputs: 0 nothing to do, <0 failed >0 Ran OK
    *           Will output if manual run, log data if cron
   **/
   function showInitialise($cron=false) {
      global $DB;

      echo '<table class="tab_cadre_fixe">';

      //
      // Load available settings fields and descriptions from MDT
      //
      $result = $this->query('SELECT  ColumnName, CategoryOrder, Category, Description
                      FROM dbo.Descriptions');
      $nb = $this->numrows($result);

      // Mark lines in order to detect deleted ones in the source database
      $DB->query("UPDATE `glpi_plugin_glpi2mdt_descriptions` SET is_in_sync=false WHERE is_deleted=false");
      // Hopefully there are less than 300 lines, do an atomic insert/update
      while ($row = $this->fetch_array($result)) {
         $column_name = $row['ColumnName'];
         $category_order = $row['CategoryOrder'];
         $category = $row['Category'];
         $description = $row['Description'];

         $query = "INSERT INTO glpi_plugin_glpi2mdt_descriptions
                    (`column_name`, `category_order`, `category`, `description`, `is_in_sync`, `is_deleted`)
                    VALUES ('$column_name', $category_order, '$category', '$description', true, false)
                  ON DUPLICATE KEY UPDATE category_order=$category_order, category='$category', description='$description', is_deleted=false, is_in_sync=true";
         $DB->query($query) or die("Error loading MDT descriptions to GLPI database. ". $DB->error());
      }
      echo "<tr class='tab_bg_1'><td>$nb ".__("lines loaded into table", 'glpi2mdt')."'descriptions'.".'</td>';
      $result = $DB->query("SELECT count(*) as nb FROM `glpi_plugin_glpi2mdt_descriptions` WHERE `is_in_sync`=false");
      $row = $DB->fetch_array($result);
      $nb = $row['nb'];
      $DB->query("UPDATE glpi_plugin_glpi2mdt_descriptions SET is_in_sync=true, is_deleted=true 
                      WHERE is_in_sync=false AND is_deleted=false");
      echo "<td>$nb ".__("lines deleted from table", 'glpi2mdt')." 'descriptions'.".'</td></tr>';

      //
      // Load available roles from MDT
      //
      $result = $this->query('SELECT  ID, Role FROM dbo.RoleIdentity');

      // Mark lines in order to detect deleted ones in the source database
      $DB->query("UPDATE `glpi_plugin_glpi2mdt_roles` SET is_in_sync=false WHERE is_deleted=false");
      while ($row = $this->fetch_array($result)) {
         $id = $row['ID'];
         $role = $row['Role'];

         $query = "INSERT INTO glpi_plugin_glpi2mdt_roles
                    (`id`, `role`, `is_deleted`, `is_in_sync`)
                    VALUES ('$id', '$role', false, true)
                  ON DUPLICATE KEY UPDATE role='$role', is_deleted=false, is_in_sync=true";
         $DB->queryOrDie($query, "Error loading MDT roles to GLPI database.");
      }

      // Mark lines which are not in MDT anymore as deleted
      $DB->query("UPDATE glpi_plugin_glpi2mdt_roles SET is_in_sync=true, is_deleted=true 
                    WHERE is_in_sync=false AND is_deleted=false");

      $result = $this->query('SELECT  count(*) as nb FROM dbo.RoleIdentity');
      $row =$this->fetch_array($result);
      $nb = $row['nb'];
      echo "<tr class='tab_bg_1'><td>$nb ".__("lines loaded into table", 'glpi2mdt')." 'roles'.".'</td></tr>';

      $this->free_result($result);

      //
      // Load data from XML files in the deployment share
      //
      // Applications
      // Mark lines in order to detect deleted ones in the source database
      $DB->query("UPDATE glpi_plugin_glpi2mdt_applications SET is_in_sync=false WHERE is_deleted=false");
      $dst = $this->globalconfig['FileShare'].'/Applications.xml';
      //Basic tests just in case...
      if (file_exists($dst) and !(is_readable($dst))) {
         echo "<tr class='tab_bg_1'><td><font color='red'>Looks like '$dst' exists but is not readable. ";
         echo "Check access rights, and more specifically SELinux settings.</td></tr>";
      }
      $applications = simplexml_load_file($dst)
              or die("Cannot load file ".$dst);
      $nb = 0;
      foreach ($applications->application as $application) {
         $name = $application->Name;
         $guid = $application['guid'];
         if (isset($application['enable']) and ($application['enable'] == 'True')) {
            $enable = 'true'; } else {
            $enable = 'false';
            }
            if (isset($application['hide']) and ($application['hide'] == 'True')) {
               $hide = 'true'; } else {
               $hide = 'false';
               }
               $shortname = $application->ShortName;
               $version = $application->Version;

               $query = "INSERT INTO glpi_plugin_glpi2mdt_applications
                    (`guid`, `name`, `shortname`, `version`, `hide`, `enable`, `is_deleted`, `is_in_sync`)
                    VALUES ('$guid', '$name', '$shortname', '$version', $hide, $enable, false, true)
                  ON DUPLICATE KEY UPDATE name='$name', shortname='$shortname', version='$version', hide=$hide, 
                                          enable=$enable, is_deleted=false, is_in_sync=true";
               $DB->query($query) or die("Error loading MDT applications to GLPI database. ". $DB->error());
               $nb += 1;
      }
      echo "<tr class='tab_bg_1'><td>$nb ".__("lines loaded into table", 'glpi2mdt')." 'applications'.</td>";
      // Mark lines which are not in MDT anymore as deleted
      $result = $DB->query("SELECT count(*) as nb FROM glpi_plugin_glpi2mdt_applications WHERE `is_in_sync`=false");
      $row = $DB->fetch_array($result);
      $nb = $row['nb'];
      $DB->query("UPDATE glpi_plugin_glpi2mdt_applications SET is_in_sync=true, is_deleted=true 
                      WHERE is_in_sync=false AND is_deleted=false");
      echo "<td>$nb ".__("lines deleted from table", 'glpi2mdt')."'applications' </td><tr>";

      // Application groups
      // Mark lines in order to detect deleted ones in the source database
      $DB->queryOrDie("UPDATE glpi_plugin_glpi2mdt_application_groups SET is_in_sync=false WHERE is_deleted=false");
      $DB->queryOrDie("UPDATE glpi_plugin_glpi2mdt_application_group_links SET is_in_sync=false WHERE is_deleted=false");
      $groups = simplexml_load_file($this->globalconfig['FileShare'].'/ApplicationGroups.xml')
              or die("Cannot load file $this->globalconfig['FileShare']/ApplicationGroups.xml");
      $nb = 0;
      foreach ($groups->group as $group) {
         $name = $group->Name;
         $guid = $group['guid'];
         if (isset($group['enable']) and ($group['enable'] == 'True')) {
            $enable = 'true'; } else {
            $enable = 'false';
            }
            if (isset($group['hide']) and ($group['hide'] == 'True') and ($name <> 'hidden')) {
               $hide = 'true'; } else {
               $hide = 'false';
               }

               $query = "INSERT INTO glpi_plugin_glpi2mdt_application_groups
                    (`guid`, `name`, `hide`, `enable`, `is_deleted`, `is_in_sync`)
                    VALUES ('$guid', '$name', $hide, $enable, false, true)
                  ON DUPLICATE KEY UPDATE name='$name', hide=$hide, enable=$enable, is_deleted=false, is_in_sync=true";
               $DB->queryOrDie($query, "Error loading MDT application groups to GLPI database.");
               $nb += 1;
               foreach ($group->Member as $application_guid) {
                  $query = "INSERT INTO glpi_plugin_glpi2mdt_application_group_links
                    (`group_guid`, `application_guid`, `is_deleted`, `is_in_sync`)
                    VALUES ('$guid', '$application_guid', false, true)
                  ON DUPLICATE KEY UPDATE is_deleted=false, is_in_sync=true";
                  $DB->queryOrDie($query, "Error loading MDT application-group links to GLPI database.");
               }
      }
      echo "<tr class='tab_bg_1'><td>$nb ".__("lines loaded into table", 'glpi2mdt')." 'application groups'.</td>";
      // Mark lines which are not in MDT anymore as deleted
      $result = $DB->queryOrDie("SELECT count(*) as nb FROM glpi_plugin_glpi2mdt_application_groups WHERE `is_in_sync`=false");
      $row = $DB->fetch_array($result);
      $nb = $row['nb'];
      $DB->queryOrDie("UPDATE glpi_plugin_glpi2mdt_application_groups SET is_in_sync=true, is_deleted=true 
                      WHERE is_in_sync=false AND is_deleted=false");
      $DB->queryOrDie("DELETE FROM glpi_plugin_glpi2mdt_application_group_links 
                      WHERE is_in_sync=false AND is_deleted=false");
      echo "<td>$nb ".__("lines deleted from table", 'glpi2mdt')." 'application_group_links'.</td></tr>";

      // Task sequences
      // Mark lines in order to detect deleted ones in the source database
      $DB->query("UPDATE glpi_plugin_glpi2mdt_task_sequences SET is_in_sync=false WHERE is_deleted=false");
      $tss = simplexml_load_file($this->globalconfig['FileShare'].'/TaskSequences.xml')
              or die("Cannot load file $this->globalconfig['FileShare']/TaskSequences.xml");
      $nb = 0;
      foreach ($tss->ts as $ts) {
         $name = $ts->Name;
         $guid = $ts['guid'];
         $id = $ts->ID;
         if (isset($ts['enable']) and ($ts['enable'] == 'True')) {
            $enable = 'true'; } else {
            $enable = 'false';
            }
            if (isset($ts['hide']) and ($ts['hide'] == 'True')) {
               $hide = 'true'; } else {
               $hide = 'false';
               }

               $query = "INSERT INTO glpi_plugin_glpi2mdt_task_sequences
                    (`id`, `guid`, `name`, `hide`, `enable`, `is_deleted`, `is_in_sync`)
                    VALUES ('$id', '$guid', '$name', $hide, $enable, false, true)
                  ON DUPLICATE KEY UPDATE guid='$guid', name='$name', hide=$hide, enable=$enable, is_deleted=false, is_in_sync=true";
               $DB->queryOrDie($query, "Error loading MDT task sequences into GLPI database.");
               $nb += 1;
      }
      echo "<tr class='tab_bg_1'><td>$nb ".__("lines loaded into table", 'glpi2mdt')." 'task_sequences'.</td>";
      // Mark lines which are not in MDT anymore as deleted
      $result = $DB->query("SELECT count(*) as nb FROM glpi_plugin_glpi2mdt_task_sequences WHERE `is_in_sync`=false");
      $row = $DB->fetch_array($result);
      $nb = $row['nb'];
      $DB->query("UPDATE glpi_plugin_glpi2mdt_task_sequences SET is_in_sync=true, is_deleted=true 
                      WHERE is_in_sync=false AND is_deleted=false");
      echo "<td>$nb ".__("lines deleted from table", 'glpi2mdt')." 'task_sequence'.</td></tr>";

      // Task sequence groups
      // Mark lines in order to detect deleted ones in the source database
      $DB->query("UPDATE glpi_plugin_glpi2mdt_task_sequence_groups SET is_in_sync=false WHERE is_deleted=false");
      $DB->query("UPDATE glpi_plugin_glpi2mdt_task_sequence_group_link SET is_in_sync=false WHERE is_deleted=false");
      $groups = simplexml_load_file($this->globalconfig['FileShare'].'/TaskSequenceGroups.xml')
              or die("Cannot load file $this->globalconfig['FileShare']/TaskSequenceGroups.xml");
      $nb = 0;
      foreach ($groups->group as $group) {
         $name = $group->Name;
         $guid = $group['guid'];
         if (isset($group['enable']) and ($group['enable'] == 'True')) {
            $enable = 'true'; } else {
            $enable = 'false';
            }
            if (isset($group['hide']) and ($group['hide'] == 'True') and ($name <> 'hidden')) {
               $hide = 'true'; } else {
               $hide = 'false';
               }

               $query = "INSERT INTO glpi_plugin_glpi2mdt_task_sequence_groups
                    (`guid`, `name`, `hide`, `enable`, `is_deleted`, `is_in_sync`)
                    VALUES ('$guid', '$name', $hide, $enable, false, true)
                  ON DUPLICATE KEY UPDATE name='$name', hide=$hide, enable=$enable, is_deleted=false, is_in_sync=true";
               $DB->query($query) or die("Error loading MDT task sequence groups to GLPI database. ". $DB->error());
               $nb += 1;
               foreach ($group->member as $sequence_guid) {
                  $query = "INSERT INTO glpi_plugin_glpi2mdt_application_group_links
                    (`group_guid`, ``sequence_guid`, `is_deleted`, `is_in_sync`)
                    VALUES ('$guid', '$sequence_guid', false, true)
                  ON DUPLICATE KEY UPDATE is_deleted=false, is_in_sync=true";
                  $DB->query($query) or die("Error loading MDT sequence-group links to GLPI database. ". $DB->error());
               }
      }
      echo "<tr class='tab_bg_1'><td>$nb ".__("lines loaded into table", 'glpi2mdt')." 'task sequence groups'.</td>";
      // Mark lines which are not in MDT anymore as deleted
      $result = $DB->query("SELECT count(*) as nb FROM glpi_plugin_glpi2mdt_task_sequence_groups WHERE `is_in_sync`=false");
      $row = $DB->fetch_array($result);
      $nb = $row['nb'];
      $DB->query("UPDATE glpi_plugin_glpi2mdt_task_sequence_groups SET is_in_sync=true, is_deleted=true 
                      WHERE is_in_sync=false AND is_deleted=false");
      $DB->query("DELETE FROM glpi_plugin_glpi2mdt_task_sequence_group_links 
                      WHERE is_in_sync=false AND is_deleted=false");
      echo "<td>$nb ".__("lines deleted from table", 'glpi2mdt')." 'task_sequence_group_links'.</td></tr></table>";
   }

}
