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
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}


/**
 * Toolbox Class
**/
class PluginGlpi2mdtToolbox {

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
   static function cronCheckUpdate($cron=true, $messageafterredirect=false) {
      global $DB;
      $currentversion = PLUGIN_GLPI2MDT_VERSION;

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
         //         if ($release['prerelease'] == false) {
            $released_tags[] =  $release['tag_name'];
         //         }
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
         $gets = "?PHP=".phpversion()."&G2M=$currentversion";
         // Are we allowed to report usage data?
         $query = "SELECT value_char FROM glpi_plugin_glpi2mdt_parameters
                     WHERE is_deleted=false AND scope='global' AND parameter='ReportUsage'";
         if ($DB->fetch_assoc($DB->query($query))['value_char'] == 'YES') {
            $AP = $DB->fetch_row($DB->query("SELECT count(*) FROM glpi_plugin_glpi2mdt_applications WHERE is_deleted=false"))[0];
            $AG = $DB->fetch_row($DB->query("SELECT count(*) FROM glpi_plugin_glpi2mdt_application_groups WHERE is_deleted=false"))[0];
            $TS = $DB->fetch_row($DB->query("SELECT count(*) FROM glpi_plugin_glpi2mdt_task_sequences WHERE is_deleted=false"))[0];
            $TG = $DB->fetch_row($DB->query("SELECT count(*) FROM glpi_plugin_glpi2mdt_task_sequence_groups WHERE is_deleted=false"))[0];
            $RO = $DB->fetch_row($DB->query("SELECT count(*) FROM glpi_plugin_glpi2mdt_roles WHERE is_deleted=false"))[0];
            $MO = 0; //$DB->fetch_row($DB->query("SELECT count(*) FROM glpi_plugin_glpi2mdt_models WHERE is_deleted=false"))[0];
            $PK = 0; //$DB->fetch_row($DB->query("SELECT count(*) FROM glpi_plugin_glpi2mdt_packages WHERE is_deleted=false"))[0];
            $ST = $DB->fetch_row($DB->query("SELECT count(*) FROM glpi_plugin_glpi2mdt_settings"))[0];
            $gets = $get."&AP=$AP&AG=$AG&TS=$TS&TG=$TG&RO=$RO&MO=$MO&PK=$PK&ST=$ST";
         }
         Toolbox::getURLContent("http://glpi2mdt.thauvin.org/report.php".$gets);
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
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
   static function getTypeName($nb=0) {
      return __('Check for glpi2mdt plugin updates', 'glpi2mdt');
   }
}
