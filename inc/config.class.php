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

class PluginGlpi2mdtConfig extends CommonDBTM {

   // Load plugin settings
   function loadConf() {
      global $DB;
      global $dbserver;
      global $dbport;
      global $dblogin;
      global $dbpassword;
      global $dbschema;
      global $mode;

      $dbserver='';
      $dbport=3306;
      $dblogin='';
      $dbpassowrd='';
      $dbschema='';
      $mode='Strict';

      $query = "SELECT `parameter`, `value_char`, `value_num`
                FROM `glpi_plugin_glpi2mdt_parameters`
                WHERE `is_deleted` = 0 AND `scope`= 'global'";
      $result=$DB->query($query) or die("Error loading parameters from GLPI database ". $DB->error());

      while ($data=$DB->fetch_array($result)) {
         if ($data['parameter'] == 'DBServer') {
            $dbserver=$data['value_char']; }
         if ($data['parameter'] == 'DBPort') {
            $dbport=$data['value_num']; }
         if ($data['parameter'] == 'DBLogin') {
            $dblogin=$data['value_char']; }
         if ($data['parameter'] == 'DBPassword') {
            $dbpassword=$data['value_char']; }
         if ($data['parameter'] == 'DBSchema') {
            $dbschema=$data['value_char']; }
         if ($data['parameter'] == 'Mode') {
            $mode=$data['value_char']; }
      }
   }

   // Store global plugin settings
   function updateValue($key, $value) {
      // Store configuration parameters
      global $DB;
      if ($key == 'DBServer' or $key == 'DBLogin' or $key == 'DBPassword' or $key == 'DBSchema' or $key == 'Mode') {
         $query = "INSERT INTO `glpi_plugin_glpi2mdt_parameters`
                          (`parameter`, `scope`, `value_char`, `is_deleted`)
                          VALUES ('$key', 'global', '$value', false)
                   ON DUPLICATE KEY UPDATE value_char='$value', value_num=NULL, is_deleted=false";
         $DB->query($query) or die("Error saving database server name ". $DB->error());
      }
      if ($key == 'DBPort' and $value > 0) {
         $query = "INSERT INTO `glpi_plugin_glpi2mdt_parameters`
                          (`parameter`, `scope`, `value_num`, `is_deleted`)
                          VALUES ('$key', 'global', '$value', false)
                   ON DUPLICATE KEY UPDATE value_num='$value', value_char=NULL, is_deleted=false";
         $DB->query($query) or die("Error saving database server name ". $DB->error());
      }
   }

   function show() {
      global $DB;
      global $dbserver;
      global $dbport;
      global $dblogin;
      global $dbpassword;
      global $dbschema;
      global $mode;

         ?>
           <form action="../front/config.form.php" method="post">
            <?php echo Html::hidden('_glpi_csrf_token', array('value' => Session::getNewCSRFToken())); ?>
            <div class="spaced" id="tabsbody">
                <table class="tab_cadre_fixe">
                    <tr class="tab_bg_1">
                        <td>
                              <?php echo __('Database server name', 'glpi2mdt'); ?> : &nbsp;&nbsp;&nbsp;
                        </td><td>
                              <?php echo '<input type="text" name="DBServer" value="'.$dbserver.'" size="50" class="ui-autocomplete-input" autocomplete="off"> &nbsp;&nbsp;&nbsp;' ?>
                        </td>
                    </tr>
                    <tr class="tab_bg_1">
                        <td>
                              <?php echo __('Database server port (optionnal)', 'glpi2mdt'); ?> : &nbsp;&nbsp;&nbsp;
                        </td><td>
                              <?php echo '<input type="text" name="DBPort" value="'.$dbport.'" size="50" class="ui-autocomplete-input" autocomplete="off"> &nbsp;&nbsp;&nbsp;' ?>
                        </td>
                    </tr>
                    <tr class="tab_bg_1">
                        <td>
                              <?php echo __('Login', 'glpi2mdt'); ?> : &nbsp;&nbsp;&nbsp;
                        </td><td>
                              <?php echo '<input type="text" name="DBLogin" value="'.$dblogin.'" size="50" class="ui-autocomplete-input" autocomplete="off"> &nbsp;&nbsp;&nbsp;' ?>
                        </td>
                    </tr>
                    <tr class="tab_bg_1">
                        <td>
                              <?php echo __('Password', 'glpi2mdt'); ?> : &nbsp;&nbsp;&nbsp;
                        </td><td>
                              <?php echo '<input type="text" name="DBPassword" value="'.$dbpassword.'" size="50" class="ui-autocomplete-input" autocomplete="off"> &nbsp;&nbsp;&nbsp;' ?>
                        </td>
                    </tr>
                    <tr class="tab_bg_1">
                        <td>
                              <?php echo __('Schema', 'glpi2mdt'); ?> : &nbsp;&nbsp;&nbsp;
                        </td><td>
                              <?php echo '<input type="text" name="DBSchema" value="'.$dbschema.'" size="50" class="ui-autocomplete-input" autocomplete="off"
> &nbsp;&nbsp;&nbsp;' ?>
                        </td>
                    </tr>
                    <tr class="tab_bg_1">
                        <?php
                          echo "<td>".__('Link mode', 'glpi2mdt')." :</td>";
                          echo "<td>";
                          Dropdown::showFromArray("Mode", array(
                             'Strict' => "Strict Master-Slave",
                             'Loose' => "Loose Master-Slave",
                             'Master' => "Master-Master"), array(
                             'value' => "$Mode")
                          );
                          echo "</td>";
                           ?>
                          </tr>
                          <tr class="tab_bg_1">
                           <td>
                            <input type="submit" class="submit" value="Save" name="SAVE"/>
                           </td>
                          </tr>
                          <tr class="tab_bg_1">
                           <td>
                            <input type="submit" class="submit" value="Test connection" name="TEST"/>
                           </td><td>
                            <input type="submit" class="submit" value="Initialise data" name="INIT"/>
                           </td>
                          </tr>
                      </table>
                  </div>
              </form>
               <?php
               return true;
   }

   // Test connection
   function showTestConnection() {
      global $dbserver;
      global $dbport;
      global $dblogin;
      global $dbpassword;
      global $dbschema;
      ?>
      <table class="tab_cadre_fixe">
      <tr class="tab_bg_1">
         <td>
            <?php
            // Connection to MSSQL
            $link = mssql_connect($dbserver, $dblogin, $dbpassword);

            if (!$link || !mssql_select_db($dbschema, $link)) {
                die('Impossible de se connecter à la base!');
            }

            // Simple query to get database version
            $version = mssql_query('SELECT @@VERSION');
            $row = mssql_fetch_array($version);

            echo $row[0];

            // Cleaning
            mssql_free_result($version);
            mssql_close($link);
            ?> 
         </td>
      </tr>
      </table>
      <?php
   }


   // Initialise data, load local tables from MDT MSSQL server
   function showInitialise() {
      global $DB;
      global $dbserver;
      global $dbport;
      global $dblogin;
      global $dbpassword;
      global $dbschema;

      // Connexion à MSSQL
      $link = mssql_connect($dbserver, $dblogin, $dbpassword);

      if (!$link || !mssql_select_db($dbschema, $link)) {
          die('Impossible de se connecter à la base!');
      }

      // Load available settings fields and descriptions from MDT
      $result = mssql_query('SELECT  ColumnName, CategoryOrder, Category, Description
                              FROM dbo.Descriptions');
      // There less than 300 lines, do an atomic insert/update
      while ($row = mssql_fetch_array($result)) {
         $column_name = $row['ColumnName'];
         $category_order = $row['CategoryOrder'];
         $category = $row['Category'];
         $description = $row['Description'];

         $query = "INSERT INTO `glpi_plugin_glpi2mdt_descriptions`
                    (`column_name`, `category_order`, `category`, `description`)
                    VALUES ('$column_name', $category_order, '$category', '$description')
                  ON DUPLICATE KEY UPDATE category_order=$category_order, category='$category', description='$description'";
         $DB->query($query) or die("Error loading MDT descriptions to GLPI database. ". $DB->error());
      }
      $result = mssql_query('SELECT  count(*) as nb FROM dbo.Descriptions');
      $row = mssql_fetch_array($result);
      $nb = $row['nb'];
      echo "$nb lines loaded into table 'descriptions'."."<br>";


      // Load available roles from MDT
      $result = mssql_query('SELECT  ID, Role FROM dbo.RoleIdentity');

      // Mark lines in order to detect deleted ones in the source database
      $DB->query("UPDATE `glpi_plugin_glpi2mdt_roles` SET is_in_sync=false WHERE is_deleted=false");
      while ($row = mssql_fetch_array($result)) {
         $id = $row['ID'];
         $role = $row['Role'];

         $query = "INSERT INTO `glpi_plugin_glpi2mdt_roles`
                    (`id`, `role`, `is_deleted`, `is_in_sync`)
                    VALUES ('$id', '$role', false, true)
                  ON DUPLICATE KEY UPDATE role='$role', is_deleted=false, is_in_sync=true";
         $DB->query($query) or die("Error loading MDT roles to GLPI database. ". $DB->error());
      }
      
      // Mark lines which are not in MDT anymore as deleted
      $DB->query("UPDATE `glpi_plugin_glpi2mdt_roles` SET is_in_sync=true, is_deleted=true 
                                                      WHERE is_in_sync=false AND is_deleted=false");

      $result = mssql_query('SELECT  count(*) as nb FROM dbo.RoleIdentity');
      $row = mssql_fetch_array($result);
      $nb = $row['nb'];
      echo "$nb lines loaded into table 'roles'."."<br>";


      // Load available applications from MDT
      $result = mssql_query('SELECT  Type, ID, Sequence, Applications FROM dbo.Settings_Applications');
      // Mark lines in order to detect deleted ones in the source database
      $DB->query("UPDATE `glpi_plugin_glpi2mdt_applications` SET is_in_sync=false WHERE is_deleted=false");
      while ($row = mssql_fetch_array($result)) {
         $type = $row['Type'];
         $id = $row['ID'];
         $sequence = $row['Sequence'];
         $application = $row['Applications'];

         $query = "INSERT INTO `glpi_plugin_glpi2mdt_applications`
                    (`id`, `type`, `sequence`, `application`, `is_deleted`, `is_in_sync`)
                    VALUES ('$id', '$type', '$sequence', '$application', false, true)
                  ON DUPLICATE KEY UPDATE application='$application', is_deleted=false, is_in_sync=true";
         $DB->query($query) or die("Error loading MDT applications to GLPI database. ". $DB->error());
      }
      $result = mssql_query('SELECT  count(*) as nb FROM dbo.Settings_Applications');
      $row = mssql_fetch_array($result);
      $nb = $row['nb'];
      echo "$nb lines loaded into table 'applications'."."<br>";


      // Cleaning
      mssql_free_result($result);
      mssql_close($link);
   }

}
