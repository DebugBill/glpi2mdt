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
// This class will use either ODBC or the mssql php extension available until php 5.5
// Other classes in for the plugin will extend it
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginGlpi2mdtMdt extends CommonDBTM {
   // Post parameters valid for configuration form and their expected content
   protected $validkeys=array(
                 'DBDriver' =>'txt',
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
   protected $DBLink, $DBModule;

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
      $DBServer = $this->globalconfig['DBServer'];
      $DBPort = $this->globalconfig['DBPort'];
      $DBSchema = $this->globalconfig['DBSchema'];
      $DBLogin = $this->globalconfig['DBLogin'];
      $DBPassword = $this->globalconfig['DBPassword'];
      $DBDriver = $this->globalconfig['DBDriver'];

      // Connection to MSSQL using ODBC PHP module
      if (extension_loaded('odbc')) {
         $this->DBModule = 'odbc';
         $DBLink = odbc_connect("Driver=$DBDriver;Server=$DBServer,$DBPort;Database=$DBSchema;", $DBLogin, $DBPassword);

         // Conection to MSSQL using native MSSQL PHP module available until PHP 5.6
      } else if (extension_loaded('mssql')) {
         $this->DBModule = 'mssql';
         $DBLink = mssql_connect($DBServer.":".$DBPort, $DBLogin, $DBPassword);
         if ($DBLink) {
            mssql_select_db($DBSchema, $MDT);
         }
      }
      $this->DBLink = $DBLink;
   }

   /**
    * Clean before destroying class
    *
    * @param None
    *
    * @return None
   **/
   function __destruct() {
      if ($DBModule == "mssql") {
         mssql_close($this->DBLink);
      } else if ($DBModule == "odbc") {
         odbc_close($this->DBLink);
      }

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
      $dbschema = $this->globalconfig['DBSchema'];

      echo '<table class="tab_cadre_fixe">';
      echo '<tr class="tab_bg_1">';
      echo '<th>'.__("Testing connection using PHP module", 'glpi2mdt')." ".$this->DBModule.'</th></tr><tr><td>';
      // Connection to MSSQL
      if ($this->DBLink) {
         echo "<font color='green'>";
         echo __("Database login OK!", 'glpi2mdt');
         echo "</font><br>";
         // Simple query to get database version
         $version = $this->query('SELECT @@VERSION');
         $row = $this->fetch_array($version);
         echo "Server is: <br>".reset($row)."<br>";
         $result = $this->query("SELECT COUNT(*) FROM $dbschema.information_schema.tables WHERE table_type='base table'");
         $nb = reset($this->fetch_array($result));
         if ($nb > 0) {
            echo "<font color='green'>";
            echo __("Schema", 'glpi2mdt')." ".$dbschema." ".__("contains", 'glpi2mdt')." ".$nb." ".__("tables", 'glpi2mdt').".";
            echo "</font><br>";
         } else {
            echo "<h1><font color='red'>";
            echo __("Could not count tables in schema", 'glpi2mdt')." ".$this->DBSchema;
            echo "</font></h1><br>";
         }
      } else {
         echo "<h1><font color='red'>";
         echo __("Database login KO!", 'glpi2mdt');
         echo "</font></h1><br>";
      }
      echo '</td>';
      echo '</tr></table>';
   }


   /**
    * Several functions to interact with MDT database
    * Uses PHP module odbc or mssql as appropriate
    *
    * @param query, comment or none depending on function purpose
    *
    * @return result set for queries, numbers, or nothin
   **/
   function dberror() {
      if ($this->DBModule == 'mssql') {
         return mssql_get_last_message();
      } else if ($this->DBModule == 'odbc') {
         return odbc_errormsg($this->DBLink);
      }
   }

   function query($query ) {
      if ($this->DBModule == 'mssql') {
         return mssql_query($query, $this->DBLink);
      } else if ($this->DBModule == 'odbc') {
         return odbc_exec($this->DBLink, $query);
      }
   }

   function queryOrDie($query, $message = '' ) {
      if ($this->DBModule == 'mssql') {
         $result = mssql_query($query, $this->DBLink) or die ($message."<br><br>".$query."<br><br>".mssql_get_last_message());
      } else if ($this->DBModule == 'odbc') {
         $result = odbc_exec($this->DBLink, $query) or die ($message."<br><br>".$query."<br><br>".odbc_errormsg($this->DBLink));
      }
      return $result;
   }

   function numrows($result) {
      if ($this->DBModule == 'mssql') {
         return mssql_num_rows($result);
      } else if ($this->DBModule == 'odbc') {
         return odbc_num_rows($result);
      }
   }

   function fetch_array($result) {
      if ($this->DBModule == 'mssql') {
         return mssql_fetch_array($result);
      } else if ($this->DBModule == 'odbc') {
         return odbc_fetch_array($result);
      }
   }

   function fetch_row($result) {
      if ($this->DBModule == 'mssql') {
         return mssql_fetch_row($result);
      } else if ($this->DBModule == 'odbc') {
         return odbc_fetch_row($result);
      }
   }

   function fetch_assoc($result) {
      if ($this->DBModule == 'mssql') {
         return mssql_fetch_assoc($result);
      } else if ($this->DBModule == 'odbc') {
         return odbc_fetch_into($result);
      }
   }

   function free_result($result) {
      if ($this->DBModule == 'mssql') {
         return mssql_free_result($result);
      } else if ($this->DBModule == 'odbc') {
         return odbc_free_result($result);
      }
   }
}
