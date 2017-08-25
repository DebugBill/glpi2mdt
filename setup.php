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
// Purpose of file: Plugin initialization
// ----------------------------------------------------------------------

define ('PLUGIN_GLPI2MDT_VERSION', '7.1');

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_glpi2mdt() {
   global $PLUGIN_HOOKS,$CFG_GLPI;

   // Params : plugin name - string type - ID - Array of attributes
   // No specific information passed so not needed
   //Plugin::registerClass('PluginGlpi2mdtGlpi2mdt',
   //                      array('classname'              => 'PluginGlpi2mdtGlpi2mdt',
   //                        ));

   Plugin::registerClass('PluginGlpi2mdtConfig', array('addtabon' => 'Config'));

   // Params : plugin name - string type - ID - Array of attributes
   Plugin::registerClass('PluginGlpi2mdtDropdown');

   $types = array('Central', 'Computer', 'ComputerDisk', 'Notification', 'Phone',
                  'Preference', 'Profile', 'Supplier');
   Plugin::registerClass('PluginGlpi2mdtGlpi2mdt',
                         array('notificationtemplates_types' => true,
                               'addtabon'                    => $types,
                              'link_types' => true));

   Plugin::registerClass('PluginGlpi2mdtRuleTestCollection',
                         array('rulecollections_types' => true));

   Plugin::registerClass('PluginGlpi2mdtDeviceCamera',
                         array('device_types' => true));

   if (version_compare(GLPI_VERSION, '9.1', 'ge')) {
      if (class_exists('PluginGlpi2mdtGlpi2mdt')) {
         Link::registerTag(PluginGlpi2mdtGlpi2mdt::$tags);
      }
   }
   // Display a menu entry ?
   $_SESSION["glpi_plugin_glpi2mdt_profile"]['glpi2mdt'] = 'w';
   if (isset($_SESSION["glpi_plugin_glpi2mdt_profile"])) { // Right set in change_profile hook
      $PLUGIN_HOOKS['menu_toadd']['glpi2mdt'] = array('plugins' => 'PluginGlpi2mdtGlpi2mdt',
                                                     'tools'   => 'PluginGlpi2mdtGlpi2mdt');

      // Old menu style
      //       $PLUGIN_HOOKS['menu_entry']['glpi2mdt'] = 'front/glpi2mdt.php';
      //
      //       $PLUGIN_HOOKS['submenu_entry']['glpi2mdt']['options']['optionname']['title'] = "Search";
      //       $PLUGIN_HOOKS['submenu_entry']['glpi2mdt']['options']['optionname']['page']  = '/plugins/glpi2mdt/front/glpi2mdt.php';
      //       $PLUGIN_HOOKS['submenu_entry']['glpi2mdt']['options']['optionname']['links']['search'] = '/plugins/glpi2mdt/front/glpi2mdt.php';
      //       $PLUGIN_HOOKS['submenu_entry']['glpi2mdt']['options']['optionname']['links']['add']    = '/plugins/glpi2mdt/front/glpi2mdt.form.php';
      //       $PLUGIN_HOOKS['submenu_entry']['glpi2mdt']['options']['optionname']['links']['config'] = '/plugins/glpi2mdt/index.php';
      //       $PLUGIN_HOOKS['submenu_entry']['glpi2mdt']['options']['optionname']['links']["<img  src='".$CFG_GLPI["root_doc"]."/pics/menu_showall.png' title='".__s('Show all')."' alt='".__s('Show all')."'>"] = '/plugins/glpi2mdt/index.php';
      //       $PLUGIN_HOOKS['submenu_entry']['glpi2mdt']['options']['optionname']['links'][__s('Test link', 'glpi2mdt')] = '/plugins/glpi2mdt/index.php';

      $PLUGIN_HOOKS["helpdesk_menu_entry"]['glpi2mdt'] = true;
   }

   // Config page
   if (Session::haveRight('config', UPDATE)) {
      $PLUGIN_HOOKS['config_page']['glpi2mdt'] = 'config.php';
   }

   // Init session
   //$PLUGIN_HOOKS['init_session']['glpi2mdt'] = 'plugin_init_session_glpi2mdt';
   // Change profile
   $PLUGIN_HOOKS['change_profile']['glpi2mdt'] = 'plugin_change_profile_glpi2mdt';
   // Change entity
   //$PLUGIN_HOOKS['change_entity']['glpi2mdt'] = 'plugin_change_entity_glpi2mdt';

   // Item action event // See define.php for defined ITEM_TYPE
   $PLUGIN_HOOKS['pre_item_update']['glpi2mdt'] = array('Computer' => 'plugin_pre_item_update_glpi2mdt');
   $PLUGIN_HOOKS['item_update']['glpi2mdt']     = array('Computer' => 'plugin_item_update_glpi2mdt');

   $PLUGIN_HOOKS['item_empty']['glpi2mdt']      = array('Computer' => 'plugin_item_empty_glpi2mdt');

   // Restrict right
   $PLUGIN_HOOKS['item_can']['glpi2mdt']        = ['Computer' => ['PluginGlpi2mdtComputer', 'restrict']];

   // Glpi2mdt using a method in class
   $PLUGIN_HOOKS['pre_item_add']['glpi2mdt']    = array('Computer' => array('PluginGlpi2mdtGlpi2mdt',
                                                                           'pre_item_add_computer'));
   $PLUGIN_HOOKS['post_prepareadd']['glpi2mdt'] = array('Computer' => array('PluginGlpi2mdtGlpi2mdt',
                                                                           'post_prepareadd_computer'));
   $PLUGIN_HOOKS['item_add']['glpi2mdt']        = array('Computer' => array('PluginGlpi2mdtGlpi2mdt',
                                                                           'item_add_computer'));

   $PLUGIN_HOOKS['pre_item_delete']['glpi2mdt'] = array('Computer' => 'plugin_pre_item_delete_glpi2mdt');
   $PLUGIN_HOOKS['item_delete']['glpi2mdt']     = array('Computer' => 'plugin_item_delete_glpi2mdt');

   // Glpi2mdt using the same function
   $PLUGIN_HOOKS['pre_item_purge']['glpi2mdt'] = array('Computer' => 'plugin_pre_item_purge_glpi2mdt',
                                                      'Phone'    => 'plugin_pre_item_purge_glpi2mdt');
   $PLUGIN_HOOKS['item_purge']['glpi2mdt']     = array('Computer' => 'plugin_item_purge_glpi2mdt',
                                                      'Phone'    => 'plugin_item_purge_glpi2mdt');

   // Glpi2mdt with 2 different functions
   $PLUGIN_HOOKS['pre_item_restore']['glpi2mdt'] = array('Computer' => 'plugin_pre_item_restore_glpi2mdt',
                                                         'Phone'   => 'plugin_pre_item_restore_glpi2mdt2');
   $PLUGIN_HOOKS['item_restore']['glpi2mdt']     = array('Computer' => 'plugin_item_restore_glpi2mdt');

   // Add event to GLPI core itemtype, event will be raised by the plugin.
   // See plugin_glpi2mdt_uninstall for cleanup of notification
   $PLUGIN_HOOKS['item_get_events']['glpi2mdt']
                                 = array('NotificationTargetTicket' => 'plugin_glpi2mdt_get_events');

   // Add datas to GLPI core itemtype for notifications template.
   $PLUGIN_HOOKS['item_get_datas']['glpi2mdt']
                                 = array('NotificationTargetTicket' => 'plugin_glpi2mdt_get_datas');

   $PLUGIN_HOOKS['item_transfer']['glpi2mdt'] = 'plugin_item_transfer_glpi2mdt';

   // function to populate planning
   // No more used since GLPI 0.84
   // $PLUGIN_HOOKS['planning_populate']['glpi2mdt'] = 'plugin_planning_populate_glpi2mdt';
   // Use instead : add class to planning types and define populatePlanning in class
   $CFG_GLPI['planning_types'][] = 'PluginGlpi2mdtGlpi2mdt';

   //function to display planning items
   // No more used sinc GLPi 0.84
   // $PLUGIN_HOOKS['display_planning']['glpi2mdt'] = 'plugin_display_planning_glpi2mdt';
   // Use instead : displayPlanningItem of the specific itemtype

   // Massive Action definition
   $PLUGIN_HOOKS['use_massive_action']['glpi2mdt'] = 1;

   $PLUGIN_HOOKS['assign_to_ticket']['glpi2mdt'] = 1;

   // Add specific files to add to the header : javascript or css
   $PLUGIN_HOOKS['add_javascript']['glpi2mdt'] = 'glpi2mdt.js';
   $PLUGIN_HOOKS['add_css']['glpi2mdt']        = 'glpi2mdt.css';

   // request more attributes from ldap
   //$PLUGIN_HOOKS['retrieve_more_field_from_ldap']['glpi2mdt']="plugin_retrieve_more_field_from_ldap_glpi2mdt";

   // Retrieve others datas from LDAP
   //$PLUGIN_HOOKS['retrieve_more_data_from_ldap']['glpi2mdt']="plugin_retrieve_more_data_from_ldap_glpi2mdt";

   // Reports
   $PLUGIN_HOOKS['reports']['glpi2mdt'] = array('report.php'       => 'New Report',
                                               'report.php?other' => 'New Report 2');

   // Stats
   $PLUGIN_HOOKS['stats']['glpi2mdt'] = array('stat.php'       => 'New stat',
                                             'stat.php?other' => 'New stats 2',);

   $PLUGIN_HOOKS['post_init']['glpi2mdt'] = 'plugin_glpi2mdt_postinit';

   $PLUGIN_HOOKS['status']['glpi2mdt'] = 'plugin_glpi2mdt_Status';

   // CSRF compliance : All actions must be done via POST and forms closed by Html::closeForm();
   $PLUGIN_HOOKS['csrf_compliant']['glpi2mdt'] = true;

   $PLUGIN_HOOKS['display_central']['glpi2mdt'] = "plugin_glpi2mdt_display_central";
   $PLUGIN_HOOKS['display_login']['glpi2mdt'] = "plugin_glpi2mdt_display_login";
   $PLUGIN_HOOKS['infocom']['glpi2mdt'] = "plugin_glpi2mdt_infocom_hook";

   // pre_show and post_show for tabs and items,
   // see PluginGlpi2mdtShowtabitem class for implementation explanations
   $PLUGIN_HOOKS['pre_show_tab']['glpi2mdt']     = array('PluginGlpi2mdtShowtabitem', 'pre_show_tab');
   $PLUGIN_HOOKS['post_show_tab']['glpi2mdt']    = array('PluginGlpi2mdtShowtabitem', 'post_show_tab');
   $PLUGIN_HOOKS['pre_show_item']['glpi2mdt']    = array('PluginGlpi2mdtShowtabitem', 'pre_show_item');
   $PLUGIN_HOOKS['post_show_item']['glpi2mdt']   = array('PluginGlpi2mdtShowtabitem', 'post_show_item');

   $PLUGIN_HOOKS['pre_item_form']['glpi2mdt']    = ['PluginGlpi2mdtItemForm', 'preItemForm'];
   $PLUGIN_HOOKS['post_item_form']['glpi2mdt']   = ['PluginGlpi2mdtItemForm', 'postItemForm'];

   // declare this plugin as an import plugin for Computer itemtype
   $PLUGIN_HOOKS['import_item']['exemple'] = array('Computer' => array('Plugin'));

   // add additional informations on Computer::showForm
   $PLUGIN_HOOKS['autoinventory_information']['exemple'] =  array(
      'Computer' =>  array('PluginGlpi2mdtComputer', 'showInfo')
   );

}


/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_glpi2mdt() {

   return array('name'           => 'Plugin Glpi2mdt',
                'version'        => PLUGIN_GLPI2MDT_VERSION,
                'author'         => 'Blaise Thauvin',
                'license'        => 'GPLv2+',
                'homepage'       => 'https://github.com/DebugBill/glpi2mdt',
                'minGlpiVersion' => '9.1.1');// For compatibility / no install in version < 9.1.1
}


/**
 * Check pre-requisites before install
 * OPTIONNAL, but recommanded
 *
 * @return boolean
 */
function plugin_glpi2mdt_check_prerequisites() {

   // Strict version check (could be less strict, or could allow various version)
   if (version_compare(GLPI_VERSION, '9.1.1', 'lt') /*|| version_compare(GLPI_VERSION,'9.1.0','gt')*/) {
      if (method_exists('Plugin', 'messageIncompatible')) {
         echo Plugin::messageIncompatible('core', '9.1.1');
      } else {
         echo "This plugin requires GLPI >= 9.1.1";
      }
      return false;
   }
   return true;
}

/**
 * Check configuration process
 *
 * @param boolean $verbose Whether to display message on failure. Defaults to false
 *
 * @return boolean
 */
function plugin_glpi2mdt_check_config($verbose = false) {
   if (true) { // Your configuration check
      return true;
   }

   if ($verbose) {
      _e('Installed / not configured', 'glpi2mdt');
   }
   return false;
}
