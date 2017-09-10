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
 * Glpi2mdtcrontasks class
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
    * @return string explaining the result
   **/
   static function cronCheckGlpi2mdtUpdate($cron=true, $messageafterredirect=false) {
      $currentversion = PLUGIN_GLPI2MDT_VERSION;
      global $DB;
      //      if (!$auto
      //          && !Session::haveRight('backup', Backup::CHECKUPDATE)) {
      //         return false;
      //      }

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
         if (!$auto) {
            if ($messageafterredirect) {
               Session::addMessageAfterRedirect($error, true, ERROR);
            } else {
               echo "<div class='center'>$error</div>";
            }
         } else {
            return $error;
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
            $AP = $DB->fetch_row($DB->query("SELECT count(*) FROM glpi_plugin_glpi2mdt_applications WHERE is_deleted=false"))[0];
            $AG = $DB->fetch_row($DB->query("SELECT count(*) FROM glpi_plugin_glpi2mdt_application_groups WHERE is_deleted=false"))[0];
            $TS = $DB->fetch_row($DB->query("SELECT count(*) FROM glpi_plugin_glpi2mdt_task_sequences WHERE is_deleted=false"))[0];
            $TG = $DB->fetch_row($DB->query("SELECT count(*) FROM glpi_plugin_glpi2mdt_task_sequence_groups WHERE is_deleted=false"))[0];
            $RO = $DB->fetch_row($DB->query("SELECT count(*) FROM glpi_plugin_glpi2mdt_roles WHERE is_deleted=false"))[0];
            $MO = $DB->fetch_row($DB->query("SELECT count(*) FROM glpi_plugin_glpi2mdt_models WHERE is_deleted=false"))[0];
            $PK = 0; //$DB->fetch_row($DB->query("SELECT count(*) FROM glpi_plugin_glpi2mdt_packages WHERE is_deleted=false"))[0];
            $ST = $DB->fetch_row($DB->query("SELECT count(*) FROM glpi_plugin_glpi2mdt_settings"))[0];
            $gets = $gets."&AP=$AP&AG=$AG&TS=$TS&TG=$TG&RO=$RO&MO=$MO&PK=$PK&ST=$ST";
         }
         Toolbox::getURLContent("https://glpi2mdt.thauvin.org/report.php".$gets);
         $newversion = sprintf(__('A new version of plugin glpi2mdt is available: v%s'), $latest_version);
         $uptodate = sprintf(__('You have the latest available version of glpi2mdti: v%s'), $latest_version);
         $repository = __('You will find it on GitHub.com.');
         $query = "INSERT INTO glpi_plugin_glpi2mdt_parameters
                          (`parameter`, `scope`, `value_char`, `is_deleted`)
                          VALUES ('LatestVersion', 'global', '$latest_version', false)
                   ON DUPLICATE KEY UPDATE value_char='$latest_version', value_num=NULL, is_deleted=false";
         $DB->query($query) or die("Database error: ". $DB->error());
         if (version_compare($currentversion, $latest_version, '<')) {

            if (!$auto) {
               if ($messageafterredirect) {
                  Session::addMessageAfterRedirect($newversion);
                  Session::addMessageAfterRedirect($repository);
               } else {
                  echo "<div class='center'>".$newversion."</div>";
                  echo "<div class='center'>".$repository."</div>";
               }

            } else {
               if ($messageafterredirect) {
                  Session::addMessageAfterRedirect($newversion);
               } else {
                  return $newversion;
               }
            }

         } else {
            if (!$auto) {
               if ($messageafterredirect) {
                  Session::addMessageAfterRedirect($uptodate);
               } else {
                  echo "<div class='center'>".$uptodate."</div>";
               }

            } else {
               if ($messageafterredirect) {
                  Session::addMessageAfterRedirect($uptodate);
               } else {
                  return $uptodate;
               }
            }
         }
      }
      return 1;
   }


   /**
   * Task to update configuration data from MDT
   *
   * @param $task Object of CronTask class for log / stat
   *
   * @return integer
   *    >0 : done
   *    <0 : to be run again (not finished)
   *     0 : nothing to be done
   */
   static function cronUpdateBaseconfigFromMDT($task) {
      $config = new PluginGlpi2mdtConfig;
      $config->loadConf();
      $config->showInitialise(true);
      return 1;
   }


   /**
   * Task to synchronize data between MDT and GLPI in Master-Master mode
   *
   * @param $task Object of CronTask class for log / stat
   *
   * @return integer
   *    >0 : done
   *    <0 : to be run again (not finished)
   *     0 : nothing to be done
   */
   static function cronSyncMasterMaster($task) {
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
   function cronExpireOSInstallFlag($task) {
      global $DB;

      // Get login parameters from database and connect to MSQSL server
      $glpi2mdtconfig = new PluginGlpi2mdtConfig;
      $glpi2mdtconfig->loadConf();
      $globalconfig = $glpi2mdtconfig->globalconfig;
      $dbschema =  $globalconfig['DBSchema'];

      $link = mssql_connect($globalconfig['DBServer'], $globalconfig['DBLogin'], $globalconfig['DBPassword'])
         or die("<h1><font color='red'>Database login KO!</font></h1><br>");
      mssql_select_db($globalconfig['DBSchema'], $link)
         or die("<h1><font color='red'>Cannot switch to schema $dbschema on MSSQL server</font></h1><br>");
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

         // Get data for current computer
         $query = "SELECT name, uuid, serial, otherserial FROM glpi_computers WHERE id=$id AND is_deleted=false";
         $result = $DB->query($query) or $task->log("Database error: ". $DB->error()."<br><br>".$query);
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
                     or $task->log("Database error: ". $DB->error()."<br><br>".$query);
         $macs="MacAddress IN (";
         unset($values);
         while ($line = $DB->fetch_array($result)) {
            $mac = $line['mac'];
            $macs=$macs."'".$mac."', ";
         }
         $macs = substr($macs, 0, -2).") ";
         if ($macs ==  "MacAddress IN ()") {
            $macs='false';
         }
         // Cancel installation flag directly into MDT and MSSQL
         $query = "UPDATE $dbschema.dbo.Settings
                     SET OSInstall='NO' 
                     FROM $dbschema.dbo.ComputerIdentity i, $dbschema.dbo.Settings s
                     WHERE i.id=s.id AND s.type='C' 
                        AND ((UUID<>'' AND UUID='$uuid')
                          OR (Description<>'' AND Description='$name')
                          OR (SerialNumber<>'' AND SerialNumber='$serial') 
                          OR $macs)";
         mssql_query($query, $link) or $task->log("Can't reset OSInstall flag<br><br>".$query."<br><br>".mssql_get_last_message());

         // Do the same now on GLPI database
         $query = "DELETE FROM glpi_plugin_glpi2mdt_settings WHERE type='C' AND category='C' 
                        AND id=$id AND (`key`='OSInstall' OR `key`='OSInstallExpire');";
         $DB->query($query) or $task->log("Database error: ". $DB->error()."<br><br>".$query);
      }
      $task->log('record(s) expired');
      $task->setVolume($nb);
      return 1;
   }


   /**
    * Get cron "type" information valid for all tasks in this file
    *
    * @param none
    * @return string type for the cron list page
    */
   static function getTypeName() {
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
            return array('description' => __('Check for new updates'));

         case 'updateBaseconfigFromMDT' :
            return array('description' => __('Update base data from MDT XML files and MS-SQL DB'));

         case 'syncMasterMaster' :
            return array('description' => __('Syncrhonize data between MDT and GLPI in Master-Master mode'));

         case 'expireOSInstallFlag' :
            return array('description' => __('Disable "OS Install" flag when expired'));

      }
   }
}
