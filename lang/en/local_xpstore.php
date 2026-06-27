<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * XP Store (local_xpstore)
 *
 * @package     local_xpstore
 * @copyright   2026 Yeison Díaz
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['action'] = 'Action';
$string['activity'] = 'Activity';
$string['activitydeleted'] = 'Activity / Resource deleted';
$string['add'] = 'Add';
$string['addproduct'] = 'Add New Product';
$string['analytics'] = 'Analytics';
$string['analyticssubtitle'] = 'Store performance and usage metrics';
$string['audit'] = 'XP Redemptions';
$string['balance'] = 'BALANCE';
$string['cancel'] = 'Cancel';
$string['canjear'] = 'Redeem';
$string['catalog'] = 'Choose your Rewards';
$string['category'] = 'Store Category';
$string['category_placeholder'] = 'Eg: VIP...';
$string['category_short'] = 'Cat:';
$string['categoryicons'] = 'Category icons';
$string['chooseactivity'] = 'Select Activity';
$string['choosetype'] = 'Select Type';
$string['colaction'] = 'Action';
$string['colactivity'] = 'Linked Activity';
$string['colcategory'] = 'Store Category';
$string['colcost'] = 'Cost';
$string['collabel'] = 'Label';
$string['color_cat_icon'] = 'Cat. Icons Color';
$string['colorconfig'] = 'Visual Customization';

$string['colorsreset'] = 'Colors successfully reset to default.';
$string['colorssaved'] = 'Colors updated successfully.';
$string['coltype'] = 'Type';
$string['configtitle'] = 'XP Store Configuration';
$string['configure'] = 'Configure';
$string['confirmdelete'] = 'Do you really want to delete this product?';
$string['confirmdeleteall'] = 'Are you sure you want to delete ALL rewards? This action cannot be undone.';
$string['confirmreset'] = 'Are you sure you want to clear all history for this course?';
$string['confirmresetcolors'] = 'Are you sure you want to reset the store to its original colors?';
$string['congratulations'] = 'Congratulations!';
$string['copyalert'] = 'Code copied to clipboard! Now go to any Label or Page in your course, open the HTML editor (</>) and paste it.';
$string['copysinglecard'] = 'Copy Single Card';
$string['cost'] = 'Cost';
$string['currentcatalog'] = 'Current Catalog';
$string['date'] = 'Date';
$string['defaultcategory'] = 'Catálogo Principal';
$string['delete'] = 'Delete';
$string['deleteall'] = 'Delete All';
$string['deletedall'] = 'All rewards have been removed from the catalog.';
$string['edit'] = 'Edit';
$string['engagementrate'] = 'Engagement Rate';
$string['error'] = 'Error processing redemption';
$string['exito'] = 'Redemption successful!';
$string['full_store'] = 'Full Store';
$string['global_settings_info'] = 'XP Store is successfully installed! This plugin does not require global configuration. To configure the store, colors, and products, please go to the main page of any course and click the "XP Store Config" button.';
$string['gotoactivity'] = 'Go to Activity';
$string['gotogradebook'] = 'Go to Gradebook';
$string['gradepoints'] = 'Bonus (+)';
$string['hide_menu_tooltip'] = 'Hide store from course menu';
$string['history'] = 'Purchase History';
$string['history_button'] = 'History Button';
$string['icon_award'] = 'Award Ribbon';
$string['icon_bolt'] = 'Lightning Bolt';
$string['icon_book'] = 'Book';
$string['icon_camera'] = 'Camera';
$string['icon_cart'] = 'Cart';
$string['icon_diamond'] = 'Diamond';
$string['icon_gamepad'] = 'Gamepad';
$string['icon_gift'] = 'Gift';
$string['icon_globe'] = 'Globe';
$string['icon_graduation'] = 'Graduation';
$string['icon_heart'] = 'Heart';
$string['icon_magic'] = 'Magic';
$string['icon_medal'] = 'Medal';
$string['icon_money'] = 'Coins';
$string['icon_music'] = 'Music';
$string['icon_puzzle'] = 'Puzzle';
$string['icon_rocket'] = 'Rocket';
$string['icon_shield'] = 'Shield';
$string['icon_star'] = 'Star';
$string['icon_ticket'] = 'Ticket';
$string['icon_trophy'] = 'Trophy';
$string['iconcolor'] = 'Card Icon Color';
$string['icons_saved'] = 'Icons saved.';
$string['insuficiente'] = 'Insufficient points';
$string['isactive'] = 'Active';
$string['label'] = 'Label';
$string['label_help'] = 'If you select the <b>Special</b> type, a group with this label will be automatically created (or reused if it already exists) and the selected activity will be restricted to it.';
$string['limit'] = 'Limit';
$string['limitreached'] = 'You have reached the maximum purchase limit for this item.';
$string['limitzero'] = '0 = ∞ (Infinite)';
$string['linkedactivity'] = 'Linked Activity';
$string['menuhidden'] = 'Menu: Hidden';
$string['menuvisibility_help'] = 'Enables or disables the direct access link to the store in the course navigation menu for students. We recommend keeping it hidden and using Store Widgets instead, as they provide a more immersive experience (including sound effects and animations when purchasing rewards).';
$string['menuvisible'] = 'Menu: Visible';
$string['mockup_sampleitem'] = 'Sample Item';
$string['mockup_shortdesc'] = 'Short description';
$string['mockup_storebanner'] = 'Store Banner';
$string['mockup_storecard'] = 'Store Card';
$string['mockup_widget'] = 'Individual Widget';
$string['nopurchases'] = 'No purchases or redemptions have been recorded in this course yet.';
$string['norewardscreated'] = 'No rewards have been created yet.';
$string['pluginname'] = 'XP Store';
$string['predefinedpalettes'] = 'Predefined Palettes';
$string['points'] = 'Points';
$string['primarycolor'] = 'Primary Color';
$string['privacy:metadata:local_xpstore_gastos'] = 'Stores information about user redemptions in the XP Store.';
$string['privacy:metadata:local_xpstore_gastos:amount'] = 'The cost of the item in XP.';
$string['privacy:metadata:local_xpstore_gastos:itemid'] = 'The ID of the course module where the redemption was applied.';
$string['privacy:metadata:local_xpstore_gastos:itemtype'] = 'The type of the item redeemed.';
$string['privacy:metadata:local_xpstore_gastos:timecreated'] = 'The time the redemption occurred.';
$string['privacy:metadata:local_xpstore_gastos:userid'] = 'The user who made the redemption.';
$string['productadded'] = 'Product added successfully';
$string['productdeleted'] = 'Product deleted';
$string['products'] = 'Products';
$string['productupdated'] = 'Reward successfully updated.';
$string['purchases'] = 'Purchases';
$string['redemptions'] = 'redemptions';
$string['redemptions_count'] = 'Redemptions:';
$string['reportsubtitle'] = 'Individual tracking of XP benefits';
$string['reporttitle'] = 'Redemption Report';
$string['resetcolors'] = 'Reset to default';
$string['resetcycle'] = 'Reset Cycle';
$string['resethistory'] = 'Clear redemption history';
$string['saldo'] = 'Your current Balance';
$string['savecolors'] = 'Save Colors';
$string['saveicons'] = 'Save icons';
$string['searchactivity'] = 'Search (Activity)...';
$string['searchfilters'] = 'Search filters';
$string['searchfilters_help'] = 'You can filter the report using two criteria:<br><br><b>Activity:</b> Searches by the name of the Moodle activity or the custom label assigned to the reward.<br><b>Type:</b> Searches by the reward type (e.g. Special, Bonus, etc).';
$string['searchtype'] = 'Search (Type)...';
$string['secondarycolor'] = 'Secondary Color';
$string['settings'] = 'Settings';
$string['show_menu_tooltip'] = 'Show store in course menu';
$string['showinmenu'] = 'Show in course menu';
$string['showinmenu_desc'] = 'If checked, students will see a shortcut to the store in the navigation menu.';
$string['soldout'] = 'Sold out';
$string['specialcontent'] = 'Special';
$string['storeempty_desc'] = 'No rewards have been configured yet.';
$string['storeempty_title'] = 'Store is empty';
$string['storetitle'] = 'XP Store';
$string['success_unlock_gradebook'] = 'You got the reward <b>{$a->reward}</b> on the activity <b>{$a->activity}</b>!';
$string['success_unlock_reward'] = 'You got the reward <b>{$a->reward}</b> on the activity <b>{$a->activity}</b>!';
$string['tiendaxp'] = 'XP Store';
$string['toprewardsbypurchases'] = 'Top Rewards (by purchases)';
$string['toprewardsbyxp'] = 'Top Rewards (by XP spent)';
$string['totalpurchases'] = 'Total Purchases';
$string['totalspent'] = 'Total spent: {$a} XP';
$string['totalstudents'] = 'Engaged Students';
$string['totalxp'] = 'Total XP Spent';
$string['type'] = 'Type';
$string['type_a'] = '24h Extension';
$string['type_f'] = 'Forum (Legacy)';
$string['type_g'] = 'Bonus';
$string['type_help'] = 'Select the type of benefit:<br><br><b>Extra Attempt:</b> Works with Quizzes.<br><b>24h Extension:</b> Works only with Assignments.<br><b>Forum (Legacy):</b> Allows posting after the cut-off date.<br><b>Bonus:</b> Adds extra points directly to the gradebook.<br><b>Special:</b> Unlocks hidden content or VIP groups.';
$string['type_q'] = 'Extra Attempt';
$string['type_s'] = 'Special';
$string['update'] = 'Update';
$string['user'] = 'User';
$string['viewanalytics'] = 'View Analytics';
$string['viewaudit'] = 'View Audit Log';
$string['widget_panel_desc'] = 'Click a button to copy the HTML code. Then paste it into the HTML editor of any Label or Page in your course.';
$string['widget_panel_title'] = 'Widgets (embed store or categories)';
$string['widgeterror'] = 'Reward not available.';
$string['widgetunlocked'] = 'Unlocked!';
$string['widgetunlockeddesc'] = 'You can now use your reward.';
$string['xpstore:manage'] = 'Manage the XP Store';
