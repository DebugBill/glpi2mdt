<?php
/*
 -------------------------------------------------------------------------
 GLPI to MDT plugin for GLPI
 Copyright (C) 2017 by Blaise Thauvin.

 https://github.com/DebugBill/glpi2mdt
 -------------------------------------------------------------------------

 LICENSE

 This file is part of glpi2mdt.

 glpi2mdt is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 glpi2mdt is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Glpi2mdt. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Blaise Thauvin
// Purpose of file: Initialize hooks for plugin
// ----------------------------------------------------------------------

// Hook called on profile change
// Good place to evaluate the user right on this plugin
// And to save it in the session
function plugin_change_profile_glpi2mdt() {
   // For glpi2mdt : same right of computer
   if (Session::haveRight('computer', UPDATE)) {
      $_SESSION["glpi_plugin_glpi2mdt_profile"] = array('glpi2mdt' => 'w');

   } else if (Session::haveRight('computer', READ)) {
      $_SESSION["glpi_plugin_glpi2mdt_profile"] = array('glpi2mdt' => 'r');

   } else {
      unset($_SESSION["glpi_plugin_glpi2mdt_profile"]);
   }
}


// Define dropdown relations
function plugin_glpi2mdt_getDatabaseRelations() {
   return array("glpi_plugin_glpi2mdt_dropdowns" => array("glpi_plugin_glpi2mdt" => "plugin_glpi2mdt_dropdowns_id"));
}


// Define Dropdown tables to be manage in GLPI :
function plugin_glpi2mdt_getDropdown() {
   // Table => Name
   return array('PluginGlpi2mdtDropdown' => __("Plugin Glpi2mdt Dropdown", 'glpi2mdt'));
}



////// SEARCH FUNCTIONS ///////(){

// Define Additionnal search options for types (other than the plugin ones)
function plugin_glpi2mdt_getAddSearchOptions($itemtype) {
   $sopt = array();
   if ($itemtype == 'Computer') {
         // Just for glpi2mdt, not working...
         $sopt[1001]['table']     = 'glpi_plugin_glpi2mdt_dropdowns';
         $sopt[1001]['field']     = 'name';
         $sopt[1001]['linkfield'] = 'plugin_glpi2mdt_dropdowns_id';
         $sopt[1001]['name']      = __('Glpi2mdt plugin', 'glpi2mdt');
   }
   return $sopt;
}

// See also PluginGlpi2mdtGlpi2mdt::getSpecificValueToDisplay()
function plugin_glpi2mdt_giveItem($type,$ID,$data,$num) {
   $searchopt = &Search::getOptions($type);
   $table = $searchopt[$ID]["table"];
   $field = $searchopt[$ID]["field"];

   switch ($table.'.'.$field) {
      case "glpi_plugin_glpi2mdt_glpi2mdts.name" :
         $out = "<a href='".Toolbox::getItemTypeFormURL('PluginGlpi2mdtGlpi2mdt')."?id=".$data['id']."'>";
         $out .= $data[$num][0]['name'];
         if ($_SESSION["glpiis_ids_visible"] || empty($data[$num][0]['name'])) {
            $out .= " (".$data["id"].")";
         }
         $out .= "</a>";
         return $out;
   }
   return "";
}


function plugin_glpi2mdt_displayConfigItem($type, $ID, $data, $num) {
   $searchopt = &Search::getOptions($type);
   $table     = $searchopt[$ID]["table"];
   $field     = $searchopt[$ID]["field"];

   // Glpi2mdt of specific style options
   // No need of the function if you do not have specific cases
   switch ($table.'.'.$field) {
      case "glpi_plugin_glpi2mdt_glpi2mdts.name" :
         return " style=\"background-color:#DDDDDD;\" ";
   }
   return "";
}


function plugin_glpi2mdt_addDefaultJoin($type, $ref_table, &$already_link_tables) {
   // Glpi2mdt of default JOIN clause
   // No need of the function if you do not have specific cases
   switch ($type) {
      //       case "PluginGlpi2mdtGlpi2mdt" :
      case "MyType" :
         return Search::addLeftJoin($type, $ref_table, $already_link_tables,
                                    "newtable", "linkfield");
   }
   return "";
}


function plugin_glpi2mdt_addDefaultSelect($type) {
   // Glpi2mdt of default SELECT item to be added
   // No need of the function if you do not have specific cases
   switch ($type) {
      //       case "PluginGlpi2mdtGlpi2mdt" :
      case "MyType" :
         return "`mytable`.`myfield` = 'myvalue' AS MYNAME, ";
   }
   return "";
}


function plugin_glpi2mdt_addDefaultWhere($type) {
   // Glpi2mdt of default WHERE item to be added
   // No need of the function if you do not have specific cases
   switch ($type) {
      //       case "PluginGlpi2mdtGlpi2mdt" :
      case "MyType" :
         return " `mytable`.`myfield` = 'myvalue' ";
   }
   return "";
}


function plugin_glpi2mdt_addLeftJoin($type, $ref_table, $new_table, $linkfield) {
   // Glpi2mdt of standard LEFT JOIN  clause but use it ONLY for specific LEFT JOIN
   // No need of the function if you do not have specific cases
   switch ($new_table) {
      case "glpi_plugin_glpi2mdt_dropdowns" :
         return " LEFT JOIN `$new_table` ON (`$ref_table`.`$linkfield` = `$new_table`.`id`) ";
   }
   return "";
}


function plugin_glpi2mdt_forceGroupBy($type) {
   switch ($type) {
      case 'PluginGlpi2mdtGlpi2mdt' :
         // Force add GROUP BY IN REQUEST
         return true;
   }
   return false;
}


function plugin_glpi2mdt_addWhere($link, $nott, $type, $ID, $val, $searchtype) {
   $searchopt = &Search::getOptions($type);
   $table     = $searchopt[$ID]["table"];
   $field     = $searchopt[$ID]["field"];

   $SEARCH = Search::makeTextSearch($val, $nott);

   // Glpi2mdt of standard Where clause but use it ONLY for specific Where
   // No need of the function if you do not have specific cases
   switch ($table.".".$field) {
      /*case "glpi_plugin_glpi2mdt.name" :
        $ADD = "";
        if ($nott && $val!="NULL") {
           $ADD = " OR `$table`.`$field` IS NULL";
        }
        return $link." (`$table`.`$field` $SEARCH ".$ADD." ) ";*/
      case "glpi_plugin_glpi2mdt_glpi2mdts.serial" :
          return $link." `$table`.`$field` = '$val' ";
   }
   return "";
}


// This is not a real glpi2mdt because the use of Having condition in this case is not suitable
function plugin_glpi2mdt_addHaving($link, $nott, $type, $ID, $val, $num) {
   $searchopt = &Search::getOptions($type);
   $table     = $searchopt[$ID]["table"];
   $field     = $searchopt[$ID]["field"];

   $SEARCH = Search::makeTextSearch($val, $nott);

   // Glpi2mdt of standard Having clause but use it ONLY for specific Having
   // No need of the function if you do not have specific cases
   switch ($table.".".$field) {
      case "glpi_plugin_glpi2mdt.serial" :
         $ADD = "";
         if (($nott && $val!="NULL")
             || $val == '^$') {
            $ADD = " OR ITEM_$num IS NULL";
         }
         return " $LINK ( ITEM_".$num.$SEARCH." $ADD ) ";
   }
   return "";
}


function plugin_glpi2mdt_addSelect($type,$ID,$num) {
   $searchopt = &Search::getOptions($type);
   $table     = $searchopt[$ID]["table"];
   $field     = $searchopt[$ID]["field"];

   // Glpi2mdt of standard Select clause but use it ONLY for specific Select
   // No need of the function if you do not have specific cases
   // switch ($table.".".$field) {
   //    case "glpi_plugin_glpi2mdt.name" :
   //       return $table.".".$field." AS ITEM_$num, ";
   // }
   return "";
}


function plugin_glpi2mdt_addOrderBy($type,$ID,$order,$key=0) {
   $searchopt = &Search::getOptions($type);
   $table     = $searchopt[$ID]["table"];
   $field     = $searchopt[$ID]["field"];

   // Glpi2mdt of standard OrderBy clause but use it ONLY for specific order by
   // No need of the function if you do not have specific cases
   // switch ($table.".".$field) {
   //    case "glpi_plugin_glpi2mdt.name" :
   //       return " ORDER BY $table.$field $order ";
   // }
   return "";
}


//////////////////////////////
////// SPECIFIC MODIF MASSIVE FUNCTIONS ///////


// Define actions :
function plugin_glpi2mdt_MassiveActions($type) {
   switch ($type) {
      // New action for core and other plugin types : name = plugin_PLUGINNAME_actionname
      case 'Computer' :
         return array('PluginGlpi2mdtGlpi2mdt'.MassiveAction::CLASS_ACTION_SEPARATOR.'DoIt' =>
                                                              __("plugin_glpi2mdt_DoIt", 'glpi2mdt'));

      // Actions for types provided by the plugin are included inside the classes
   }
   return array();
}


// How to display specific update fields ?
// options must contain at least itemtype and options array
function plugin_glpi2mdt_MassiveActionsFieldsDisplay($options=array()) {
   //$type,$table,$field,$linkfield

   $table     = $options['options']['table'];
   $field     = $options['options']['field'];
   $linkfield = $options['options']['linkfield'];

   if ($table == getTableForItemType($options['itemtype'])) {
      // Table fields
      switch ($table.".".$field) {
         case 'glpi_plugin_glpi2mdt_glpi2mdts.serial' :
            _e("Not really specific - Just for glpi2mdt", 'glpi2mdt');
            //Html::autocompletionTextField($linkfield,$table,$field);
            // Dropdown::showYesNo($linkfield);
            // Need to return true if specific display
            return true;
      }

   } else {
      // Linked Fields
      switch ($table.".".$field) {
         case "glpi_plugin_glpi2mdt_dropdowns.name" :
            _e("Not really specific - Just for glpi2mdt", 'glpi2mdt');
            // Need to return true if specific display
            return true;
      }
   }
   // Need to return false on non display item
   return false;
}


// How to display specific search fields or dropdown ?
// options must contain at least itemtype and options array
// MUST Use a specific AddWhere & $tab[X]['searchtype'] = 'equals'; declaration
function plugin_glpi2mdt_searchOptionsValues($options=array()) {
   $table = $options['searchoption']['table'];
   $field = $options['searchoption']['field'];

    // Table fields
   switch ($table.".".$field) {
      case "glpi_plugin_glpi2mdt_glpi2mdts.serial" :
            _e("Not really specific - Use your own dropdown - Just for glpi2mdt", 'glpi2mdt');
            Dropdown::show(getItemTypeForTable($options['searchoption']['table']),
                                               array('value'    => $options['value'],
                                                     'name'     => $options['name'],
                                                     'comments' => 0));
            // Need to return true if specific display
            return true;
   }
   return false;
}


//////////////////////////////

// Hook done on before update item case
function plugin_pre_item_update_glpi2mdt($item) {
   /* Manipulate data if needed
   if (!isset($item->input['comment'])) {
      $item->input['comment'] = addslashes($item->fields['comment']);
   }
   $item->input['comment'] .= addslashes("\nUpdate: ".date('r'));
   */
   Session::addMessageAfterRedirect(__("Pre Update Computer Hook", 'glpi2mdt'), true);
}


// Hook done on update item case
function plugin_item_update_glpi2mdt($item) {
   Session::addMessageAfterRedirect(sprintf(__("Update Computer Hook (%s)", 'glpi2mdt'), implode(',', $item->updates)), true);
   return true;
}


// Hook done on get empty item case
function plugin_item_empty_glpi2mdt($item) {
   if (empty($_SESSION['Already displayed "Empty Computer Hook"'])) {
      Session::addMessageAfterRedirect(__("Empty Computer Hook", 'glpi2mdt'), true);
      $_SESSION['Already displayed "Empty Computer Hook"'] = true;
   }
   return true;
}


// Hook done on before delete item case
function plugin_pre_item_delete_glpi2mdt($object) {
   // Manipulate data if needed
   Session::addMessageAfterRedirect(__("Pre Delete Computer Hook", 'glpi2mdt'), true);
}


// Hook done on delete item case
function plugin_item_delete_glpi2mdt($object) {
   Session::addMessageAfterRedirect(__("Delete Computer Hook", 'glpi2mdt'), true);
   return true;
}


// Hook done on before purge item case
function plugin_pre_item_purge_glpi2mdt($object) {
   // Manipulate data if needed
   Session::addMessageAfterRedirect(__("Pre Purge Computer Hook", 'glpi2mdt'), true);
}


// Hook done on purge item case
function plugin_item_purge_glpi2mdt($object) {
   Session::addMessageAfterRedirect(__("Purge Computer Hook", 'glpi2mdt'), true);
   return true;
}


// Hook done on before restore item case
function plugin_pre_item_restore_glpi2mdt($item) {
   // Manipulate data if needed
   Session::addMessageAfterRedirect(__("Pre Restore Computer Hook", 'glpi2mdt'));
}


// Hook done on before restore item case
function plugin_pre_item_restore_glpi2mdt2($item) {
   // Manipulate data if needed
   Session::addMessageAfterRedirect(__("Pre Restore Phone Hook", 'glpi2mdt'));
}


// Hook done on restore item case
function plugin_item_restore_glpi2mdt($item) {
   Session::addMessageAfterRedirect(__("Restore Computer Hook", 'glpi2mdt'));
   return true;
}


// Hook done on restore item case
function plugin_item_transfer_glpi2mdt($parm) {
   //TRANS: %1$s is the source type, %2$d is the source ID, %3$d is the destination ID
   Session::addMessageAfterRedirect(sprintf(__('Transfer Computer Hook %1$s %2$d -> %3$d', 'glpi2mdt'), $parm['type'], $parm['id'],
                                     $parm['newID']));

   return false;
}

// Do special actions for dynamic report
function plugin_glpi2mdt_dynamicReport($parm) {
   if ($parm["item_type"] == 'PluginGlpi2mdtGlpi2mdt') {
      // Do all what you want for export depending on $parm
      echo "Personalized export for type ".$parm["display_type"];
      echo 'with additional datas : <br>';
      echo "Single data : add1 <br>";
      print $parm['add1'].'<br>';
      echo "Array data : add2 <br>";
      Html::printCleanArray($parm['add2']);
      // Return true if personalized display is done
      return true;
   }
   // Return false if no specific display is done, then use standard display
   return false;
}


// Add parameters to Html::printPager in search system
function plugin_glpi2mdt_addParamFordynamicReport($itemtype) {
   if ($itemtype == 'PluginGlpi2mdtGlpi2mdt') {
      // Return array data containing all params to add : may be single data or array data
      // Search config are available from session variable
      return array('add1' => $_SESSION['glpisearch'][$itemtype]['order'],
                   'add2' => array('tutu' => 'Second Add',
                                   'Other Data'));
   }
   // Return false or a non array data if not needed
   return false;
}


/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_glpi2mdt_install() {
   global $DB;

   $config = new Config();
   $config->setConfigurationValues('plugin:Glpi2mdt', array('configuration' => false));

   ProfileRight::addProfileRights(array('glpi2mdt:read'));

   if (!TableExists("glpi_plugin_glpi2mdt_glpi2mdts")) {
      $query = "CREATE TABLE `glpi_plugin_glpi2mdt_glpi2mdts` (
                  `id` int(11) NOT NULL auto_increment,
                  `name` varchar(255) collate utf8_unicode_ci default NULL,
                  `serial` varchar(255) collate utf8_unicode_ci NOT NULL,
                  `plugin_glpi2mdt_dropdowns_id` int(11) NOT NULL default '0',
                  `is_deleted` tinyint(1) NOT NULL default '0',
                  `is_template` tinyint(1) NOT NULL default '0',
                  `template_name` varchar(255) collate utf8_unicode_ci default NULL,
                PRIMARY KEY (`id`)
               ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->query($query) or die("error creating glpi_plugin_glpi2mdt_glpi2mdts ". $DB->error());

      $query = "INSERT INTO `glpi_plugin_glpi2mdt_glpi2mdts`
                       (`id`, `name`, `serial`, `plugin_glpi2mdt_dropdowns_id`, `is_deleted`,
                        `is_template`, `template_name`)
                VALUES (1, 'glpi2mdt 1', 'serial 1', 1, 0, 0, NULL),
                       (2, 'glpi2mdt 2', 'serial 2', 2, 0, 0, NULL),
                       (3, 'glpi2mdt 3', 'serial 3', 1, 0, 0, NULL)";
      $DB->query($query) or die("error populate glpi_plugin_glpi2mdt ". $DB->error());
   }

   if (!TableExists("glpi_plugin_glpi2mdt_dropdowns")) {
      $query = "CREATE TABLE `glpi_plugin_glpi2mdt_dropdowns` (
                  `id` int(11) NOT NULL auto_increment,
                  `name` varchar(255) collate utf8_unicode_ci default NULL,
                  `comment` text collate utf8_unicode_ci,
                PRIMARY KEY  (`id`),
                KEY `name` (`name`)
               ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->query($query) or die("error creating glpi_plugin_glpi2mdt_dropdowns". $DB->error());

      $query = "INSERT INTO `glpi_plugin_glpi2mdt_dropdowns`
                       (`id`, `name`, `comment`)
                VALUES (1, 'dp 1', 'comment 1'),
                       (2, 'dp2', 'comment 2')";

      $DB->query($query) or die("error populate glpi_plugin_glpi2mdt_dropdowns". $DB->error());

   }

   if (!TableExists('glpi_plugin_glpi2mdt_devicecameras')) {
      $query = "CREATE TABLE `glpi_plugin_glpi2mdt_devicecameras` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `designation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `manufacturers_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  KEY `designation` (`designation`),
                  KEY `manufacturers_id` (`manufacturers_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->query($query) or die("error creating glpi_plugin_glpi2mdt_glpi2mdts ". $DB->error());
   }

   if (!TableExists('glpi_plugin_glpi2mdt_items_devicecameras')) {
      $query = "CREATE TABLE `glpi_plugin_glpi2mdt_items_devicecameras` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `items_id` int(11) NOT NULL DEFAULT '0',
                  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `plugin_glpi2mdt_devicecameras_id` int(11) NOT NULL DEFAULT '0',
                  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
                  `is_dynamic` tinyint(1) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  KEY `computers_id` (`items_id`),
                  KEY `plugin_glpi2mdt_devicecameras_id` (`plugin_glpi2mdt_devicecameras_id`),
                  KEY `is_deleted` (`is_deleted`),
                  KEY `is_dynamic` (`is_dynamic`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->query($query) or die("error creating glpi_plugin_glpi2mdt_glpi2mdts ". $DB->error());
   }

   // To be called for each task the plugin manage
   // task in class
   CronTask::Register('PluginGlpi2mdtGlpi2mdt', 'Sample', DAY_TIMESTAMP, array('param' => 50));
   return true;
}


/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_glpi2mdt_uninstall() {
   global $DB;

   $config = new Config();
   $config->deleteConfigurationValues('plugin:Glpi2mdt', array('configuration' => false));

   ProfileRight::deleteProfileRights(array('glpi2mdt:read'));

   $notif = new Notification();
   $options = array('itemtype' => 'Ticket',
                    'event'    => 'plugin_glpi2mdt',
                    'FIELDS'   => 'id');
   foreach ($DB->request('glpi_notifications', $options) as $data) {
      $notif->delete($data);
   }
   // Old version tables
   if (TableExists("glpi_dropdown_plugin_glpi2mdt")) {
      $query = "DROP TABLE `glpi_dropdown_plugin_glpi2mdt`";
      $DB->query($query) or die("error deleting glpi_dropdown_plugin_glpi2mdt");
   }
   if (TableExists("glpi_plugin_glpi2mdt")) {
      $query = "DROP TABLE `glpi_plugin_glpi2mdt`";
      $DB->query($query) or die("error deleting glpi_plugin_glpi2mdt");
   }
   // Current version tables
   if (TableExists("glpi_plugin_glpi2mdt_glpi2mdt")) {
      $query = "DROP TABLE `glpi_plugin_glpi2mdt_glpi2mdt`";
      $DB->query($query) or die("error deleting glpi_plugin_glpi2mdt_glpi2mdt");
   }
   if (TableExists("glpi_plugin_glpi2mdt_dropdowns")) {
      $query = "DROP TABLE `glpi_plugin_glpi2mdt_dropdowns`;";
      $DB->query($query) or die("error deleting glpi_plugin_glpi2mdt_dropdowns");
   }
   if (TableExists("glpi_plugin_glpi2mdt_devicecameras")) {
      $query = "DROP TABLE `glpi_plugin_glpi2mdt_devicecameras`;";
      $DB->query($query) or die("error deleting glpi_plugin_glpi2mdt_devicecameras");
   }
   if (TableExists("glpi_plugin_glpi2mdt_items_devicecameras")) {
      $query = "DROP TABLE `glpi_plugin_glpi2mdt_items_devicecameras`;";
      $DB->query($query) or die("error deleting glpi_plugin_glpi2mdt_items_devicecameras");
   }
   return true;
}


function plugin_glpi2mdt_AssignToTicket($types) {
   $types['PluginGlpi2mdtGlpi2mdt'] = "Glpi2mdt";
   return $types;
}


function plugin_glpi2mdt_get_events(NotificationTargetTicket $target) {
   $target->events['plugin_glpi2mdt'] = __("Glpi2mdt event", 'glpi2mdt');
}


function plugin_glpi2mdt_get_datas(NotificationTargetTicket $target) {
   $target->datas['##ticket.glpi2mdt##'] = __("Glpi2mdt datas", 'glpi2mdt');
}


function plugin_glpi2mdt_postinit() {
   global $CFG_GLPI;

   // All plugins are initialized, so all types are registered
   //foreach (Infocom::getItemtypesThatCanHave() as $type) {
      // do something
   //}
}


/**
 * Hook to add more data from ldap
 * fields from plugin_retrieve_more_field_from_ldap_glpi2mdt
 *
 * @param $datas   array
 *
 * @return un tableau
 **/
function plugin_retrieve_more_data_from_ldap_glpi2mdt(array $datas) {
   return $datas;
}


/**
 * Hook to add more fields from LDAP
 *
 * @param $fields   array
 *
 * @return un tableau
 **/
function plugin_retrieve_more_field_from_ldap_glpi2mdt($fields) {
   return $fields;
}

// Check to add to status page
function plugin_glpi2mdt_Status($param) {
   // Do checks (no check for glpi2mdt)
   $ok = true;
   echo "glpi2mdt plugin: glpi2mdt";
   if ($ok) {
      echo "_OK";
   } else {
      echo "_PROBLEM";
      // Only set ok to false if trouble (global status)
      $param['ok'] = false;
   }
   echo "\n";
   return $param;
}

function plugin_glpi2mdt_display_central() {
   echo "<tr><th colspan='2'>";
   echo "<div style='text-align:center; font-size:2em'>";
   echo __("Plugin glpi2mdt displays on central page", "glpi2mdt");
   echo "</div>";
   echo "</th></tr>";
}

function plugin_glpi2mdt_display_login() {
   echo "<div style='text-align:center; font-size:2em'>";
   echo __("Plugin glpi2mdt displays on login page", "glpi2mdt");
   echo "</div>";
}

function plugin_glpi2mdt_infocom_hook($params) {
   echo "<tr><th colspan='4'>";
   echo __("Plugin glpi2mdt displays on central page", "glpi2mdt");
   echo "</th></tr>";
}
