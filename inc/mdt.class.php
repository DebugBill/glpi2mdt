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
// Purpose of file: Base class to connect to MS-SQL MDT
// Other classes in for the plugin will extend it
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginGlpi2mdtMdt extends CommonDBTM {
   // Post parameters valid for configuration form and their expected content
   protected $validkeys=array(
                 'DBServer' => 'txt',
                 'DBLogin' => 'txt',
                 'DBPassword' => 'txt',
                 'DBSchema' => 'txt',
                 'DBPort' => 'num',
                 'Mode' => 'txt',
                 'FileShare' => 'txt',
                 'LocalAdmin' => 'txt',
                 'Complexity' => 'txt',
                 'CheckNewVersion' => 'txt',
                 'ReportUsage' => 'txt'
                );
   protected $globalconfig;
   protected $MDT;

   /**
    * Load plugin settings, connect to MSSQL;
    *
    * @param None
    *
    * @return                     Dies if database cannot be read
   **/
   function __construct() {
      parent::__construct();
      global $DB;

      $dbport=1433;
      $mode='Strict';

      $query = "SELECT `parameter`, `value_char`, `value_num`
                FROM `glpi_plugin_glpi2mdt_parameters`
                WHERE `is_deleted` = false AND `scope`= 'global'";
      $result=$DB->query($query) or die("Error loading parameters from GLPI database ". $DB->error());
      while ($data = $DB->fetch_array($result)) {
         if (isset($this->validkeys[$data['parameter']])) {
            if ($this->validkeys[$data['parameter']] == 'txt') {
               $this->globalconfig[$data['parameter']] = $data['value_char'];
            } else {
               $this->globalconfig[$data['parameter']] = $data['value_num'];
            }
         }
      }
      // Connection to MSSQL
      $MDT = mssql_connect($this->globalconfig['DBServer'], $this->globalconfig['DBLogin'], $this->globalconfig['DBPassword']);
      if ($MDT) {
         if (mssql_select_db($this->globalconfig['DBSchema'], $MDT)) {
            $this->MDT = $MDT;
         }
      }
   }


   /**
    * Clean before destroying class
    *
    * @param None
    *
    * @return None
   **/
   function __destruct() {
      mssql_close($this->MDT);

      // Looks nice.... but fails because there is no destruct over there
      //parent::__destruct();
   }


   /**
    * Test MDT connection;
    *
    * @param None
    *
    * @return result as HTML output
   **/
   function showTestConnection() {
      echo '<table class="tab_cadre_fixe">';
      echo '<tr class="tab_bg_1">';
      echo '<td>';
      // Connection to MSSQL
      $link = mssql_connect($this->globalconfig['DBServer'], $this->globalconfig['DBLogin'], $this->globalconfig['DBPassword']);
      if ($link) {
         echo "<h1><font color='green'>";
         echo _e("Database login OK!", 'glpi2mdt');
         echo "</font></h1><br>";
         // Simple query to get database version
         $version = mssql_query('SELECT @@VERSION');
         $row = mssql_fetch_array($version);
         echo "Server is: <br>".$row[0]."<br>";
         if (mssql_select_db($this->globalconfig['DBSchema'], $link)) {
            echo "<h1><font color='green'>";
            echo _e("Schema selection OK!", 'glpi2mdt');
            echo "</font></h1><br>";
         } else {
            echo "<h1><font color='red'>";
            echo _e("Schema selection KO!", 'glpi2mdt');
            echo "</font></h1><br>";
         }
      } else {
         echo "<h1><font color='red'>";
         echo _e("Database login KO!", 'glpi2mdt');
         echo "</font></h1><br>";
      }
      echo '</td>';
      echo '</tr></table>';
   }


   function query($query ) {
      return mssql_query($query, $this->MDT);
   }

   function queryOrDie($query, $message = '' ) {
      $result = mssql_query($query, $this->MDT) or die ($message."<br><br>".$query."<br><br>".mssql_get_last_message());
      return $result;
   }

   function numrows(mssql_result $result) {
      return mssql_num_rows($result);
   }

   function fetch_array(mssql_result $result) {
      return mssql_fetch_array($result);
   }

   function fetch_row(mssql_result $result) {
      return mssql_fetch_row($result);
   }

   function fetch_assoc(mssql_result $result) {
      return mssql_fetch_assoc($result);
   }

   function free_result(mssql_result $result) {
      return mssql_free_result($result);
   }
}
