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
                 'DBVersion' => 'txt',
                 'DBServer' => 'txt',
                 'DBDriver' => 'txt',
                 'DBLogin' => 'txt',
                 'DBPassword' => 'txt',
                 'DBSchema' => 'txt',
                 'DBPort' => 'num',
                 'Mode' => 'txt',
                 'FileShare' => 'txt',
                 'LocalAdmin' => 'txt',
                 'Complexity' => 'txt',
                 'CheckNewVersion' => 'txt',
                 'ReportUsage' => 'txt',
                 'LatestVersion' => 'txt'
                );
   protected $globalconfig;
   protected $DBLink, $DBDriver;

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

      // Build array of valid variables
      $result = $DB->query("SELECT column_name FROM glpi_plugin_glpi2mdt_descriptions WHERE is_deleted=false");
      while ($line = $DB->fetch_array($result)) {
         $this->globalconfig['variables'][$line['column_name']] = true;
      }

      // Shortcut variables
      $DBServer = $this->globalconfig['DBServer'];
      $DBPort = $this->globalconfig['DBPort'];
      $DBSchema = $this->globalconfig['DBSchema'];
      $DBLogin = $this->globalconfig['DBLogin'];
      $DBPassword = $this->globalconfig['DBPassword'];
      $DBDriver = $this->globalconfig['DBDriver'];

      // Plugin version check
      $currentversion = PLUGIN_GLPI2MDT_VERSION;
      $latestversion = $this->globalconfig['LatestVersion'];
      if (version_compare($currentversion, $latestversion, '<')) {
         $this->globalconfig['newversion'] = sprintf(__('A new version of plugin glpi2mdt is available: v%s'), $latestversion);
      }

      // Connection to MSSQL using ODBC PHP module
      if (extension_loaded('odbc')) {
         $DBLink = odbc_connect("Driver=$DBDriver;Server=$DBServer,$DBPort;Database=$DBSchema;", $DBLogin, $DBPassword);
      }
      // Check if connection is successful, die if not
      if ($DBLink === false) {
         $error = __("Can't connect to MSSQL database using PHP ODBC module. Check configuration", 'glpi2mdt');
         Session::addMessageAfterRedirect($error, true, ERROR);
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
      // DBLink is false if no connection could be established
      if ($this->DBLink === false) {
         return;
      }
      odbc_close($this->DBLink);

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
      echo '<th>'.__("Testing connection using PHP ODBC module", 'glpi2mdt').'</th></tr><tr><td>';
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
         $array_result = $this->fetch_array($result);
         $nb = reset($array_result);
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
    * Uses PHP odbc
    *
    * @param query, comment or none depending on function purpose
    *
    * @return result set for queries, numbers, or nothin
   **/
   function dberror() {
      return odbc_errormsg($this->DBLink);
   }

   function query($query ) {
      return odbc_exec($this->DBLink, $query);
   }

   function queryOrDie($query, $message = '' ) {
      $result = odbc_exec($this->DBLink, $query) or die ($message."<br><br>".$query."<br><br>".odbc_errormsg($this->DBLink));
      return $result;
   }

   function numrows($result) {
      return odbc_num_rows($result);
   }

   function fetch_array($result) {
      return odbc_fetch_array($result);
   }

   function fetch_row($result) {
      return odbc_fetch_row($result);
   }

   function fetch_assoc($result) {
      return odbc_fetch_into($result);
   }

   function free_result($result) {
      return odbc_free_result($result);
   }

   /**
    * This function returns the list of computer IDs in MDT
    * corresponding to one computer ID in GLPI (because a computer in GLPI can have 0 to N mac addresses
    * when MDT has a 1 to 1 relationship on this item
    *
    * @param computer ID in GLPI
    *
    * @return array with three elements: macs, values, ids
    *            macs   string, list of mac addresses ready to be included in a WHERE clause
    *            values string, list ready to be used in a "INSERT VALUES" clause
    *            ids    string, list of IDs ready to be included in a WHERE clause
   **/
   function getMdtIds($id) {
      global $DB;

      // Get data for current computer
      $query = "SELECT name, uuid, serial, otherserial FROM glpi_computers WHERE id=$id AND is_deleted=false";
      $result = $DB->query($query) or $task->log("Database error: ". $DB->error()."<br><br>".$query);
      $common = $DB->fetch_array($result);
      $uuid = $common['uuid'];
      $name = $common['name'];
      $serial = $common['serial'];
      $otherserial = $common['otherserial']; // Asset Tag

      // Build list of mac addresses to search for
      $result = $DB->query("SELECT UPPER(n.mac) as mac
                              FROM glpi_computers c, glpi_networkports n
                              WHERE c.id=$id AND c.id=n.items_id AND itemtype='Computer'
                                AND n.instantiation_type='NetworkPortEthernet' AND n.mac<>'' 
                                AND c.is_deleted=FALSE AND n.is_deleted=false");
      $macs="MacAddress IN (";
      $values = '';
      $nbrows = 0;
      while ($line = $DB->fetch_array($result)) {
         $mac = $line['mac'];
         $macs=$macs."'".$mac."', ";
         $values = $values."('$name', '$uuid', '$serial', '$otherserial', '$mac'), ";
         $nbrows += 1;
      }
      // There should be one record per mac address in MDT, and at least one if no mac is provided.
      if ($nbrows == 0) {
         $nbrows = 1;
      }
      // $macs is a list of mac addresses ready to be included in a WHERE clause
      $macs = substr($macs, 0, -2).") ";

      //$values is a list ready to be used in a "INSERT VALUES" clause
      $values =  substr($values, 0, -2)." ";
      if ($macs ==  "MacAddress IN) ") {
         // When no mac address is defined, don't match on MacAddress in WHERE clause.
         $macs="MacAddress=''";
         $values= "('$name', '$uuid', '$serial', '$assettag', '')";
      }

      // Build list of IDs of existing records in MDT bearing same name, uuid, serial or mac adresses
      // as the computer being updated (this might clean up other bogus entries and remove duplicate names)
      // There can be several because of multiple mac addresses or duplicate names, serials....
      $query = "SELECT ID FROM dbo.ComputerIdentity 
                  WHERE (UUID<>'' AND UUID='$uuid')
                     OR (Description<>'' AND Description='$name')
                     OR (SerialNumber<>'' AND SerialNumber='$serial') 
                     OR (AssetTag<>'' AND AssetTag='$otherserial') 
                     OR (MacAddress<>'' AND $macs);";
      $result = $this->queryOrDie("$query", "Can't read IDs");

      $mdtids = "ID IN (";
      $arraymdtids = [];
      while ($line = $this->fetch_array($result)) {
         $mtdid = $line['ID'];
         $mdtids=$mdtids."'".$mtdid."', ";
         $arraymdtids[$mtdid] = $mtdid;
      }
      $mdtids = substr($mdtids, 0, -2).")";
      if ($mdtids ==  "ID IN)") {
         $mdtids="ID = ''";
      }
      $mdt['macs'] = $macs;
      $mdt['values'] = $values;
      $mdt['mdtids'] = $mdtids;
      $mdt['arraymdtids'] = $arraymdtids;
      $mdt['name'] = $name;
      $mdt['uuid'] = $uuid;
      $mdt['serial'] = $serial;
      $mdt['otherserial'] = $otherserial; //asset tag
      $mdt['nbrows'] = $nbrows;
      return $mdt;
   }
}
