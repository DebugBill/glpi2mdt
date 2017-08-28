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
// Purpose of file: Form to manipulation global configuration parameters
// ----------------------------------------------------------------------

// Entry menu case
define('GLPI_ROOT', '../..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("config", UPDATE);

// To be available when plugin in not activated
Plugin::load('glpi2mdt');

Html::header("TITRE", $_SERVER['PHP_SELF'], "config", "plugins");
_e("This is the plugin config page", 'glpi2mdt');
         ?>
        <form action="../plugins/glpi2mdt/front/computer.form.php" method="post">
            <?php echo Html::hidden('id', array('value' => $item->getID())); ?>
            <?php echo Html::hidden('_glpi_csrf_token', array('value' => Session::getNewCSRFToken())); ?>
            <div class="spaced" id="tabsbody">
                <table class="tab_cadre_fixe">
                    <tr class="tab_bg_1">
                        <td>
                            New Computer name: &nbsp;&nbsp;&nbsp;
                            <input type="text" name="name" size="40" class="ui-autocomplete-input" autocomplete="off"> &nbsp;&nbsp;&nbsp;
                            <input type="submit" class="submit" value="CLONE" name="clone"/>
                        </td>
                    </tr>
                </table>
            </div>
        </form>
        <?php
Html::footer();
