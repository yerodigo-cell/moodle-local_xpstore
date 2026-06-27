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

// Global stats.
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

// Course total students.
$coursecontext = context_course::instance($courseid);
$totalstudentsincourse = count(get_enrolled_users($coursecontext, '', 0, 'u.id'));
$engagementrate = $totalstudentsincourse > 0 ? round(($engagedstudents / $totalstudentsincourse) * 100) : 0;

// Data for charts.
$sql = "SELECT cm.id as itemid, g.itemtype, COUNT(g.id) as purchases, SUM(g.amount) as totalxp
        FROM {local_xpstore_gastos} g
        JOIN {course_modules} cm ON g.itemid = cm.id
        WHERE cm.course = ?
        GROUP BY cm.id, g.itemtype
        ORDER BY purchases DESC";
$itemsdata = $DB->get_records_sql($sql, [$courseid]);

$chartdatalabels = [];
$chartdatapurchases = [];
$chartdataxp = [];
$toprewardspurchases = [];
$toprewardsxp = [];
$activitypurchases = [];

// Prepare data.
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
        // Limit label length for charts.
        $shortname = core_text::substr($displayname, 0, 20) . (core_text::strlen($displayname) > 20 ? '...' : '');

        $chartdatalabels[] = $shortname;

        $chartdatapurchases[] = (int)$item->purchases;
        $chartdataxp[] = (int)$item->totalxp;

        if (!isset($activitypurchases[$activityname])) {
            $activitypurchases[$activityname] = 0;
        }
        $activitypurchases[$activityname] += (int)$item->purchases;


        $item->displayname = $displayname;
    }
}

// Prepare Moodle Charts.
$hasdata = count($chartdatalabels) > 0;
$chartpurchaseshtml = '';
$chartxphtml = '';
$chartactivitieshtml = '';

if ($hasdata) {
    // Top 5 or all if less.
    $toplabels = array_slice($chartdatalabels, 0, 5);
    $toppurchases = array_slice($chartdatapurchases, 0, 5);

    $chart1 = new \core\chart_bar();
    $series1 = new \core\chart_series(get_string('purchases', 'local_xpstore'), $toppurchases);
    $series1->set_color('#0084ff'); // Vibrant blue to match the first card.
    $chart1->add_series($series1);
    $chart1->set_labels($toplabels);
    $chartpurchaseshtml = $OUTPUT->render($chart1);

    // Sort by XP.
    $sortedbyxp = $itemsdata;
    usort($sortedbyxp, function ($a, $b) {
        return $b->totalxp <=> $a->totalxp;
    });

    $topxplabels = [];
    $topxpvalues = [];
    $count = 0;
    foreach ($sortedbyxp as $item) {
        if ($count >= 5) {
            break;
        }
        $topxplabels[] = core_text::substr($item->displayname, 0, 20) . (core_text::strlen($item->displayname) > 20 ? '...' : '');
        $topxpvalues[] = (int)$item->totalxp;
        $count++;
    }

    $chart2 = new \core\chart_bar();
    $series2 = new \core\chart_series(get_string('points', 'local_xpstore'), $topxpvalues);
    $series2->set_color('#ff6a00'); // Vibrant orange to match the second card.
    $chart2->add_series($series2);
    $chart2->set_labels($topxplabels);
    // Set a different color for the second chart if possible.
    // Moodle handles colors automatically based on theme, but we can rely on defaults.
    $chartxphtml = $OUTPUT->render($chart2);

    $chartactivitieshtml = '';
    if (!empty($activitypurchases)) {
        arsort($activitypurchases);
        $topactivities = array_slice($activitypurchases, 0, 5, true);
        
        $activitylabels = [];
        $activitydata = [];
        foreach ($topactivities as $name => $purchases) {
            $shortname = core_text::substr($name, 0, 20) . (core_text::strlen($name) > 20 ? '...' : '');
            $activitylabels[] = $shortname;
            $activitydata[] = $purchases;
        }
        
        $chart3 = new \core\chart_pie();
        $chart3->set_doughnut(true);
        $series3 = new \core\chart_series(get_string('purchases', 'local_xpstore'), $activitydata);
        $chart3->add_series($series3);
        $chart3->set_labels($activitylabels);
        $chartactivitieshtml = $OUTPUT->render($chart3);
    }
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
    'has_data' => $hasdata,
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

    'str_topactivities' => get_string('topactivities', 'local_xpstore'),
    'totalpurchases' => $totalpurchases,

    'totalxp' => number_format($totalxp),
    'engagedstudents' => $engagedstudents,
    'engagementrate' => $engagementrate,
    'chart_purchases_html' => $chartpurchaseshtml,

    'chart_xp_html' => $chartxphtml,
    
    'chart_xp_html' => $chartxphtml,
    'chart_activities_html' => $chartactivitieshtml,
    
    'has_activities_chart' => !empty($chartactivitieshtml),
    'debug_info' => 'Count purchases: ' . count($activitypurchases) . ' | chart empty? ' . (empty($chartactivitieshtml) ? 'yes' : 'no') . ' | hasdata: ' . ($hasdata ? 'yes' : 'no'),



], $navdata);

echo $OUTPUT->render_from_template('local_xpstore/analytics_page', $templatedata);
echo $OUTPUT->footer();
