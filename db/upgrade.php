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
 * Upgrade steps for local_xpstore.
 *
 * @package     local_xpstore
 * @copyright   2026 EduPlugins Studio
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute local_xpstore upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_xpstore_upgrade($oldversion) {
    global $DB, $CFG;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026103000) {
        require_once($CFG->dirroot . '/local/xpstore/lib.php');

        // 1. Add new columns and indexes to {local_xpstore_gastos}.
        $table = new xmldb_table('local_xpstore_gastos');

        $field = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'id');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('gradevalue', XMLDB_TYPE_NUMBER, '10, 4', null, null, null, null, 'amount');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('bonusmode', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'gradevalue');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('rewardname', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'bonusmode');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('prevdata', XMLDB_TYPE_TEXT, null, null, null, null, null, 'rewardname');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $index = new xmldb_index('userid_courseid', XMLDB_INDEX_NOTUNIQUE, ['userid', 'courseid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new xmldb_index('courseid', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new xmldb_index('user_item', XMLDB_INDEX_NOTUNIQUE, ['userid', 'itemtype', 'itemid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // 2. Create {local_xpstore_grade_state} table.
        $statetable = new xmldb_table('local_xpstore_grade_state');
        $statetable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $statetable->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $statetable->add_field('itemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $statetable->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $statetable->add_field('basegrade', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);
        $statetable->add_field('appliedgrade', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);
        $statetable->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $statetable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $statetable->add_index('itemid_userid', XMLDB_INDEX_UNIQUE, ['itemid', 'userid']);
        $statetable->add_index('courseid_userid', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'userid']);

        if (!$dbman->table_exists($statetable)) {
            $dbman->create_table($statetable);
        }

        // 3. Backfill courseid, gradevalue, and rewardname on existing purchase rows.
        $sql = "SELECT g.*, cm.course AS cmcourse, gi.courseid AS gicourse
                  FROM {local_xpstore_gastos} g
             LEFT JOIN {course_modules} cm ON cm.id = g.itemid AND g.itemtype NOT IN ('M', 'C')
             LEFT JOIN {grade_items} gi ON gi.id = g.itemid AND g.itemtype IN ('M', 'C')
                 WHERE g.courseid = 0 OR g.rewardname IS NULL";
        $records = $DB->get_recordset_sql($sql);

        $catalogcache = [];
        foreach ($records as $rec) {
            $cid = (int)$rec->courseid;
            if ($cid <= 0) {
                $cid = !empty($rec->cmcourse) ? (int)$rec->cmcourse : (!empty($rec->gicourse) ? (int)$rec->gicourse : 0);
            }
            if ($cid > 0 && !isset($catalogcache[$cid])) {
                $catalogcache[$cid] = local_xpstore_parse_catalog($cid);
            }

            $key = $rec->itemtype . (int)$rec->itemid;
            $catitem = ($cid > 0 && isset($catalogcache[$cid][$key])) ? $catalogcache[$cid][$key] : null;

            $needsupdate = false;
            if ((int)$rec->courseid === 0 && $cid > 0) {
                $rec->courseid = $cid;
                $needsupdate = true;
            }
            if ((int)$rec->amount < 0) {
                $rec->amount = 0;
                $needsupdate = true;
            }
            if ($rec->rewardname === null && $catitem && $catitem['nombre'] !== '') {
                $rec->rewardname = $catitem['nombre'];
                $needsupdate = true;
            }
            if ($rec->gradevalue === null && in_array($rec->itemtype, ['G', 'M', 'C'], true)) {
                $rec->gradevalue = $catitem ? (float)$catitem['valornota'] : 0.5;
                $needsupdate = true;
            }
            if ($needsupdate) {
                unset($rec->cmcourse, $rec->gicourse);
                $DB->update_record('local_xpstore_gastos', $rec);
            }
        }
        $records->close();

        // 4. Migrate legacy per-user state keys from {config_plugins} into {local_xpstore_grade_state}.
        $configs = $DB->get_records('config_plugins', ['plugin' => 'local_xpstore']);
        foreach ($configs as $cfg) {
            if (preg_match('/^item_base_(\d+)_(\d+)$/', $cfg->name, $m)) {
                $giid = (int)$m[1];
                $uid = (int)$m[2];
                $gi = $DB->get_record('grade_items', ['id' => $giid], 'id, courseid');
                if ($gi && !$DB->record_exists('local_xpstore_grade_state', ['itemid' => $giid, 'userid' => $uid])) {
                    $gg = $DB->get_record('grade_grades', ['itemid' => $giid, 'userid' => $uid], 'finalgrade');
                    $baseval = ($cfg->value === 'null' || $cfg->value === '') ? null : (float)$cfg->value;
                    $DB->insert_record('local_xpstore_grade_state', (object)[
                        'courseid' => (int)$gi->courseid,
                        'itemid' => $giid,
                        'userid' => $uid,
                        'basegrade' => $baseval,
                        'appliedgrade' => ($gg && $gg->finalgrade !== null) ? (float)$gg->finalgrade : null,
                        'timemodified' => time(),
                    ]);
                }
                unset_config($cfg->name, 'local_xpstore');
            } else if (preg_match('/^course_base_(\d+)_(\d+)$/', $cfg->name, $m)) {
                $cid = (int)$m[1];
                $uid = (int)$m[2];
                $gi = $DB->get_record('grade_items', ['courseid' => $cid, 'itemtype' => 'course'], 'id, courseid');
                if ($gi && !$DB->record_exists('local_xpstore_grade_state', ['itemid' => $gi->id, 'userid' => $uid])) {
                    $gg = $DB->get_record('grade_grades', ['itemid' => $gi->id, 'userid' => $uid], 'finalgrade');
                    $baseval = ($cfg->value === 'null' || $cfg->value === '') ? null : (float)$cfg->value;
                    $DB->insert_record('local_xpstore_grade_state', (object)[
                        'courseid' => $cid,
                        'itemid' => (int)$gi->id,
                        'userid' => $uid,
                        'basegrade' => $baseval,
                        'appliedgrade' => ($gg && $gg->finalgrade !== null) ? (float)$gg->finalgrade : null,
                        'timemodified' => time(),
                    ]);
                }
                unset_config($cfg->name, 'local_xpstore');
            } else if (
                preg_match('/^item_ovr_\d+_\d+$/', $cfg->name) ||
                preg_match('/^course_bonus_\d+_\d+$/', $cfg->name)
            ) {
                unset_config($cfg->name, 'local_xpstore');
            }
        }

        // 5. One-time cleanup of legacy 'xpstore' aggregationstatus values (previously run on every page view).
        $DB->set_field('grade_grades', 'aggregationstatus', 'unknown', ['aggregationstatus' => 'xpstore']);

        // 6. Ensure the forum extension role exists at upgrade time rather than student purchase time.
        local_xpstore_get_or_create_forum_role();

        upgrade_plugin_savepoint(true, 2026103000, 'local', 'xpstore');
    }

    return true;
}
