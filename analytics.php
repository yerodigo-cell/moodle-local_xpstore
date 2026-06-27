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

/**
 * Analytics.php
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('moodle/course:update', $context);

$url = new moodle_url('/local/xpstore/analytics.php', ['id' => $courseid]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('analytics', 'local_xpstore'));

// Logic to reset the cycle.
$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'reset' && confirm_sesskey()) {
    $resetsql = "DELETE g FROM {local_xpstore_gastos} g
                 JOIN {course_modules} cm ON g.itemid = cm.id
                 WHERE cm.course = ?";
    $DB->execute($resetsql, [$courseid]);
    redirect($url, get_string('productdeleted', 'local_xpstore'));
}

// Pre-load store categories to assign them to records.
$configraw = get_config('local_xpstore', 'catalog_course_' . $courseid) ?: '';
$itemsraw = array_filter(explode(',', $configraw));
$mapcategorias = [];
$mapcustomlabels = [];

foreach ($itemsraw as $item) {
    $tipochar = strtoupper(substr($item, 0, 1));
    $rest = substr($item, 1);
    $parts = explode(':', $rest);
    if (count($parts) >= 2) {
        $cid = $parts[0] ?? '';
        $customname = $parts[2] ?? '';
        $cat = !empty($parts[4]) ? trim($parts[4]) : get_string('defaultcategory', 'local_xpstore');
        $mapcategorias[$tipochar][$cid] = $cat;
        if ($customname !== '') {
            $mapcustomlabels[$tipochar][$cid] = $customname;
        }
    }
}

// Global stats
$totalpurchases = $DB->count_records_sql("
    SELECT COUNT(g.id) 
    FROM {local_xpstore_gastos} g
    JOIN {course_modules} cm ON g.itemid = cm.id
    WHERE cm.course = ?", [$courseid]);

$totalxp = $DB->get_field_sql("
    SELECT SUM(g.amount) 
    FROM {local_xpstore_gastos} g
    JOIN {course_modules} cm ON g.itemid = cm.id
    WHERE cm.course = ?", [$courseid]) ?: 0;

$engagedstudents = $DB->count_records_sql("
    SELECT COUNT(DISTINCT g.userid) 
    FROM {local_xpstore_gastos} g
    JOIN {course_modules} cm ON g.itemid = cm.id
    WHERE cm.course = ?", [$courseid]);

// Course total students
$coursecontext = context_course::instance($courseid);
$totalstudents_in_course = count(get_enrolled_users($coursecontext, '', 0, 'u.id'));
$engagementrate = $totalstudents_in_course > 0 ? round(($engagedstudents / $totalstudents_in_course) * 100) : 0;

// Data for charts
$sql = "SELECT cm.id as itemid, g.itemtype, COUNT(g.id) as purchases, SUM(g.amount) as totalxp
        FROM {local_xpstore_gastos} g
        JOIN {course_modules} cm ON g.itemid = cm.id
        WHERE cm.course = ?
        GROUP BY cm.id, g.itemtype
        ORDER BY purchases DESC";
$itemsdata = $DB->get_records_sql($sql, [$courseid]);

$chartdata_labels = [];
$chartdata_purchases = [];
$chartdata_xp = [];
$toprewards_purchases = [];
$toprewards_xp = [];

// Prepare data
if ($itemsdata) {
    foreach ($itemsdata as $item) {
        $cm = $DB->get_record('course_modules', ['id' => $item->itemid]);
        if ($cm) {
            $modname = $DB->get_field('modules', 'name', ['id' => $cm->module]);
            $activityname = $DB->get_field($modname, 'name', ['id' => $cm->instance]);
        } else {
            $activityname = get_string('activitydeleted', 'local_xpstore');
        }

        $tipostr = strtolower($item->itemtype);
        $tipocharupper = strtoupper($tipostr);
        $customlabel = isset($mapcustomlabels[$tipocharupper][$item->itemid])
            ? $mapcustomlabels[$tipocharupper][$item->itemid]
            : '';

        $displayname = $customlabel !== '' ? $customlabel : $activityname;
        // Limit label length for charts
        $shortname = core_text::substr($displayname, 0, 20) . (core_text::strlen($displayname) > 20 ? '...' : '');

        $chartdata_labels[] = $shortname;
        $chartdata_purchases[] = (int)$item->purchases;
        $chartdata_xp[] = (int)$item->totalxp;
        
        $item->displayname = $displayname;
    }
}

// Prepare Moodle Charts
$has_data = count($chartdata_labels) > 0;
$chart_purchases_html = '';
$chart_xp_html = '';

if ($has_data) {
    // Top 5 or all if less
    $top_labels = array_slice($chartdata_labels, 0, 5);
    $top_purchases = array_slice($chartdata_purchases, 0, 5);
    
    $chart1 = new \core\chart_bar();
    $series1 = new \core\chart_series(get_string('purchases', 'local_xpstore'), $top_purchases);
    $series1->set_color('#0084ff'); // Vibrant blue to match the first card
    $chart1->add_series($series1);
    $chart1->set_labels($top_labels);
    $chart_purchases_html = $OUTPUT->render($chart1);

    // Sort by XP
    $sorted_by_xp = $itemsdata;
    usort($sorted_by_xp, function($a, $b) {
        return $b->totalxp <=> $a->totalxp;
    });
    
    $top_xp_labels = [];
    $top_xp_values = [];
    $count = 0;
    foreach($sorted_by_xp as $item) {
        if ($count >= 5) break;
        $top_xp_labels[] = core_text::substr($item->displayname, 0, 20) . (core_text::strlen($item->displayname) > 20 ? '...' : '');
        $top_xp_values[] = (int)$item->totalxp;
        $count++;
    }

    $chart2 = new \core\chart_bar();
    $series2 = new \core\chart_series(get_string('points', 'local_xpstore'), $top_xp_values);
    $series2->set_color('#ff6a00'); // Vibrant orange to match the second card
    $chart2->add_series($series2);
    $chart2->set_labels($top_xp_labels);
    // Set a different color for the second chart if possible, Moodle handles colors automatically based on theme, but we can rely on defaults.
    $chart_xp_html = $OUTPUT->render($chart2);
}

echo $OUTPUT->header();

$navdata = local_xpstore_get_navigation_data($courseid, 'analytics');
$navdata['isteacher'] = true;
$navdata['show_reset_btn'] = true;
$navdata['str_confirmreset'] = get_string('confirmreset', 'local_xpstore');
$navdata['str_resetcycle'] = get_string('resetcycle', 'local_xpstore');
$navdata['reseturl'] = (new moodle_url($url, ['action' => 'reset', 'sesskey' => sesskey()]))->out(false);

$templatedata = array_merge([
    'courseid' => $courseid,
    'has_data' => $has_data,
    'str_analytics' => get_string('analytics', 'local_xpstore'),
    'str_analyticssubtitle' => get_string('analyticssubtitle', 'local_xpstore'),
    'str_toprewardsbypurchases' => get_string('toprewardsbypurchases', 'local_xpstore'),
    'str_toprewardsbyxp' => get_string('toprewardsbyxp', 'local_xpstore'),
    'str_totalpurchases' => get_string('totalpurchases', 'local_xpstore'),
    'str_totalxp' => get_string('totalxp', 'local_xpstore'),
    'str_totalstudents' => get_string('totalstudents', 'local_xpstore'),
    'str_engagementrate' => get_string('engagementrate', 'local_xpstore'),
    'str_viewaudit' => get_string('viewaudit', 'local_xpstore'),
    'str_nopurchases' => get_string('nopurchases', 'local_xpstore'),
    'totalpurchases' => $totalpurchases,
    'totalxp' => number_format($totalxp),
    'engagedstudents' => $engagedstudents,
    'engagementrate' => $engagementrate,
    'chart_purchases_html' => $chart_purchases_html,
    'chart_xp_html' => $chart_xp_html,
], $navdata);

echo $OUTPUT->render_from_template('local_xpstore/analytics_page', $templatedata);
echo $OUTPUT->footer();
