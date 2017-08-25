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
// Purpose of file: Main plugin class
// ----------------------------------------------------------------------

// Class of the defined type
class PluginGlpi2mdtGlpi2mdt extends CommonDBTM {

   static $tags = '[EXAMPLE_ID]';

   // Should return the localized name of the type
   static function getTypeName($nb = 0) {
      return 'Glpi2mdt Type';
   }


   static function canCreate() {

      if (isset($_SESSION["glpi_plugin_glpi2mdt_profile"])) {
         return ($_SESSION["glpi_plugin_glpi2mdt_profile"]['glpi2mdt'] == 'w');
      }
      return false;
   }


   static function canView() {

      if (isset($_SESSION["glpi_plugin_glpi2mdt_profile"])) {
         return ($_SESSION["glpi_plugin_glpi2mdt_profile"]['glpi2mdt'] == 'w'
                 || $_SESSION["glpi_plugin_glpi2mdt_profile"]['glpi2mdt'] == 'r');
      }
      return false;
   }


   /**
    * @see CommonGLPI::getMenuName()
   **/
   static function getMenuName() {
      return __('Glpi2mdt plugin');
   }


   /**
    * @see CommonGLPI::getAdditionalMenuLinks()
   **/
   static function getAdditionalMenuLinks() {
      global $CFG_GLPI;
      $links = array();

      $links['config'] = '/plugins/glpi2mdt/index.php';
      $links["<img  src='".$CFG_GLPI["root_doc"]."/pics/menu_showall.png' title='".__s('Show all')."' alt='".__s('Show all')."'>"] = '/plugins/glpi2mdt/index.php';
      $links[__s('Test link', 'glpi2mdt')] = '/plugins/glpi2mdt/index.php';

      return $links;
   }

   function defineTabs($options = array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Link', $ong, $options);

      return $ong;
   }

   function showForm($ID, $options = array()) {
      global $CFG_GLPI;

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";

      echo "<td>" . __('ID') . "</td>";
      echo "<td>";
      echo $ID;
      echo "</td>";

      $this->showFormButtons($options);

      return true;
   }

   function getSearchOptions() {

      $tab = array();
      $tab['common'] = "Header Needed";

      $tab[1]['table']     = 'glpi_plugin_glpi2mdt_glpi2mdts';
      $tab[1]['field']     = 'name';
      $tab[1]['name']      = __('Name');

      $tab[2]['table']     = 'glpi_plugin_glpi2mdt_dropdowns';
      $tab[2]['field']     = 'name';
      $tab[2]['name']      = __('Dropdown');

      $tab[3]['table']     = 'glpi_plugin_glpi2mdt_glpi2mdts';
      $tab[3]['field']     = 'serial';
      $tab[3]['name']      = __('Serial number');
      $tab[3]['usehaving'] = true;
      $tab[3]['searchtype'] = 'equals';

      $tab[30]['table']     = 'glpi_plugin_glpi2mdt_glpi2mdts';
      $tab[30]['field']     = 'id';
      $tab[30]['name']      = __('ID');

      return $tab;
   }


   /**
    * Give localized information about 1 task
    *
    * @param $name of the task
    *
    * @return array of strings
    */
   static function cronInfo($name) {

      switch ($name) {
         case 'Sample' :
            return array('description' => __('Cron description for glpi2mdt', 'glpi2mdt'),
                         'parameter'   => __('Cron parameter for glpi2mdt', 'glpi2mdt'));
      }
      return array();
   }


   /**
    * Execute 1 task manage by the plugin
    *
    * @param $task Object of CronTask class for log / stat
    *
    * @return interger
    *    >0 : done
    *    <0 : to be run again (not finished)
    *     0 : nothing to do
    */
   static function cronSample($task) {

      $task->log("Glpi2mdt log message from class");
      $r = mt_rand(0, $task->fields['param']);
      usleep(1000000+$r*1000);
      $task->setVolume($r);

      return 1;
   }


   // Hook done on before add item case (data from form, not altered)
   static function pre_item_add_computer(Computer $item) {
      if (isset($item->input['name']) && empty($item->input['name'])) {
         Session::addMessageAfterRedirect("Pre Add Computer Hook KO (name empty)", true);
         return $item->input = false;
      } else {
         Session::addMessageAfterRedirect("Pre Add Computer Hook OK", true);
      }
   }

   // Hook done on before add item case (data altered by object prepareInputForAdd)
   static function post_prepareadd_computer(Computer$item) {
      Session::addMessageAfterRedirect("Post prepareAdd Computer Hook", true);
   }


   // Hook done on add item case
   static function item_add_computer(Computer$item) {

      Session::addMessageAfterRedirect("Add Computer Hook, ID=".$item->getID(), true);
      return true;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         switch ($item->getType()) {
            case 'Profile' :
               if ($item->getField('central')) {
                  return __('Glpi2mdt', 'glpi2mdt');
               }
               break;

            case 'Phone' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry(__('Glpi2mdt', 'glpi2mdt'),
                                              countElementsInTable($this->getTable()));
               }
               return __('Glpi2mdt', 'glpi2mdt');

            case 'ComputerDisk' :
            case 'Supplier' :
               return array(1 => __("Test Plugin", 'glpi2mdt'),
                            2 => __("Test Plugin 2", 'glpi2mdt'));

            case 'Computer' :
            case 'Central' :
            case 'Preference':
            case 'Notification':
               return array(1 => __("Test Plugin", 'glpi2mdt'));

         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'Phone' :
            _e("Plugin Glpi2mdt on Phone", 'glpi2mdt');
            break;

         case 'Central' :
            _e("Plugin central action", 'glpi2mdt');
            break;

         case 'Preference' :
            // Complete form display
            $data = plugin_version_glpi2mdt();

            echo "<form action='Where to post form'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th colspan='3'>".$data['name']." - ".$data['version'];
            echo "</th></tr>";

            echo "<tr class='tab_bg_1'><td>Name of the pref</td>";
            echo "<td>Input to set the pref</td>";

            echo "<td><input class='submit' type='submit' name='submit' value='submit'></td>";
            echo "</tr>";

            echo "</table>";
            echo "</form>";
            break;

         case 'Notification' :
            _e("Plugin mailing action", 'glpi2mdt');
            break;

         case 'ComputerDisk' :
         case 'Supplier' :
            if ($tabnum==1) {
               _e('First tab of Plugin glpi2mdt', 'glpi2mdt');
            } else {
               _e('Second tab of Plugin glpi2mdt', 'glpi2mdt');
            }
            break;

         default :
            //TRANS: %1$s is a class name, %2$d is an item ID
            printf(__('Plugin glpi2mdt CLASS=%1$s id=%2$d', 'glpi2mdt'), $item->getType(), $item->getField('id'));
            break;
      }
      return true;
   }

   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      switch ($field) {
         case 'serial' :
            return "S/N: ".$values[$field];
      }
      return '';
   }

   // Parm contains begin, end and who
   // Create data to be displayed in the planning of $parm["who"] or $parm["who_group"] between $parm["begin"] and $parm["end"]
   static function populatePlanning($parm) {

      // Add items in the output array
      // Items need to have an unique index beginning by the begin date of the item to display
      // needed to be correcly displayed
      $output = array();
      $key = $parm["begin"]."$$$"."plugin_glpi2mdt1";
      $output[$key]["begin"]  = date("Y-m-d 17:00:00");
      $output[$key]["end"]    = date("Y-m-d 18:00:00");
      $output[$key]["name"]   = __("test planning glpi2mdt 1", 'glpi2mdt');
      // Specify the itemtype to be able to use specific display system
      $output[$key]["itemtype"] = "PluginGlpi2mdtGlpi2mdt";
      // Set the ID using the ID of the item in the database to have unique ID
      $output[$key][getForeignKeyFieldForItemType('PluginGlpi2mdtGlpi2mdt')] = 1;
      return $output;
   }

   /**
    * Display a Planning Item
    *
    * @param $val Array of the item to display
    * @param $who ID of the user (0 if all)
    * @param $type position of the item in the time block (in, through, begin or end)
    * @param $complete complete display (more details)
    *
    * @return Nothing (display function)
    **/
   static function displayPlanningItem(array $val, $who, $type="", $complete=0) {

      // $parm["type"] say begin end in or from type
      // Add items in the items fields of the parm array
      switch ($type) {
         case "in" :
            //TRANS: %1$s is the start time of a planned item, %2$s is the end
            printf(__('From %1$s to %2$s :'),
                   date("H:i", strtotime($val["begin"])), date("H:i", strtotime($val["end"])));
            break;

         case "through" :
            echo Html::resume_text($val["name"], 80);
            break;

         case "begin" :
            //TRANS: %s is the start time of a planned item
            printf(__('Start at %s:'), date("H:i", strtotime($val["begin"])));
            break;

         case "end" :
            //TRANS: %s is the end time of a planned item
            printf(__('End at %s:'), date("H:i", strtotime($val["end"])));
         break;
      }
      echo "<br>";
      echo Html::resume_text($val["name"], 80);
   }

   /**
    * Get an history entry message
    *
    * @param $data Array from glpi_logs table
    *
    * @since GLPI version 0.84
    *
    * @return string
   **/
   static function getHistoryEntry($data) {

      switch ($data['linked_action'] - Log::HISTORY_PLUGIN) {
         case 0:
            return __('History from plugin glpi2mdt', 'glpi2mdt');
      }

      return '';
   }


   //////////////////////////////
   ////// SPECIFIC MODIF MASSIVE FUNCTIONS ///////
   /**
    * @since version 0.85
    *
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem=NULL) {

      $actions = parent::getSpecificMassiveActions($checkitem);

      $actions['Document_Item'.MassiveAction::CLASS_ACTION_SEPARATOR.'add']  =
                                        _x('button', 'Add a document');         // GLPI core one
      $actions[__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'do_nothing'] =
                                        __('Do Nothing - just for fun', 'glpi2mdt');  // Specific one

      return $actions;
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
   **/
   static function showMassiveActionsSubForm(MassiveAction $ma) {

      switch ($ma->getAction()) {
         case 'DoIt':
            echo "&nbsp;<input type='hidden' name='toto' value='1'>".
                 Html::submit(_x('button', 'Post'), array('name' => 'massiveaction')).
                 " ".__('Write in item history', 'glpi2mdt');
            return true;
         case 'do_nothing' :
            echo "&nbsp;".Html::submit(_x('button', 'Post'), array('name' => 'massiveaction')).
                 " ".__('but do nothing :)', 'glpi2mdt');
            return true;
      }
      return parent::showMassiveActionsSubForm($ma);
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {
      global $DB;

      switch ($ma->getAction()) {
         case 'DoIt' :
            if ($item->getType() == 'Computer') {
               Session::addMessageAfterRedirect(__("Right it is the type I want...", 'glpi2mdt'));
               Session::addMessageAfterRedirect(__('Write in item history', 'glpi2mdt'));
               $changes = array(0, 'old value', 'new value');
               foreach ($ids as $id) {
                  if ($item->getFromDB($id)) {
                     Session::addMessageAfterRedirect("- ".$item->getField("name"));
                     Log::history($id, 'Computer', $changes, 'PluginGlpi2mdtGlpi2mdt',
                                  Log::HISTORY_PLUGIN);
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                  } else {
                     // Glpi2mdt of ko count
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                  }
               }
            } else {
               // When nothing is possible ...
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
            }
            return;

         case 'do_nothing' :
            If ($item->getType() == 'PluginGlpi2mdtGlpi2mdt') {
               Session::addMessageAfterRedirect(__("Right it is the type I want...", 'glpi2mdt'));
               Session::addMessageAfterRedirect(__("But... I say I will do nothing for:",
                                                   'glpi2mdt'));
               foreach ($ids as $id) {
                  if ($item->getFromDB($id)) {
                     Session::addMessageAfterRedirect("- ".$item->getField("name"));
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                  } else {
                     // Glpi2mdt for noright / Maybe do it with can function is better
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                  }
               }
            } else {
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
            }
            Return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }

   static function generateLinkContents($link, CommonDBTM $item) {

      if (strstr($link, "[EXAMPLE_ID]")) {
         $link = str_replace("[EXAMPLE_ID]", $item->getID(), $link);
         return array($link);
      }

      return parent::generateLinkContents($link, $item);
   }

}
