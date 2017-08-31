<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
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
class ToolboxGlpi2mdt {


   /**
    * Check if new version is available
    *
    * @param $auto                  boolean: check done autically ? (if not display result)
    *                                        (true by default)
    * @param $messageafterredirect  boolean: use message after redirect instead of display
    *                                        (false by default)
    *
    * @return string explaining the result
   **/
   static function checkNewVersionAvailable($auto=true, $messageafterredirect=false) {
      global $CFG_GLPI;

      if (!$auto
          && !Session::haveRight('backup', Backup::CHECKUPDATE)) {
         return false;
      }

      if (!$auto && !$messageafterredirect) {
         echo "<br>";
      }

      //parse github releases (get last version number)
      $error = "";
      $json_gh_releases = self::getURLContent("https://api.github.com/repos/glpi-project/glpi/releases", $error);
      $all_gh_releases = json_decode($json_gh_releases, true);
      $released_tags = array();
      foreach ($all_gh_releases as $release) {
         if ($release['prerelease'] == false) {
            $released_tags[] =  $release['tag_name'];
         }
      }
      usort($released_tags, 'version_compare');
      $latest_version = array_pop($released_tags);

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
         if (version_compare($CFG_GLPI["version"], $latest_version, '<')) {
            $config_object                = new Config();
            $input["id"]                  = 1;
            $input["founded_new_version"] = $latest_version;
            $config_object->update($input);

            if (!$auto) {
               if ($messageafterredirect) {
                  Session::addMessageAfterRedirect(sprintf(__('A new version is available: %s.'),
                                                           $latest_version));
                  Session::addMessageAfterRedirect(__('You will find it on the GLPI-PROJECT.org site.'));
               } else {
                  echo "<div class='center'>".sprintf(__('A new version is available: %s.'),
                                                      $latest_version)."</div>";
                  echo "<div class='center'>".__('You will find it on the GLPI-PROJECT.org site.').
                       "</div>";
               }

            } else {
               if ($messageafterredirect) {
                  Session::addMessageAfterRedirect(sprintf(__('A new version is available: %s.'),
                                                           $latest_version));
               } else {
                  return sprintf(__('A new version is available: %s.'), $latest_version);
               }
            }

         } else {
            if (!$auto) {
               if ($messageafterredirect) {
                  Session::addMessageAfterRedirect(__('You have the latest available version'));
               } else {
                  echo "<div class='center'>".__('You have the latest available version')."</div>";
               }

            } else {
               if ($messageafterredirect) {
                  Session::addMessageAfterRedirect(__('You have the latest available version'));
               } else {
                  return __('You have the latest available version');
               }
            }
         }
      }
      return 1;
   }

}
