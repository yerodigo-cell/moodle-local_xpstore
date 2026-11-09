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
 * @copyright   2026 EduPlugins Studio
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

if (!local_xpstore_can_manage($context)) {
    require_capability('local/xpstore:manage', $context);
}

$url = new moodle_url('/local/xpstore/analytics.php', ['id' => $courseid]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('analytics', 'local_xpstore'));

// Logic to reset the cycle.
$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'reset' && confirm_sesskey()) {
    local_xpstore_reset_purchases($courseid);
    redirect($url, get_string('productdeleted', 'local_xpstore'));
}

// Pre-load parsed store catalog and course modinfo in RAM.
$catalogmap = local_xpstore_parse_catalog($courseid, true);
$modinfo = get_fast_modinfo($course);

$coursefilter = "(g.courseid = ? OR (g.courseid = 0 AND (cm.course = ? OR gi.courseid = ?)))";
$filterparams = [$courseid, $courseid, $courseid];

// 1. Global stats in a single SQL pass instead of 3 separate LEFT JOIN queries.
$statssql = "SELECT COUNT(g.id) AS totalpurchases,
                    COALESCE(SUM(CASE WHEN g.amount > 0 THEN g.amount ELSE 0 END), 0) AS totalxp,
                    COUNT(DISTINCT g.userid) AS engagedstudents
               FROM {local_xpstore_gastos} g
          LEFT JOIN {course_modules} cm ON g.itemid = cm.id AND g.itemtype NOT IN ('M', 'C')
          LEFT JOIN {grade_items} gi ON g.itemid = gi.id AND g.itemtype IN ('M', 'C')
              WHERE {$coursefilter}";
$stats = $DB->get_record_sql($statssql, $filterparams);

$totalpurchases = $stats ? (int)$stats->totalpurchases : 0;
$totalxp = $stats ? (int)$stats->totalxp : 0;
$engagedstudents = $stats ? (int)$stats->engagedstudents : 0;

// 2. Course total students using SQL COUNT instead of loading user objects into RAM.
$totalstudentsincourse = count_enrolled_users($context);
$engagementrate = $totalstudentsincourse > 0 ? round(($engagedstudents / $totalstudentsincourse) * 100) : 0;

// 3. Data for charts (including grade_items name to avoid N+1 queries inside foreach).
$uniqueidsql = $DB->sql_concat('g.itemtype', "'_'", 'g.itemid');
$sql = "SELECT {$uniqueidsql} AS uniqueid, g.itemid, g.itemtype,
               COUNT(g.id) AS purchases,
               COALESCE(SUM(CASE WHEN g.amount > 0 THEN g.amount ELSE 0 END), 0) AS totalxp,
               MAX(g.rewardname) AS storedrewardname,
               MAX(gi.id) AS gi_id,
               MAX(gi.itemname) AS gi_itemname
          FROM {local_xpstore_gastos} g
     LEFT JOIN {course_modules} cm ON g.itemid = cm.id AND g.itemtype NOT IN ('M', 'C')
     LEFT JOIN {grade_items} gi ON g.itemid = gi.id AND g.itemtype IN ('M', 'C')
         WHERE {$coursefilter}
      GROUP BY g.itemid, g.itemtype
      ORDER BY purchases DESC";
$itemsdata = $DB->get_records_sql($sql, $filterparams);

$chartdatalabels = [];
$chartdatapurchases = [];
$chartdataxp = [];
$activitypurchases = [];

// Prepare data in memory without additional DB queries.
if ($itemsdata) {
    foreach ($itemsdata as $item) {
        $itemid = (int)$item->itemid;

        if ($item->itemtype === 'M') {
            $activityname = (!empty($item->gi_itemname))
                ? $item->gi_itemname
                : get_string('activitydeleted', 'local_xpstore');
        } else if ($item->itemtype === 'C') {
            if (!empty($item->gi_id)) {
                $activityname = trim((string)$item->gi_itemname) !== ''
                    ? format_string($item->gi_itemname)
                    : get_string('coursetotal', 'grades');
            } else {
                $activityname = get_string('activitydeleted', 'local_xpstore');
            }
        } else {
            if (isset($modinfo->cms[$itemid]) && empty($modinfo->cms[$itemid]->deletioninprogress)) {
                $activityname = $modinfo->cms[$itemid]->name;
            } else {
                $activityname = get_string('activitydeleted', 'local_xpstore');
            }
        }

        $tipocharupper = strtoupper($item->itemtype);
        $itemkey = $tipocharupper . $itemid;
        $catentry = $catalogmap[$itemkey] ?? null;

        $customlabel = '';
        if ($catentry && $catentry['nombre'] !== '') {
            $customlabel = $catentry['nombre'];
        } else if (!empty($item->storedrewardname)) {
            $customlabel = trim((string)$item->storedrewardname);
        }

        $cleanactivity = html_entity_decode(
            format_string($activityname, true, ['context' => $context]),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
        $cleancustom = ($customlabel !== '') ? html_entity_decode(
            format_string($customlabel, true, ['context' => $context]),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        ) : '';
        $displayname = $cleancustom !== '' ? $cleancustom : $cleanactivity;

        // Limit label length for charts.
        $shortname = core_text::substr($displayname, 0, 20) . (core_text::strlen($displayname) > 20 ? '...' : '');

        $chartdatalabels[] = $shortname;
        $chartdatapurchases[] = (int)$item->purchases;
        $chartdataxp[] = (int)$item->totalxp;

        if (!isset($activitypurchases[$cleanactivity])) {
            $activitypurchases[$cleanactivity] = 0;
        }
        $activitypurchases[$cleanactivity] += (int)$item->purchases;

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
    $sortedbyxp = array_values($itemsdata);
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
    'chart_activities_html' => $chartactivitieshtml,
    'has_activities_chart' => !empty($chartactivitieshtml),
], $navdata);

echo $OUTPUT->render_from_template('local_xpstore/analytics_page', $templatedata);
echo $OUTPUT->footer();
