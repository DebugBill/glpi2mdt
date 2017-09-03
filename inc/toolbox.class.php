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
   static function checkNewVersionAvailable($cron=true, $messageafterredirect=false) {
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
         // Are we allowed to report usage data?
         $DB->query("SELECT value_num FROM glpi_plugin_glpi2mdt_parameters 
                     WHERE is_deleted=false AND scope='global' AND parameter='ReportUsage' AND value_CHAR='YES'");
         if ($DB->numrows($result) == 1) {
            Toolbox::getURLContent("http://glpi2mdt.thauvin.org/report.php?version=PLUGIN_GLPI2MDT_VERSION");
         }
         $newversion = sprintf(__('A new version of plugin glpi2mdt is available: %s.'), $latest_version);
         $uptodate = sprintf(__('You have the latest available version of glpi2mdti: %s.'), $latest_version);
         $repository = __('You will find it on GitHub.com.');

         $query = "INSERT INTO glpi_plugin_glpi2mdt_parameters
                          (`parameter`, `scope`, `value_char`, `is_deleted`)
                          VALUES ('LatestVersion', 'global', '$latest_version', false)
                   ON DUPLICATE KEY UPDATE value_char='$latst_version', value_num=NULL, is_deleted=false";
         $DB->query($query) or die("Database error: ". $DB->error());
         if (version_compare(PLUGIN_GLPI2MDT_VERSION, $latest_version, '<')) {

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

}
