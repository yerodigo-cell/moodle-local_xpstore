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
 * @copyright  2026 Yeison Díaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

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
 * @copyright  2026 Yeison Díaz
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
                'itemid' => 'privacy:metadata:local_xpstore_gastos:itemid',
                'itemtype' => 'privacy:metadata:local_xpstore_gastos:itemtype',
                'amount' => 'privacy:metadata:local_xpstore_gastos:amount',
                'timecreated' => 'privacy:metadata:local_xpstore_gastos:timecreated',
            ],
            'privacy:metadata:local_xpstore_gastos'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int         $userid     The user to search.
     * @return  contextlist   $contextlist  The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {local_xpstore_gastos} g ON g.itemid = cm.id
                 WHERE g.userid = :userid";

        $contextlist->add_from_sql($sql, ['contextlevel' => CONTEXT_MODULE, 'userid' => $userid]);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $sql = "SELECT g.userid
                  FROM {local_xpstore_gastos} g
                 WHERE g.itemid = :itemid";

        $userlist->add_from_sql('userid', $sql, ['itemid' => $context->instanceid]);
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

        $sql = "SELECT g.*, c.id as contextid
                  FROM {local_xpstore_gastos} g
                  JOIN {course_modules} cm ON cm.id = g.itemid
                  JOIN {context} c ON c.instanceid = cm.id AND c.contextlevel = :contextlevel
                 WHERE g.userid = :userid AND c.id {$contextsql}";

        $params = ['userid' => $userid, 'contextlevel' => CONTEXT_MODULE] + $contextparams;
        $gastos = $DB->get_recordset_sql($sql, $params);

        foreach ($gastos as $gasto) {
            $context = \context::instance_by_id($gasto->contextid);
            $data = (object)[
                'itemtype' => $gasto->itemtype,
                'amount' => $gasto->amount,
                'timecreated' => transform::datetime($gasto->timecreated),
            ];
            writer::with_context($context)->export_data(
                [get_string('pluginname', 'local_xpstore'), get_string('purchases', 'local_xpstore')],
                $data
            );
        }
        $gastos->close();
    }

    /**
     * Delete all use data which matches the specified context.
     *
     * @param   \context        $context   A user context.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel == CONTEXT_MODULE) {
            $DB->delete_records('local_xpstore_gastos', ['itemid' => $context->instanceid]);
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

        $sql = "SELECT g.id
                  FROM {local_xpstore_gastos} g
                  JOIN {course_modules} cm ON cm.id = g.itemid
                  JOIN {context} c ON c.instanceid = cm.id AND c.contextlevel = :contextlevel
                 WHERE g.userid = :userid AND c.id {$contextsql}";

        $params = ['userid' => $userid, 'contextlevel' => CONTEXT_MODULE] + $contextparams;
        $ids = $DB->get_fieldset_sql($sql, $params);

        if (!empty($ids)) {
            [$idsql, $idparams] = $DB->get_in_or_equal($ids);
            $DB->delete_records_select('local_xpstore_gastos', "id {$idsql}", $idparams);
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

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        [$usersql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $sql = "SELECT g.id
                  FROM {local_xpstore_gastos} g
                 WHERE g.itemid = :itemid AND g.userid {$usersql}";

        $params = ['itemid' => $context->instanceid] + $userparams;
        $ids = $DB->get_fieldset_sql($sql, $params);

        if (!empty($ids)) {
            [$idsql, $idparams] = $DB->get_in_or_equal($ids);
            $DB->delete_records_select('local_xpstore_gastos', "id {$idsql}", $idparams);
        }
    }
}
