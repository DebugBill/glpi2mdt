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
* @brief This class extends the original GLPI crontask class and adds specific crons
* for the gkpi2mdt plugin.
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}


/**
 * Glpi2mdtcrontask class
**/
class PluginGlpi2mdtCronTask extends PluginGlpi2mdtMdt {

   /**
   * Check if new version is available
   *
   * @param $auto                  boolean: check done by cron ? (if not display result)
   *                                        (default true)
   * @param $messageafterredirect  boolean: use message after redirect instead of display
   *                                        (default false)
   *
   * @return integer if started in cron mode. Outputs HTML data otherwise
   *    >0 : done
   *    <0 : to be run again (not finished)
   *     0 : nothing to be done
   **/
   static function cronCheckGlpi2mdtUpdate($task, $cron=true, $messageafterredirect=false) {
      $currentversion = PLUGIN_GLPI2MDT_VERSION;
      global $DB;

      // if (!$cron && !Session::haveRight('backup', Backup::CHECKUPDATE)) {
      //   return false;
      // }

      //parse github releases (get last version number)
      $error = "";
      $json_gh_releases = Toolbox::getURLContent("https://api.github.com/repos/DebugBill/glpi2mdt/releases", $error);
      $all_gh_releases = json_decode($json_gh_releases, true);
      $released_tags = array();
      foreach ($all_gh_releases as $release) {
         if ($release['prerelease'] == false) {
            $released_tags[] =  $release['tag_name'];
         }
      }
      usort($released_tags, 'version_compare');
      $latest_version = array_pop($released_tags);
      // Did we get something? Maybe not if the server has no internet access...
      if (strlen(trim($latest_version)) == 0) {
         if ($cron) {
            $task->log($error);
         } else {
            if ($messageafterredirect) {
               Session::addMessageAfterRedirect($error, true, ERROR);
            } else {
               return $error;
            }
         }
      } else {
         // Build a unique ID which will differentiate platforms (dev, prod..) behind the same public IP
         $glpi2mdtconfig = new PluginGlpi2mdtConfig;
         $globalconfig = $glpi2mdtconfig->globalconfig;
         $rawid  = $globalconfig['DBServer'].$globalconfig['DBPort'].$globalconfig['DBSchema'].$globalconfig['DBLogin'];
         $PL = hash('md5', $rawid, false);
         $gets = "?PHP=".phpversion()."&G2M=$currentversion&PL=$PL";
         // Are we allowed to report usage data?
         $query = "SELECT value_char FROM glpi_plugin_glpi2mdt_parameters
                     WHERE is_deleted=false AND scope='global' AND parameter='ReportUsage'";
         if ($DB->fetch_assoc($DB->query($query))['value_char'] == 'YES') {
            $CO = $globalconfig['Mode'];
            $AP = $DB->fetch_row($DB->query("SELECT count(*) FROM glpi_plugin_glpi2mdt_applications WHERE is_deleted=false"))[0];
            $AG = $DB->fetch_row($DB->query("SELECT count(*) FROM glpi_plugin_glpi2mdt_application_groups WHERE is_deleted=false"))[0];
            $TS = $DB->fetch_row($DB->query("SELECT count(*) FROM glpi_plugin_glpi2mdt_task_sequences WHERE is_deleted=false"))[0];
            $TG = $DB->fetch_row($DB->query("SELECT count(*) FROM glpi_plugin_glpi2mdt_task_sequence_groups WHERE is_deleted=false"))[0];
            $RO = $DB->fetch_row($DB->query("SELECT count(*) FROM glpi_plugin_glpi2mdt_roles WHERE is_deleted=false"))[0];
            $MO = $DB->fetch_row($DB->query("SELECT count(*) FROM glpi_plugin_glpi2mdt_models WHERE is_deleted=false"))[0];
            $PK = 0; //$DB->fetch_row($DB->query("SELECT count(*) FROM glpi_plugin_glpi2mdt_packages WHERE is_deleted=false"))[0];
            $ST = $DB->fetch_row($DB->query("SELECT count(*) FROM glpi_plugin_glpi2mdt_settings"))[0];
            $gets = $gets."&CO=$CO&&AP=$AP&AG=$AG&TS=$TS&TG=$TG&RO=$RO&MO=$MO&PK=$PK&ST=$ST";
         }
         Toolbox::getURLContent("https://glpi2mdt.thauvin.org/report.php".$gets);
         if ($cron) {
            $task->log("https://glpi2mdt.thauvin.org/report.php".$gets);
         }
         $query = "INSERT INTO glpi_plugin_glpi2mdt_parameters
                          (`parameter`, `scope`, `value_char`, `is_deleted`)
                          VALUES ('LatestVersion', 'global', '$latest_version', false)
                   ON DUPLICATE KEY UPDATE value_char='$latest_version', value_num=NULL, is_deleted=false";
         $DB->queryOrDie($query, "Database error");
         if (version_compare($currentversion, $latest_version, '<')) {
            $message = sprintf(__('A new version of plugin glpi2mdt is available: v%s'), $latest_version);
         } else {
            $message = sprintf(__('You have the latest available version of glpi2mdti: v%s'), $latest_version);
         }
         if ($cron) {
               $task->log($message);
         } else {
            if ($messageafterredirect) {
               Session::addMessageAfterRedirect($message);
            } else {
               return $message;
            }
         }
      }
      return 1;
   }


   /**
   * Task to initialise data, load local tables from MDT MSSQL server
   *
   * @param Flag for manual run or started by cron
   *
   * @return integer if started in cron mode. Outputs HTML data otherwise
   *    >0 : done
   *    <0 : to be run again (not finished)
   *     0 : nothing to be done
   **/
   static function cronUpdateBaseconfigFromMDT($task, $cron=true) {
      global $DB;
      $ok = 1;
      $MDT = new PluginGlpi2mdtMdt;
      $globalconfig = $MDT->globalconfig;

      if (!$cron) {
         echo '<table class="tab_cadre_fixe">';
      }

      //
      // Load available settings fields and descriptions from MDT
      //
      $result = $MDT->queryOrDie('SELECT  ColumnName, CategoryOrder, Category, Description
                      FROM dbo.Descriptions', "???");
      $nb = $MDT->numrows($result);
      // Mark lines in order to detect deleted ones in the source database
      $DB->query("UPDATE `glpi_plugin_glpi2mdt_descriptions` SET is_in_sync=false WHERE is_deleted=false");
      // Hopefully there are less than 300 lines, do an atomic insert/update
      while ($row = $MDT->fetch_array($result)) {
         $column_name = $row['ColumnName'];
         $category_order = $row['CategoryOrder'];
         $category = $row['Category'];
         $description = $row['Description'];

         $query = "INSERT INTO glpi_plugin_glpi2mdt_descriptions
                    (`column_name`, `category_order`, `category`, `description`, `is_in_sync`, `is_deleted`)
                    VALUES ('$column_name', $category_order, '$category', '$description', true, false)
                    ON DUPLICATE KEY UPDATE category_order=$category_order, category='$category',
                                  description='$description', is_in_sync=true, is_deleted=false;";
         $DB->queryOrDie($query, "Error loading MDT descriptions to GLPI database.");
      }
      if (!$cron) {
         echo "<tr class='tab_bg_1'><td>$nb ".__("lines loaded into table", 'glpi2mdt')."'descriptions'.".'</td>';
      }
      $result = $DB->query("SELECT count(*) as nb FROM `glpi_plugin_glpi2mdt_descriptions` WHERE `is_in_sync`=false");
      $row = $DB->fetch_array($result);
      $nb = $row['nb'];
      $DB->query("UPDATE glpi_plugin_glpi2mdt_descriptions SET is_in_sync=true, is_deleted=true 
                      WHERE is_in_sync=false AND is_deleted=false");
      if (!$cron) {
         echo "<td>$nb ".__("lines deleted from table", 'glpi2mdt')." 'descriptions'.".'</td></tr>';
      }

      //
      // Load available roles from MDT
      //
      $result = $MDT->query('SELECT  ID, Role FROM dbo.RoleIdentity');

      // Mark lines in order to detect deleted ones in the source database
      $DB->query("UPDATE `glpi_plugin_glpi2mdt_roles` SET is_in_sync=false WHERE is_deleted=false");
      while ($row = $MDT->fetch_array($result)) {
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

      $result = $MDT->query('SELECT  count(*) as nb FROM dbo.RoleIdentity');
      $row =$MDT->fetch_array($result);
      $nb = $row['nb'];
      if (!$cron) {
         echo "<tr class='tab_bg_1'><td>$nb ".__("lines loaded into table", 'glpi2mdt')." 'roles'.".'</td></tr>';
      }

      $MDT->free_result($result);

      //
      // Load data from XML files in the deployment share
      //
      // Applications
      // Mark lines in order to detect deleted ones in the source database
      $dst = $MDT->globalconfig['FileShare'].'/Applications.xml';
      $applications = PluginGlpi2mdtCronTask::checkFile($dst, $task, $cron);

      if ($applications !== false) {
         $DB->query("UPDATE glpi_plugin_glpi2mdt_applications SET is_in_sync=false WHERE is_deleted=false");
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
                  $DB->queryOrDie($query, "Error loading MDT applications to GLPI database.");
                  $nb += 1;
         }
         if (!$cron) {
            echo "<tr class='tab_bg_1'><td>$nb ".__("lines loaded into table", 'glpi2mdt')." 'applications'.</td>";
         }
         // Mark lines which are not in MDT anymore as deleted
         $result = $DB->query("SELECT count(*) as nb FROM glpi_plugin_glpi2mdt_applications WHERE `is_in_sync`=false");
         $row = $DB->fetch_array($result);
         $nb = $row['nb'];
         $DB->query("UPDATE glpi_plugin_glpi2mdt_applications SET is_in_sync=true, is_deleted=true 
                      WHERE is_in_sync=false AND is_deleted=false");
         if (!$cron) {
            echo "<td>$nb ".__("lines deleted from table", 'glpi2mdt')."'applications' </td><tr>";
         }
      } else {
         $ok = -1;
      }

      // Application groups
      // Mark lines in order to detect deleted ones in the source database
      $dst = $MDT->globalconfig['FileShare'].'/ApplicationGroups.xml';
      $groups = PluginGlpi2mdtCronTask::checkFile($dst, $task, $cron);

      if ($groups !== false) {
         $DB->queryOrDie("UPDATE glpi_plugin_glpi2mdt_application_groups SET is_in_sync=false WHERE is_deleted=false");
         $DB->queryOrDie("UPDATE glpi_plugin_glpi2mdt_application_group_links SET is_in_sync=false WHERE is_deleted=false");
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
         if (!$cron) {
            echo "<tr class='tab_bg_1'><td>$nb ".__("lines loaded into table", 'glpi2mdt')." 'application groups'.</td>";
         }
         // Mark lines which are not in MDT anymore as deleted
         $result = $DB->queryOrDie("SELECT count(*) as nb FROM glpi_plugin_glpi2mdt_application_groups WHERE `is_in_sync`=false");
         $row = $DB->fetch_array($result);
         $nb = $row['nb'];
         $DB->queryOrDie("UPDATE glpi_plugin_glpi2mdt_application_groups SET is_in_sync=true, is_deleted=true 
                      WHERE is_in_sync=false AND is_deleted=false");
         $DB->queryOrDie("DELETE FROM glpi_plugin_glpi2mdt_application_group_links 
                      WHERE is_in_sync=false AND is_deleted=false");
         if (!$cron) {
            echo "<td>$nb ".__("lines deleted from table", 'glpi2mdt')." 'application_group_links'.</td></tr>";
         }
      } else {
         $ok = -1;
      }
      // Task sequences
      // Mark lines in order to detect deleted ones in the source database
      $dst = $MDT->globalconfig['FileShare'].'/TaskSequences.xml';
      $tss = PluginGlpi2mdtCronTask::checkFile($dst, $task, $cron);

      if ($tss !== false) {
         $DB->query("UPDATE glpi_plugin_glpi2mdt_task_sequences SET is_in_sync=false WHERE is_deleted=false");
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
         if (!$cron) {
            echo "<tr class='tab_bg_1'><td>$nb ".__("lines loaded into table", 'glpi2mdt')." 'task_sequences'.</td>";
         }
         // Mark lines which are not in MDT anymore as deleted
         $result = $DB->query("SELECT count(*) as nb FROM glpi_plugin_glpi2mdt_task_sequences WHERE `is_in_sync`=false");
         $row = $DB->fetch_array($result);
         $nb = $row['nb'];
         $DB->query("UPDATE glpi_plugin_glpi2mdt_task_sequences SET is_in_sync=true, is_deleted=true 
                      WHERE is_in_sync=false AND is_deleted=false");
         if (!$cron) {
            echo "<td>$nb ".__("lines deleted from table", 'glpi2mdt')." 'task_sequence'.</td></tr>";
         }
      } else {
         $ok = -1;
      }
      // Task sequence groups
      // Mark lines in order to detect deleted ones in the source database
      $dst = $MDT->globalconfig['FileShare'].'/TaskSequenceGroups.xml';
      $groups = PluginGlpi2mdtCronTask::checkFile($dst, $task, $cron);

      if ($groups !== false) {
         $DB->query("UPDATE glpi_plugin_glpi2mdt_task_sequence_groups SET is_in_sync=false WHERE is_deleted=false");
         $DB->query("UPDATE glpi_plugin_glpi2mdt_task_sequence_group_links SET is_in_sync=false WHERE is_deleted=false");
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
                  $DB->queryOrDie($query, "Error loading MDT task sequence groups to GLPI database.");
                  $nb += 1;
                  foreach ($group->member as $sequence_guid) {
                     $query = "INSERT INTO glpi_plugin_glpi2mdt_application_group_links
                    (`group_guid`, ``sequence_guid`, `is_deleted`, `is_in_sync`)
                    VALUES ('$guid', '$sequence_guid', false, true)
                  ON DUPLICATE KEY UPDATE is_deleted=false, is_in_sync=true";
                     $DB->queryOrDie($query, "Error loading MDT sequence-group links to GLPI database.");
                  }
         }
         if (!$cron) {
            echo "<tr class='tab_bg_1'><td>$nb ".__("lines loaded into table", 'glpi2mdt')." 'task sequence groups'.</td>";
         }
         // Mark lines which are not in MDT anymore as deleted
         $result = $DB->query("SELECT count(*) as nb FROM glpi_plugin_glpi2mdt_task_sequence_groups WHERE `is_in_sync`=false");
         $row = $DB->fetch_array($result);
         $nb = $row['nb'];
         $DB->query("UPDATE glpi_plugin_glpi2mdt_task_sequence_groups SET is_in_sync=true, is_deleted=true 
                      WHERE is_in_sync=false AND is_deleted=false");
         $DB->query("DELETE FROM glpi_plugin_glpi2mdt_task_sequence_group_links
                      WHERE is_in_sync=false AND is_deleted=false");
         if (!$cron) {
            echo "<td>$nb ".__("lines deleted from table", 'glpi2mdt')." 'task_sequence_group_links'.</td></tr></table>";
         }
      } else {
         $ok = -1;
      }
      return $ok;
   }

   /**
   * Task to synchronize data between MDT and GLPI in Master-Master mode
   * Can be used atomically to update one machine, or globally by cron
   *
   * @param $task Object of CronTask class for log / stat
   * @param $id; computer ID to update
   *
   * @return integer
   *    >0 : done
   *    <0 : to be run again (not finished)
   *     0 : nothing to be done
   */
   static function cronSyncMasterMaster($task, $id=0) {
      global $DB;
      $MDT = new PluginGlpi2mdtMdt;
      $globalconfig = $MDT->globalconfig;
      if ($globalconfig['Mode'] <> "Master") {
         $task->log("This cron task runs only in Master-Master mode");
         return 0;
      }
      // Build array of valid variables
      $variables = $globalconfig['variables'];
      if ($id > 0) {
         $mdt = $MDT->getMdtIds($id);
         $mdtids = "AND c.".$mdt['mdtids'];
         $arraymdtids = $mdt['arraymdtids'];
      } else {
         $mdtids='';
      }

      //GET all computers and settings
      $query="SELECT * FROM dbo.ComputerIdentity c, dbo.Settings s WHERE c.id=s.id $mdtids";
      $result = $MDT->queryOrDie($query, "Cannot retreive computers from MDT");
      if (isset($task)) {
         $task->setVolume($MDT->numrows($result));
         $task->log("Computer entries found in MDT database");
      }
      $correspondances = 0;
      $fields = 0;
      $previousid = 0;
      while ($row = $MDT->fetch_array($result)) {
         // Find correspondance in GLPI
         $query = "SELECT c.id as id FROM glpi_computers c, glpi_networkports n
                    WHERE c.id=n.items_id AND n.itemtype='Computer' AND n.instantiation_type='NetworkPortEthernet' 
                    AND c.is_deleted=FALSE AND n.is_deleted=false AND c.name = '".$row['Description']."' 
                    AND UPPER(n.mac)='".$row['MacAddress']."' AND serial='".$row['SerialNumber']."' AND otherserial='".
                    $row['AssetTag']."' AND uuid='".$row['UUID']."' ORDER BY c.id";
         $glpi = $DB->queryOrDie($query, "Can't find correspondance in GLPI");
         if ($DB->numrows($glpi) == 1) {
            $array = $DB->fetch_array($glpi);
            $id = $array['id'];
            // Mark settings that may have to be deleted only if first iteration on this computer
            if ($previousid<>$id) {
               $DB->query("UPDATE glpi_plugin_glpi2mdt_settings SET is_in_sync=false WHERE type='C' AND category='C' AND id=$id");
            }
            $previousid = $id;
            $correspondances += 1;
            // Update GLPI with data from MDT
            foreach ($row as $key=>$value) {
               if (isset($variables[$key]) AND $value <>'' AND $value<>null) {
                  $DB->queryOrDie("INSERT INTO glpi_plugin_glpi2mdt_settings (ID, type, category, `key`, `value`, `is_in_sync`) 
                              VALUES ($id, 'C', 'C', '$key', '$value', true)
                              ON DUPLICATE KEY UPDATE `key`='$key', value='$value', is_in_sync=true;", "Can't insert setting");
                  $fields += 1;
               }
            }
            $DB->query("DELETE FROM glpi_plugin_glpi2mdt_settings WHERE type='C' AND category='C' AND is_in_sync=false AND id=$id");
         }
      }
      if (isset($task)) {
         $task->setVolume($correspondances);
         $task->log("computers updated in GLPI");
         $task->setVolume($fields);
         $task->log("settings updated in GLPI");
         return 1;
      }
      return 0;
   }



   /**
   * Task to reset the OSinstall flag at specified time
   *
   * @param $task Object of CronTask class for log / stat
   *
   * @return integer
   *    >0 : done
   *    <0 : to be run again (not finished)
   *     0 : nothing to be done
   */
   static function cronExpireOSInstallFlag($task) {
      global $DB;
      $MDT = new PluginGlpi2mdtMdt;
      $globalconfig = $MDT->globalconfig;

      $query = "SELECT id FROM glpi_plugin_glpi2mdt_settings 
                   WHERE `type`='C' AND `category`='C' AND `key`='OSInstallExpire' AND `value`<=".time();
      $result = $DB->query($query) or $task->log("Database error: ". $DB->error()."<br><br>".$query);
      if ($DB->numrows($result)==0) {
         $task->log("No records to expire, exiting");
         return 0;
      }
      $nb = 0;
      while ($row=$DB->fetch_array($result)) {
         $nb += 1;
         $id = $row['id'];
         $ids = $MDT->getMdtIDs($id);
         // Cancel installation flag directly into MDT and MSSQL
         $query = "UPDATE dbo.Settings SET OSInstall='' 
                     FROM dbo.Settings WHERE type='C' AND ".$ids['mdtids'].";";
         $MDT->query($query) or $task->log("Can't reset OSInstall flag<br><br>".$query."<br><br>".$MDT->dberror());

         // Do the same now on GLPI database
         $query = "DELETE FROM glpi_plugin_glpi2mdt_settings WHERE type='C' AND category='C' 
                        AND id=$id AND ((`key`='OSInstall' AND  value='YES') OR `key`='OSInstallExpire');";
         $DB->query($query) or $task->log("Database error: ". $DB->error()."<br><br>".$query);
      }
      $task->log('record(s) expired');
      $task->setVolume($nb);
      return 1;
   }


   /**
    * Get cron "type" information valid for all tasks in this file
    *
    * @param nb, no idea what it is used for.
    * @return string type for the cron list page
    */
   static function getTypeName($nb=0) {
      return __('Glpi2mdt Plugin', 'glpi2mdt');

   }



   /**
    * get Cron descriptions for crons defined in this class
    *
    * @param $name string name of the task
    *
    * @return array of string
   **/
   static function cronInfo($name) {

      switch ($name) {
         case 'checkGlpi2mdtUpdate' :
            return array('description' => __('Check for new updates', 'glpi2mdt'));

         case 'updateBaseconfigFromMDT' :
            return array('description' => __('Update base data from MDT XML files and MS-SQL DB', 'glpi2mdt'));

         case 'syncMasterMaster' :
            return array('description' => __('Synchronize data between MDT and GLPI in Master-Master mode', 'glpi2mdt'));

         case 'expireOSInstallFlag' :
            return array('description' => __('Disable "OS Install" flag when expired', 'glpi2mdt'));

      }
   }

   /**
    * Check if xml file is accessible and valid
    *
    * @param $file file to check
    * $task task object is launched from cron
    * $cron flag "started by cron or interactively"
    *
    * @return "false" if failed, handle to XML if successful
   **/
   static function checkFile($file, $task, $cron) {
      if (!file_exists($file)) {
         if ($cron) {
            $task->log("File '$file' not found. Check mounting point.");
         } else {
            echo "<tr class='tab_bg_1'><td><font color='red'>". sprintf(__("File '%s' not found.", 'glpi2mdt'), $file)."</font></td></tr> ";
         }
         return false;
      }
      if (!is_readable($file)) {
         if ($cron) {
            $task->log("File '$file' exists but is not readable. check access rights, and more specifically SELinux settings.");
         } else {
            echo "<tr class='tab_bg_1'><td><font color='red'>".sprintf(__("Looks like '%s' exists but is not readable. ", 'glpi2mdt'), $file);
            echo "<br>Check access rights, and more specifically SELinux settings.</font></td></tr>";
         }
         return false;
      }
      $XML = simplexml_load_file($file);
      if ($XML === false) {
         if ($cron) {
            $task->log("File '$file' contains no valid data. Check MDT configuration");
         } else {
            echo "<tr class='tab_bg_1'><td><font color='red'>".sprintf(__("File '%s' contains no valid data. Check MDT configuration", 'glpi2mdt'), $file);
            echo "</font></td></tr>";
         }
         return false;
      }
      return $XML;
   }
}
