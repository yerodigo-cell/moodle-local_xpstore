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
 * Privacy provider.
 *
 * @package    local_xpstore
 * @copyright  2026 EduPlugins Studio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpstore\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy API provider for local_xpstore.
 *
 * @copyright  2026 EduPlugins Studio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Returns meta data about this system.
     *
     * @param   collection     $collection The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_xpstore_gastos',
            [
                'userid' => 'privacy:metadata:local_xpstore_gastos:userid',
                'courseid' => 'privacy:metadata:local_xpstore_gastos:courseid',
                'itemid' => 'privacy:metadata:local_xpstore_gastos:itemid',
                'itemtype' => 'privacy:metadata:local_xpstore_gastos:itemtype',
                'amount' => 'privacy:metadata:local_xpstore_gastos:amount',
                'gradevalue' => 'privacy:metadata:local_xpstore_gastos:gradevalue',
                'bonusmode' => 'privacy:metadata:local_xpstore_gastos:bonusmode',
                'rewardname' => 'privacy:metadata:local_xpstore_gastos:rewardname',
                'prevdata' => 'privacy:metadata:local_xpstore_gastos:prevdata',
                'timecreated' => 'privacy:metadata:local_xpstore_gastos:timecreated',
            ],
            'privacy:metadata:local_xpstore_gastos'
        );

        $collection->add_database_table(
            'local_xpstore_grade_state',
            [
                'courseid' => 'privacy:metadata:local_xpstore_grade_state:courseid',
                'itemid' => 'privacy:metadata:local_xpstore_grade_state:itemid',
                'userid' => 'privacy:metadata:local_xpstore_grade_state:userid',
                'basegrade' => 'privacy:metadata:local_xpstore_grade_state:basegrade',
                'appliedgrade' => 'privacy:metadata:local_xpstore_grade_state:appliedgrade',
                'timemodified' => 'privacy:metadata:local_xpstore_grade_state:timemodified',
            ],
            'privacy:metadata:local_xpstore_grade_state'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int         $userid     The user to search.
     * @return  contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Module contexts for active module-linked redemptions (excluding manual/course grade items).
        $sqlmod = "SELECT DISTINCT c.id
                     FROM {context} c
                     JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                     JOIN {local_xpstore_gastos} g ON g.itemid = cm.id AND g.itemtype NOT IN ('M', 'C')
                    WHERE g.userid = :userid";
        $contextlist->add_from_sql($sqlmod, ['contextlevel' => CONTEXT_MODULE, 'userid' => $userid]);

        // Course contexts for course/manual grade items, deleted-activity redemptions, and course-scoped logs.
        $sqlcourse = "SELECT DISTINCT c.id
                        FROM {context} c
                        JOIN {local_xpstore_gastos} g
                          ON c.contextlevel = :contextlevel
                         AND (
                              g.courseid = c.instanceid
                              OR EXISTS (
                                  SELECT 1
                                    FROM {grade_items} gi
                                   WHERE gi.id = g.itemid
                                     AND g.itemtype IN ('M', 'C')
                                     AND gi.courseid = c.instanceid
                              )
                         )
                       WHERE g.userid = :userid";
        $contextlist->add_from_sql($sqlcourse, ['contextlevel' => CONTEXT_COURSE, 'userid' => $userid]);

        // Course contexts for grade bonus state records.
        $sqlstate = "SELECT DISTINCT c.id
                       FROM {context} c
                       JOIN {local_xpstore_grade_state} s
                         ON s.courseid = c.instanceid AND c.contextlevel = :contextlevel
                      WHERE s.userid = :userid";
        $contextlist->add_from_sql($sqlstate, ['contextlevel' => CONTEXT_COURSE, 'userid' => $userid]);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context->contextlevel == CONTEXT_MODULE) {
            $sql = "SELECT g.userid
                      FROM {local_xpstore_gastos} g
                     WHERE g.itemid = :itemid
                       AND g.itemtype NOT IN ('M', 'C')";
            $userlist->add_from_sql('userid', $sql, ['itemid' => $context->instanceid]);
            return;
        }

        if ($context->contextlevel == CONTEXT_COURSE) {
            $sqlgastos = "SELECT g.userid
                            FROM {local_xpstore_gastos} g
                       LEFT JOIN {course_modules} cm ON cm.id = g.itemid AND g.itemtype NOT IN ('M', 'C')
                       LEFT JOIN {grade_items} gi ON gi.id = g.itemid AND g.itemtype IN ('M', 'C')
                           WHERE g.courseid = :courseid1
                              OR (g.courseid = 0 AND (cm.course = :courseid2 OR gi.courseid = :courseid3))";
            $userlist->add_from_sql('userid', $sqlgastos, [
                'courseid1' => $context->instanceid,
                'courseid2' => $context->instanceid,
                'courseid3' => $context->instanceid,
            ]);

            $sqlstate = "SELECT s.userid
                           FROM {local_xpstore_grade_state} s
                          WHERE s.courseid = :courseid";
            $userlist->add_from_sql('userid', $sqlstate, ['courseid' => $context->instanceid]);
        }
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        [$contextsql, $contextparams] = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        // 1. Export module-linked purchases under CONTEXT_MODULE.
        $sqlmod = "SELECT g.*, c.id AS contextid
                     FROM {local_xpstore_gastos} g
                     JOIN {course_modules} cm ON cm.id = g.itemid AND g.itemtype NOT IN ('M', 'C')
                     JOIN {context} c ON c.instanceid = cm.id AND c.contextlevel = :contextlevel
                    WHERE g.userid = :userid AND c.id {$contextsql}
                 ORDER BY g.timecreated ASC, g.id ASC";
        $paramsmod = ['userid' => $userid, 'contextlevel' => CONTEXT_MODULE] + $contextparams;
        $modgastos = $DB->get_recordset_sql($sqlmod, $paramsmod);

        $groupedmod = [];
        foreach ($modgastos as $gasto) {
            $groupedmod[$gasto->contextid][] = (object)[
                'itemtype' => $gasto->itemtype,
                'amount' => $gasto->amount,
                'gradevalue' => $gasto->gradevalue,
                'bonusmode' => $gasto->bonusmode,
                'rewardname' => $gasto->rewardname,
                'timecreated' => transform::datetime($gasto->timecreated),
            ];
        }
        $modgastos->close();

        foreach ($groupedmod as $ctxid => $records) {
            $ctx = \context::instance_by_id($ctxid, IGNORE_MISSING);
            if ($ctx) {
                writer::with_context($ctx)->export_data(
                    [get_string('pluginname', 'local_xpstore'), get_string('purchases', 'local_xpstore')],
                    (object)['purchases' => $records]
                );
            }
        }

        // 2. Export course-level purchases ('M', 'C', or deleted modules) and grade_state under CONTEXT_COURSE.
        $sqlcourse = "SELECT g.*, c.id AS contextid
                        FROM {local_xpstore_gastos} g
                   LEFT JOIN {course_modules} cm ON cm.id = g.itemid AND g.itemtype NOT IN ('M', 'C')
                   LEFT JOIN {grade_items} gi ON gi.id = g.itemid AND g.itemtype IN ('M', 'C')
                        JOIN {context} c
                          ON c.contextlevel = :contextlevel
                         AND (g.courseid = c.instanceid OR (g.courseid = 0 AND gi.courseid = c.instanceid))
                       WHERE g.userid = :userid
                         AND (g.itemtype IN ('M', 'C') OR cm.id IS NULL)
                         AND c.id {$contextsql}
                    ORDER BY g.timecreated ASC, g.id ASC";
        $paramscourse = ['userid' => $userid, 'contextlevel' => CONTEXT_COURSE] + $contextparams;
        $coursegastos = $DB->get_recordset_sql($sqlcourse, $paramscourse);

        $groupedcourse = [];
        foreach ($coursegastos as $gasto) {
            $groupedcourse[$gasto->contextid][] = (object)[
                'itemid' => $gasto->itemid,
                'itemtype' => $gasto->itemtype,
                'amount' => $gasto->amount,
                'gradevalue' => $gasto->gradevalue,
                'bonusmode' => $gasto->bonusmode,
                'rewardname' => $gasto->rewardname,
                'timecreated' => transform::datetime($gasto->timecreated),
            ];
        }
        $coursegastos->close();

        foreach ($groupedcourse as $ctxid => $records) {
            $ctx = \context::instance_by_id($ctxid, IGNORE_MISSING);
            if ($ctx) {
                writer::with_context($ctx)->export_data(
                    [get_string('pluginname', 'local_xpstore'), get_string('purchases', 'local_xpstore')],
                    (object)['purchases' => $records]
                );
            }
        }

        $sqlstate = "SELECT s.*, c.id AS contextid
                       FROM {local_xpstore_grade_state} s
                       JOIN {context} c ON c.instanceid = s.courseid AND c.contextlevel = :contextlevel
                      WHERE s.userid = :userid AND c.id {$contextsql}
                   ORDER BY s.id ASC";
        $states = $DB->get_recordset_sql($sqlstate, $paramscourse);
        $groupedstates = [];
        foreach ($states as $state) {
            $groupedstates[$state->contextid][] = (object)[
                'itemid' => $state->itemid,
                'basegrade' => $state->basegrade,
                'appliedgrade' => $state->appliedgrade,
                'timemodified' => transform::datetime($state->timemodified),
            ];
        }
        $states->close();

        foreach ($groupedstates as $ctxid => $records) {
            $ctx = \context::instance_by_id($ctxid, IGNORE_MISSING);
            if ($ctx) {
                writer::with_context($ctx)->export_data(
                    [get_string('pluginname', 'local_xpstore'), 'grade_state'],
                    (object)['grade_states' => $records]
                );
            }
        }
    }

    /**
     * Delete all user data which matches the specified context.
     *
     * @param   \context        $context   A context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel == CONTEXT_MODULE) {
            $DB->delete_records_select(
                'local_xpstore_gastos',
                "itemid = :itemid AND itemtype NOT IN ('M', 'C')",
                ['itemid' => $context->instanceid]
            );
            return;
        }

        if ($context->contextlevel == CONTEXT_COURSE) {
            $courseid = (int)$context->instanceid;
            $ids = $DB->get_fieldset_sql(
                "SELECT g.id
                   FROM {local_xpstore_gastos} g
              LEFT JOIN {course_modules} cm ON cm.id = g.itemid AND g.itemtype NOT IN ('M', 'C')
              LEFT JOIN {grade_items} gi ON gi.id = g.itemid AND g.itemtype IN ('M', 'C')
                  WHERE g.courseid = ? OR (g.courseid = 0 AND (cm.course = ? OR gi.courseid = ?))",
                [$courseid, $courseid, $courseid]
            );
            if (!empty($ids)) {
                [$idsql, $idparams] = $DB->get_in_or_equal($ids);
                $DB->delete_records_select('local_xpstore_gastos', "id {$idsql}", $idparams);
            }
            $DB->delete_records('local_xpstore_grade_state', ['courseid' => $courseid]);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        [$contextsql, $contextparams] = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        // 1. Delete module-level gastos in approved CONTEXT_MODULE contexts.
        $sqlmod = "SELECT g.id
                     FROM {local_xpstore_gastos} g
                     JOIN {course_modules} cm ON cm.id = g.itemid AND g.itemtype NOT IN ('M', 'C')
                     JOIN {context} c ON c.instanceid = cm.id AND c.contextlevel = :contextlevel
                    WHERE g.userid = :userid AND c.id {$contextsql}";
        $paramsmod = ['userid' => $userid, 'contextlevel' => CONTEXT_MODULE] + $contextparams;
        $modids = $DB->get_fieldset_sql($sqlmod, $paramsmod);
        if (!empty($modids)) {
            [$idsql, $idparams] = $DB->get_in_or_equal($modids);
            $DB->delete_records_select('local_xpstore_gastos', "id {$idsql}", $idparams);
        }

        // 2. Delete course-level gastos and grade_state in approved CONTEXT_COURSE contexts.
        $sqlcourse = "SELECT g.id
                        FROM {local_xpstore_gastos} g
                   LEFT JOIN {course_modules} cm ON cm.id = g.itemid AND g.itemtype NOT IN ('M', 'C')
                   LEFT JOIN {grade_items} gi ON gi.id = g.itemid AND g.itemtype IN ('M', 'C')
                        JOIN {context} c
                          ON c.contextlevel = :contextlevel
                         AND (
                              g.courseid = c.instanceid
                              OR (g.courseid = 0 AND (cm.course = c.instanceid OR gi.courseid = c.instanceid))
                         )
                       WHERE g.userid = :userid AND c.id {$contextsql}";
        $paramscourse = ['userid' => $userid, 'contextlevel' => CONTEXT_COURSE] + $contextparams;
        $courseids = $DB->get_fieldset_sql($sqlcourse, $paramscourse);
        if (!empty($courseids)) {
            [$idsql, $idparams] = $DB->get_in_or_equal($courseids);
            $DB->delete_records_select('local_xpstore_gastos', "id {$idsql}", $idparams);
        }

        $stateids = $DB->get_fieldset_sql(
            "SELECT s.id
               FROM {local_xpstore_grade_state} s
               JOIN {context} c ON c.instanceid = s.courseid AND c.contextlevel = :contextlevel
              WHERE s.userid = :userid AND c.id {$contextsql}",
            $paramscourse
        );
        if (!empty($stateids)) {
            [$idsql, $idparams] = $DB->get_in_or_equal($stateids);
            $DB->delete_records_select('local_xpstore_grade_state', "id {$idsql}", $idparams);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        [$usersql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        if ($context->contextlevel == CONTEXT_MODULE) {
            $sql = "SELECT g.id
                      FROM {local_xpstore_gastos} g
                     WHERE g.itemid = :itemid
                       AND g.itemtype NOT IN ('M', 'C')
                       AND g.userid {$usersql}";
            $params = ['itemid' => $context->instanceid] + $userparams;
            $ids = $DB->get_fieldset_sql($sql, $params);
            if (!empty($ids)) {
                [$idsql, $idparams] = $DB->get_in_or_equal($ids);
                $DB->delete_records_select('local_xpstore_gastos', "id {$idsql}", $idparams);
            }
            return;
        }

        if ($context->contextlevel == CONTEXT_COURSE) {
            $courseid = (int)$context->instanceid;
            $sql = "SELECT g.id
                      FROM {local_xpstore_gastos} g
                 LEFT JOIN {course_modules} cm ON cm.id = g.itemid AND g.itemtype NOT IN ('M', 'C')
                 LEFT JOIN {grade_items} gi ON gi.id = g.itemid AND g.itemtype IN ('M', 'C')
                     WHERE (g.courseid = :c1 OR (g.courseid = 0 AND (cm.course = :c2 OR gi.courseid = :c3)))
                       AND g.userid {$usersql}";
            $params = ['c1' => $courseid, 'c2' => $courseid, 'c3' => $courseid] + $userparams;
            $ids = $DB->get_fieldset_sql($sql, $params);
            if (!empty($ids)) {
                [$idsql, $idparams] = $DB->get_in_or_equal($ids);
                $DB->delete_records_select('local_xpstore_gastos', "id {$idsql}", $idparams);
            }

            $DB->delete_records_select(
                'local_xpstore_grade_state',
                "courseid = :courseid AND userid {$usersql}",
                ['courseid' => $courseid] + $userparams
            );
        }
    }
}
