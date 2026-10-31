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
 * Checks whether a user can manage the XP Store in the given course context.
 *
 * @param context $context The course context.
 * @param int|null $userid Optional user ID (defaults to current $USER).
 * @return bool True if the user can manage the store.
 */
function local_xpstore_can_manage(\context $context, $userid = null): bool {
    return has_capability('local/xpstore:manage', $context, $userid)
        || has_capability('moodle/course:update', $context, $userid);
}

/**
 * Encodes a free-text catalog field so ':' and ',' never corrupt catalog serialization.
 *
 * @param string $value Raw field value.
 * @return string Safe encoded string.
 */
function local_xpstore_encode_catalog_field($value): string {
    $str = trim((string)$value);
    $str = str_replace(['[[XPCOLON]]', '[[XPCOMMA]]'], [':', ','], $str);
    return str_replace([':', ','], ['[[XPCOLON]]', '[[XPCOMMA]]'], $str);
}

/**
 * Decodes a catalog field previously encoded by local_xpstore_encode_catalog_field().
 *
 * @param string $value Stored catalog field string.
 * @return string Decoded plain string.
 */
function local_xpstore_decode_catalog_field($value): string {
    return str_replace(['[[XPCOLON]]', '[[XPCOMMA]]'], [':', ','], trim((string)$value));
}

/**
 * Validates a CSS hex color string, returning $default when invalid.
 *
 * @param string $color Candidate hex color.
 * @param string $default Fallback hex color.
 * @return string Safe hex color.
 */
function local_xpstore_clean_hex_color($color, $default = '#4f46e5'): string {
    $color = trim((string)$color);
    if (preg_match('/^#[0-9a-fA-F]{6}$/', $color) || preg_match('/^#[0-9a-fA-F]{3}$/', $color)) {
        return strtolower($color);
    }
    return $default;
}

/**
 * Validates a FontAwesome icon identifier or custom card image key against the whitelist.
 *
 * @param string $icon Candidate icon identifier.
 * @param string $default Fallback icon identifier.
 * @return string Safe icon identifier.
 */
function local_xpstore_clean_icon($icon, $default = ''): string {
    $icon = trim((string)$icon);
    if ($icon === '') {
        return $default;
    }
    if (local_xpstore_is_custom_card_image($icon) && preg_match('/^img_\d+$/', $icon)) {
        return $icon;
    }
    $available = local_xpstore_get_available_icons();
    if (isset($available[$icon])) {
        return $icon;
    }
    return $default;
}

/**
 * Serializes a structured catalog item array into its colon-separated storage representation.
 *
 * @param array $item Catalog item data.
 * @return string Serialized catalog entry.
 */
function local_xpstore_serialize_catalog_item(array $item): string {
    $tipo = strtoupper(substr(trim((string)($item['tipo'] ?? '')), 0, 1));
    $cmid = max(0, (int)($item['cmid'] ?? 0));
    $costo = max(0, (int)($item['costo'] ?? 0));
    $nombre = local_xpstore_encode_catalog_field($item['nombre'] ?? '');
    $valornota = trim((string)($item['valornota'] ?? '0'));
    if ($valornota === '' || !is_numeric($valornota)) {
        $valornota = '0';
    } else {
        $num = max(0.0, (float)$valornota);
        $valornota = rtrim(rtrim(number_format($num, 4, '.', ''), '0'), '.');
        if ($valornota === '') {
            $valornota = '0';
        }
    }
    $categoria = local_xpstore_encode_catalog_field($item['categoria'] ?? '');
    $limite = max(0, (int)($item['limite'] ?? 0));
    $requisito = max(0, (int)($item['requisito'] ?? 0));
    $hiddenwhenlocked = !empty($item['hiddenwhenlocked']) ? 1 : 0;
    $icono = local_xpstore_clean_icon($item['icono'] ?? '', '');
    $minlevel = max(0, (int)($item['minlevel'] ?? 0));
    $hidebylevel = (!isset($item['hidebylevel']) || (int)$item['hidebylevel'] === 1) ? 1 : 0;
    $bonusmode = (!empty($item['bonusmode']) && (int)$item['bonusmode'] === 1) ? 1 : 0;

    return "{$tipo}{$cmid}:{$costo}:{$nombre}:{$valornota}:{$categoria}:{$limite}:" .
        "{$requisito}:{$hiddenwhenlocked}:{$icono}:{$minlevel}:{$hidebylevel}:{$bonusmode}";
}

/**
 * Parses a single raw catalog item string into a normalized associative array.
 *
 * @param string $rawitem Raw colon-separated catalog entry.
 * @return array|null Normalized item array or null if malformed.
 */
function local_xpstore_parse_catalog_entry($rawitem): ?array {
    $trimmed = trim((string)$rawitem);
    if ($trimmed === '') {
        return null;
    }

    $parts = explode(':', $trimmed);
    if (count($parts) < 2) {
        return null;
    }

    $idcompleto = trim($parts[0]);
    if (strlen($idcompleto) < 2) {
        return null;
    }

    $tipo = strtoupper(substr($idcompleto, 0, 1));
    $validtypes = ['Q', 'A', 'F', 'G', 'M', 'C', 'S', 'U'];
    if (!in_array($tipo, $validtypes, true)) {
        return null;
    }

    $cmid = (int)substr($idcompleto, 1);
    if ($cmid <= 0) {
        return null;
    }

    $costo = max(0, (int)$parts[1]);
    $nombre = isset($parts[2]) ? local_xpstore_decode_catalog_field($parts[2]) : '';
    $valornotaraw = (isset($parts[3]) && trim($parts[3]) !== '' && is_numeric(trim($parts[3])))
        ? trim($parts[3])
        : (in_array($tipo, ['G', 'M', 'C'], true) ? '0.5' : '0');
    $valornotafloat = max(0.0, (float)$valornotaraw);
    $categoria = isset($parts[4]) ? local_xpstore_decode_catalog_field($parts[4]) : '';
    $limite = isset($parts[5]) ? max(0, (int)$parts[5]) : 0;
    $requisito = isset($parts[6]) ? max(0, (int)$parts[6]) : 0;
    $hiddenwhenlocked = (isset($parts[7]) && (int)$parts[7] === 1) ? 1 : 0;
    $icono = isset($parts[8]) ? local_xpstore_clean_icon($parts[8], '') : '';
    $minlevel = isset($parts[9]) ? max(0, (int)$parts[9]) : 0;
    $hidebylevel = (isset($parts[10]) && $parts[10] !== '') ? (((int)$parts[10] === 1) ? 1 : 0) : 1;
    $bonusmode = (isset($parts[11]) && (int)$parts[11] === 1) ? 1 : 0;

    return [
        'key' => $tipo . $cmid,
        'tipo' => $tipo,
        'cmid' => $cmid,
        'costo' => $costo,
        'nombre' => $nombre,
        'valornota' => $valornotaraw,
        'valornota_float' => $valornotafloat,
        'categoria' => $categoria,
        'limite' => $limite,
        'requisito' => $requisito,
        'hiddenwhenlocked' => $hiddenwhenlocked,
        'icono' => $icono,
        'minlevel' => $minlevel,
        'hidebylevel' => $hidebylevel,
        'bonusmode' => $bonusmode,
        'rawitem' => $trimmed,
    ];
}

/**
 * Parses the catalog configuration of a course into normalized item definitions.
 *
 * @param int $courseid The course ID.
 * @param bool $indexed If true (default), keys array by "{tipo}{cmid}"; if false, returns a 0-indexed list.
 * @return array Parsed catalog entries.
 */
function local_xpstore_parse_catalog($courseid, $indexed = true): array {
    $configraw = get_config('local_xpstore', 'catalog_course_' . (int)$courseid) ?: '';
    if (trim($configraw) === '') {
        return [];
    }

    $rawitems = array_filter(explode(',', $configraw), function ($v) {
        return trim($v) !== '';
    });

    $result = [];
    foreach ($rawitems as $idx => $rawitem) {
        $parsed = local_xpstore_parse_catalog_entry($rawitem);
        if (!$parsed) {
            continue;
        }
        $parsed['rawindex'] = $idx;
        if ($indexed) {
            if (!isset($result[$parsed['key']])) {
                $result[$parsed['key']] = $parsed;
            }
        } else {
            $result[] = $parsed;
        }
    }

    return $result;
}

/**
 * Returns a course+user scoped BroadcastChannel identifier so tabs/courses never collide.
 *
 * @param int $courseid The course ID.
 * @param int $userid The user ID.
 * @return string Channel name.
 */
function local_xpstore_get_broadcast_channel($courseid, $userid): string {
    return 'xpstore_balance_sync_' . (int)$courseid . '_' . (int)$userid;
}

/**
 * Extend course navigation with XP Store link.
 *
 * @param navigation_node $parentnode The parent node.
 * @param stdClass $course The course object.
 * @param context_course $context The course context.
 * @return void
 */
function local_xpstore_extend_navigation_course(navigation_node $parentnode, $course, $context) {
    global $DB;

    // 1. Detect if Level Up! is in the course.
    $hasxp = $DB->record_exists('block_instances', [
        'blockname' => 'xp',
        'parentcontextid' => $context->id,
    ]);

    // If Level Up! block is not active in this course, do not show the menu to anyone.
    if (!$hasxp) {
        return;
    }

    // 2. Check the specific visibility configuration for THIS COURSE.
    $showinmenu = get_config('local_xpstore', 'show_menu_course_' . $course->id);

    // If it's not set (false), default it to '0' (hidden).
    if ($showinmenu === false) {
        $showinmenu = '0';
    }

    // MAGIC HERE: If the menu is hidden ('0') AND the user is NOT a store manager/teacher, hide it.
    if ($showinmenu === '0' && !local_xpstore_can_manage($context)) {
        return; // Exit, hiding the menu only for students.
    }

    // 3. If it is a student (and it's visible) or a teacher (always visible), draw the store menu.
    $url = new moodle_url('/local/xpstore/index.php', ['id' => $course->id]);
    $shopnode = navigation_node::create(
        get_string('tiendaxp', 'local_xpstore'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'tiendaxp',
        new pix_icon('i/store', '')
    );
    $parentnode->add_node($shopnode);
}

/**
 * Gets the available balance of a user in a course.
 *
 * @param int $userid The user ID.
 * @param int $courseid The course ID.
 * @return int Available balance.
 */
function local_xpstore_get_balance($userid, $courseid) {
    global $DB;

    $userid = (int)$userid;
    $courseid = (int)$courseid;
    $totalxp = null;

    // Prefer Level Up! (block_xp) authoritative store state (handles sitewide mode, teacher manual edits, and log purges).
    if (class_exists('\block_xp\di')) {
        try {
            $world = \block_xp\di::get('course_world_factory')->get_world($courseid);
            if ($world) {
                $state = $world->get_store()->get_state($userid);
                if ($state && method_exists($state, 'get_xp')) {
                    $totalxp = (int)$state->get_xp();
                }
            }
        } catch (\Throwable $e) {
            $totalxp = null;
        }
    }

    if ($totalxp === null) {
        $xp = $DB->get_field('block_xp', 'xp', ['userid' => $userid, 'courseid' => $courseid]);
        if ($xp === false) {
            $xp = $DB->get_field_sql(
                "SELECT SUM(xp) FROM {block_xp_log} WHERE userid = ? AND courseid = ?",
                [$userid, $courseid]
            );
        }
        $totalxp = (int)($xp ?: 0);
    }

    $sqlg = "SELECT COALESCE(SUM(CASE WHEN g.amount > 0 THEN g.amount ELSE 0 END), 0)
               FROM {local_xpstore_gastos} g
          LEFT JOIN {course_modules} cm ON g.itemid = cm.id AND g.itemtype NOT IN ('M', 'C')
          LEFT JOIN {grade_items} gi ON g.itemid = gi.id AND g.itemtype IN ('M', 'C')
              WHERE g.userid = ?
                AND (g.courseid = ? OR (g.courseid = 0 AND (cm.course = ? OR gi.courseid = ?)))";
    $expenses = (int)$DB->get_field_sql($sqlg, [$userid, $courseid, $courseid, $courseid]);

    return max(0, $totalxp - $expenses);
}

/**
 * Gets the display name for a course total grade item.
 *
 * @param int $itemid The grade_items ID (optional).
 * @return string The course total label.
 */
function local_xpstore_get_course_grade_item_name($itemid = 0) {
    global $DB;
    $customname = '';
    if ($itemid > 0) {
        $customname = (string)$DB->get_field('grade_items', 'itemname', ['id' => $itemid]);
    }
    return trim($customname) !== '' ? format_string($customname) : get_string('coursetotal', 'grades');
}

/**
 * Saves or updates a grade state snapshot in {local_xpstore_grade_state}.
 *
 * @param int $courseid The course ID.
 * @param int $itemid The grade_items ID.
 * @param int $userid The user ID.
 * @param float|null $basegrade Un-bonused base grade.
 * @param float|null $appliedgrade Bonused grade written to {grade_grades}.
 * @return void
 */
function local_xpstore_save_grade_state($courseid, $itemid, $userid, $basegrade, $appliedgrade): void {
    global $DB;

    $existing = $DB->get_record('local_xpstore_grade_state', [
        'itemid' => (int)$itemid,
        'userid' => (int)$userid,
    ]);

    if ($existing) {
        $existing->courseid = (int)$courseid;
        $existing->basegrade = $basegrade;
        $existing->appliedgrade = $appliedgrade;
        $existing->timemodified = time();
        $DB->update_record('local_xpstore_grade_state', $existing);
    } else {
        $DB->insert_record('local_xpstore_grade_state', (object)[
            'courseid' => (int)$courseid,
            'itemid' => (int)$itemid,
            'userid' => (int)$userid,
            'basegrade' => $basegrade,
            'appliedgrade' => $appliedgrade,
            'timemodified' => time(),
        ]);
    }
}

/**
 * Checks whether a grade_grades feedback string contains an XP Store bonus marker.
 *
 * @param string|null $feedback Feedback string from grade_grades.
 * @return bool True if XP Store marker is present.
 */
function local_xpstore_has_bonus_feedback($feedback): bool {
    if (empty($feedback)) {
        return false;
    }
    return strpos($feedback, '<!--xpstore-fb-->') !== false
        || stripos($feedback, 'XP Store') !== false;
}

/**
 * Returns accumulated course bonus points and configured bonus mode for a user in a course.
 *
 * @param int $courseid The course ID.
 * @param int $userid The user ID.
 * @return array ['bonus' => float, 'bonusmode' => int]
 */
function local_xpstore_get_user_course_bonus_info($courseid, $userid): array {
    global $DB;

    $courseid = (int)$courseid;
    $userid = (int)$userid;

    $sql = "SELECT g.id, g.itemid, g.gradevalue, g.bonusmode
              FROM {local_xpstore_gastos} g
         LEFT JOIN {grade_items} gi ON g.itemid = gi.id
             WHERE g.userid = ?
               AND g.itemtype = 'C'
               AND (g.courseid = ? OR (g.courseid = 0 AND gi.courseid = ?))";
    $purchases = $DB->get_records_sql($sql, [$userid, $courseid, $courseid]);

    if (empty($purchases)) {
        return ['bonus' => 0.0, 'bonusmode' => 0];
    }

    $catalog = local_xpstore_parse_catalog($courseid);
    $total = 0.0;
    $bonusmode = null;

    foreach ($purchases as $p) {
        $catkey = 'C' . (int)$p->itemid;
        $catitem = $catalog[$catkey] ?? null;

        if ($p->gradevalue !== null) {
            $total += max(0.0, (float)$p->gradevalue);
        } else if ($catitem) {
            $total += $catitem['valornota_float'];
        } else {
            $total += 0.5;
        }

        if ($bonusmode === null) {
            $bonusmode = $catitem ? (int)$catitem['bonusmode'] : (int)($p->bonusmode ?? 0);
        }
    }

    return [
        'bonus' => $total,
        'bonusmode' => ($bonusmode === 1) ? 1 : 0,
    ];
}

/**
 * Calculates the total accumulated XP Store bonus points for the final course grade of a user.
 *
 * @param int $courseid The course ID.
 * @param int $userid The user ID.
 * @return float Total bonus points to add to the automatic course average.
 */
function local_xpstore_get_user_course_bonus($courseid, $userid) {
    $info = local_xpstore_get_user_course_bonus_info($courseid, $userid);
    return (float)$info['bonus'];
}

/**
 * Merges or removes the XP Store bonus note inside a grade_grades feedback string
 * while preserving any existing teacher feedback.
 *
 * @param string|null $existingfeedback Current feedback text from grade_grades.
 * @param float $totalbonus Total bonus points to display (<= 0 removes the XP Store note).
 * @return string Updated feedback HTML.
 */
function local_xpstore_merge_grade_feedback($existingfeedback, $totalbonus) {
    $current = (string)($existingfeedback ?? '');

    // Remove existing XP Store marker block if present.
    $cleaned = preg_replace('/\s*<!--xpstore-fb-->.*?<!--\/xpstore-fb-->/s', '', $current);
    // Also clean plain legacy one-liner if it matches our exact pattern without markers.
    $cleaned = preg_replace('/^\+\d+(?:\.\d+)?\s+[^\r\n]+XP Store\)?$/iu', '', trim($cleaned));

    if ($totalbonus <= 0) {
        return trim($cleaned);
    }

    $sm = get_string_manager();
    $bonusformatted = rtrim(rtrim(number_format((float)$totalbonus, 2, '.', ''), '0'), '.');
    $bonustext = $sm->string_exists('coursebonus_feedback', 'local_xpstore')
        ? get_string('coursebonus_feedback', 'local_xpstore', $bonusformatted)
        : ('+' . $bonusformatted . ' ' . strtolower(get_string('points', 'local_xpstore')) . ' (XP Store)');

    $wrapped = '<!--xpstore-fb--><div class="xpstore-grade-feedback">' . $bonustext . '</div><!--/xpstore-fb-->';

    if (trim($cleaned) === '') {
        return $wrapped;
    }

    return rtrim($cleaned) . "\n" . $wrapped;
}

/**
 * Checks whether a course regrade is currently being orchestrated by XP Store for a user.
 *
 * @param int $courseid The course ID.
 * @param int $userid The user ID.
 * @param bool|null $set Optional boolean to set or clear the guard state.
 * @return bool Current guard state.
 */
function local_xpstore_course_regrade_guard($courseid, $userid, $set = null): bool {
    static $regrading = [];
    $key = (int)$courseid . '_' . (int)$userid;
    if ($set !== null) {
        if ($set) {
            $regrading[$key] = true;
        } else {
            unset($regrading[$key]);
        }
    }
    return !empty($regrading[$key]);
}

/**
 * Restores a user's course grade item to its un-bonused basegrade, runs Moodle's course
 * grade aggregation so changes are always detected, and re-applies any active course bonus.
 *
 * @param int $courseid The course ID.
 * @param int $userid The user ID.
 * @return void
 */
function local_xpstore_regrade_course_with_bonus($courseid, $userid): void {
    global $DB, $CFG;

    $courseid = (int)$courseid;
    $userid = (int)$userid;
    if (local_xpstore_course_regrade_guard($courseid, $userid)) {
        return;
    }

    local_xpstore_course_regrade_guard($courseid, $userid, true);
    try {
        require_once($CFG->libdir . '/gradelib.php');
        $courseitem = \grade_item::fetch_course_item($courseid);
        if (!$courseitem) {
            return;
        }

        $coursestate = $DB->get_record('local_xpstore_grade_state', [
            'itemid' => $courseitem->id,
            'userid' => $userid,
        ]);

        // Restore pure base grade before Moodle regrades so Moodle always sees true changes
        // even if the newly aggregated course total happens to equal the previous bonused grade.
        if ($coursestate && $DB->record_exists('grade_grades', ['itemid' => $courseitem->id, 'userid' => $userid])) {
            $DB->set_field('grade_grades', 'finalgrade', $coursestate->basegrade, [
                'itemid' => $courseitem->id,
                'userid' => $userid,
            ]);
        }

        $courseitem->force_regrading();
        grade_regrade_final_grades($courseid);

        local_xpstore_sync_course_grade_bonus($courseid, $userid, true, true);
    } catch (\Throwable $e) {
        debugging('local_xpstore_regrade_course_with_bonus error: ' . $e->getMessage(), DEBUG_DEVELOPER);
    } finally {
        local_xpstore_course_regrade_guard($courseid, $userid, false);
    }
}

/**
 * Recalculates the automatic course total grade for a user and applies any accumulated
 * XP Store course bonus points without marking the course grade as overridden.
 *
 * @param int $courseid The course ID.
 * @param int $userid The user ID.
 * @param bool $fromobserver True when called from user_graded observer right after Moodle's aggregation.
 * @param bool $knownpure True when finalgrade was explicitly restored and freshly aggregated by Moodle.
 * @return void
 */
function local_xpstore_sync_course_grade_bonus($courseid, $userid, $fromobserver = false, $knownpure = false) {
    global $DB, $CFG;
    static $syncing = [];

    $courseid = (int)$courseid;
    $userid = (int)$userid;
    $lockkey = $courseid . '_' . $userid;
    if (!empty($syncing[$lockkey])) {
        return;
    }

    // When called manually ($fromobserver === false), run a full base-restore + Moodle regrade pass
    // so the pure automatic course average is always fresh before applying or removing bonuses.
    if (!$fromobserver && !local_xpstore_course_regrade_guard($courseid, $userid)) {
        local_xpstore_regrade_course_with_bonus($courseid, $userid);
        return;
    }

    $syncing[$lockkey] = true;

    try {
        require_once($CFG->libdir . '/gradelib.php');

        $courseitem = \grade_item::fetch_course_item($courseid);
        if (!$courseitem || (int)$courseitem->gradetype !== GRADE_TYPE_VALUE) {
            $syncing[$lockkey] = false;
            return;
        }

        $bonusinfo = local_xpstore_get_user_course_bonus_info($courseid, $userid);
        $totalbonus = (float)$bonusinfo['bonus'];
        $bonusmode = (int)$bonusinfo['bonusmode'];

        $grade = $DB->get_record('grade_grades', [
            'itemid' => $courseitem->id,
            'userid' => $userid,
        ]);
        $state = $DB->get_record('local_xpstore_grade_state', [
            'itemid' => $courseitem->id,
            'userid' => $userid,
        ]);

        $grademax = (float)$courseitem->grademax;
        $grademin = (float)$courseitem->grademin;

        if ($totalbonus > 0) {
            if ($grade) {
                if ($knownpure || !$state) {
                    $purebase = ($grade->finalgrade !== null) ? (float)$grade->finalgrade : null;
                } else if ($fromobserver) {
                    if ($grade->finalgrade === null) {
                        $purebase = null;
                    } else if (
                        $state->appliedgrade !== null &&
                        abs((float)$grade->finalgrade - (float)$state->appliedgrade) < 0.0001
                    ) {
                        // Spurious duplicate observer call where finalgrade already carries our bonus.
                        $purebase = ($state->basegrade !== null) ? (float)$state->basegrade : null;
                    } else {
                        $purebase = (float)$grade->finalgrade;
                    }
                } else {
                    $purebase = ($state->basegrade !== null) ? (float)$state->basegrade : null;
                }

                if ($purebase === null && $bonusmode === 1) {
                    $newvalue = null;
                } else {
                    $baseval = ($purebase !== null) ? $purebase : $grademin;
                    $newvalue = min($grademax, max($grademin, $baseval + $totalbonus));
                }

                local_xpstore_save_grade_state($courseid, $courseitem->id, $userid, $purebase, $newvalue);

                $grade->finalgrade = $newvalue;
                $grade->overridden = 0;
                $grade->aggregationstatus = 'unknown';
                $grade->feedback = local_xpstore_merge_grade_feedback($grade->feedback ?? '', $totalbonus);
                $grade->feedbackformat = FORMAT_HTML;
                $grade->timemodified = time();
                $DB->update_record('grade_grades', $grade);
            } else {
                $newvalue = ($bonusmode === 1)
                    ? null
                    : min($grademax, max($grademin, $grademin + $totalbonus));

                local_xpstore_save_grade_state($courseid, $courseitem->id, $userid, null, $newvalue);

                $newgrade = (object)[
                    'itemid' => $courseitem->id,
                    'userid' => $userid,
                    'rawgrade' => null,
                    'rawgrademax' => $grademax,
                    'rawgrademin' => $grademin,
                    'finalgrade' => $newvalue,
                    'overridden' => 0,
                    'aggregationstatus' => 'unknown',
                    'feedback' => local_xpstore_merge_grade_feedback('', $totalbonus),
                    'feedbackformat' => FORMAT_HTML,
                    'timecreated' => time(),
                    'timemodified' => time(),
                ];
                $DB->insert_record('grade_grades', $newgrade);
            }
        } else {
            if ($grade && ($state || local_xpstore_has_bonus_feedback($grade->feedback ?? ''))) {
                $purebase = $knownpure
                    ? (($grade->finalgrade !== null) ? (float)$grade->finalgrade : null)
                    : (($state && $state->basegrade !== null) ? (float)$state->basegrade : null);
                $grade->finalgrade = $purebase;
                $grade->overridden = 0;
                $grade->aggregationstatus = 'unknown';
                $grade->feedback = local_xpstore_merge_grade_feedback($grade->feedback ?? '', 0);
                $grade->timemodified = time();
                $DB->update_record('grade_grades', $grade);
            } else if ($grade && $grade->aggregationstatus === 'xpstore') {
                $grade->aggregationstatus = 'unknown';
                $DB->update_record('grade_grades', $grade);
            }

            if ($state) {
                $DB->delete_records('local_xpstore_grade_state', ['id' => $state->id]);
            }
        }
    } catch (\Throwable $e) {
        debugging('local_xpstore_sync_course_grade_bonus error: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }

    $syncing[$lockkey] = false;
}

/**
 * Returns the active grade_items record(s) for a course module ('G').
 * Handles modules like Forum and Workshop that create multiple grade_items (itemnumber 0 and 1),
 * selecting the active one(s) where gradetype == GRADE_TYPE_VALUE (1).
 *
 * @param int $courseid The course ID.
 * @param string $itemmodule The module name (e.g. 'assign', 'forum', 'quiz').
 * @param int $iteminstance The module instance ID.
 * @return array Array of stdClass grade_items records.
 */
function local_xpstore_get_mod_grade_items($courseid, $itemmodule, $iteminstance) {
    global $DB;

    $items = $DB->get_records('grade_items', [
        'itemtype' => 'mod',
        'itemmodule' => $itemmodule,
        'iteminstance' => $iteminstance,
        'courseid' => $courseid,
    ], 'itemnumber ASC');

    if (empty($items)) {
        return [];
    }

    $valueitems = [];
    foreach ($items as $gi) {
        if ((int)$gi->gradetype === 1) { // GRADE_TYPE_VALUE.
            $valueitems[] = $gi;
        }
    }

    if (!empty($valueitems)) {
        return $valueitems;
    }

    $active = [];
    foreach ($items as $gi) {
        if ((int)$gi->gradetype !== 0) {
            $active[] = $gi;
        }
    }

    return !empty($active) ? $active : [reset($items)];
}

/**
 * Returns accumulated bonus points and configured bonus mode for a specific activity ('G')
 * or manual grade item ('M') for a user.
 *
 * @param int $courseid The course ID.
 * @param stdClass $gradeitem The grade_items record.
 * @param int $userid The user ID.
 * @return array ['bonus' => float, 'bonusmode' => int]
 */
function local_xpstore_get_user_item_bonus_info($courseid, $gradeitem, $userid): array {
    global $DB;

    $courseid = (int)$courseid;
    $userid = (int)$userid;

    if ($gradeitem->itemtype === 'manual') {
        $type = 'M';
        $itemid = (int)$gradeitem->id;
    } else if ($gradeitem->itemtype === 'mod') {
        $type = 'G';
        $sql = "SELECT cm.id
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                 WHERE cm.course = ? AND m.name = ? AND cm.instance = ?
              ORDER BY cm.deletioninprogress ASC, cm.id DESC";
        $cmids = $DB->get_fieldset_sql($sql, [$courseid, $gradeitem->itemmodule, $gradeitem->iteminstance]);
        $itemid = !empty($cmids) ? (int)reset($cmids) : 0;
        if ($itemid <= 0) {
            return ['bonus' => 0.0, 'bonusmode' => 0];
        }
    } else {
        return ['bonus' => 0.0, 'bonusmode' => 0];
    }

    $purchases = $DB->get_records_select(
        'local_xpstore_gastos',
        'userid = ? AND itemtype = ? AND itemid = ? AND (courseid = ? OR courseid = 0)',
        [$userid, $type, $itemid, $courseid]
    );
    if (empty($purchases)) {
        return ['bonus' => 0.0, 'bonusmode' => 0];
    }

    $catalog = local_xpstore_parse_catalog($courseid);
    $catitem = $catalog[$type . $itemid] ?? null;

    $total = 0.0;
    $bonusmode = null;
    foreach ($purchases as $p) {
        if ($p->gradevalue !== null) {
            $total += max(0.0, (float)$p->gradevalue);
        } else if ($catitem) {
            $total += $catitem['valornota_float'];
        } else {
            $total += 0.5;
        }

        if ($bonusmode === null) {
            $bonusmode = $catitem ? (int)$catitem['bonusmode'] : (int)($p->bonusmode ?? 0);
        }
    }

    return [
        'bonus' => $total,
        'bonusmode' => ($bonusmode === 1) ? 1 : 0,
    ];
}

/**
 * Calculates the total accumulated XP Store bonus points for a specific activity ('G')
 * or manual grade item ('M') for a user.
 *
 * @param int $courseid The course ID.
 * @param stdClass $gradeitem The grade_items record.
 * @param int $userid The user ID.
 * @return float Total bonus points to add to the activity/manual item grade.
 */
function local_xpstore_get_user_item_bonus($courseid, $gradeitem, $userid) {
    $info = local_xpstore_get_user_item_bonus_info($courseid, $gradeitem, $userid);
    return (float)$info['bonus'];
}

/**
 * Queries the module's own grade table (assign_grades, quiz_grades, forum_grades) to get
 * the true un-bonused score awarded by the activity, or sets $known = false if the module
 * does not use a single authoritative grade table.
 *
 * @param stdClass $gradeitem The grade_items record.
 * @param int $userid The user ID.
 * @param bool $known Whether the module's native table was authoritative (out parameter).
 * @return float|null The pure activity grade, or null if not yet graded in the activity.
 */
function local_xpstore_get_module_raw_grade($gradeitem, $userid, &$known = false) {
    global $DB;
    $known = false;

    if ($gradeitem->itemtype !== 'mod') {
        return null;
    }

    $mod = $gradeitem->itemmodule;
    $instance = (int)$gradeitem->iteminstance;
    $userid = (int)$userid;

    if ($mod === 'assign') {
        $known = true;
        $records = $DB->get_records_select(
            'assign_grades',
            'assignment = ? AND userid = ? AND grade >= 0',
            [$instance, $userid],
            'attemptnumber DESC, id DESC',
            'id, grade',
            0,
            1
        );
        $rec = !empty($records) ? reset($records) : null;
        return ($rec && $rec->grade !== null) ? (float)$rec->grade : null;
    } else if ($mod === 'quiz') {
        $known = true;
        $records = $DB->get_records(
            'quiz_grades',
            ['quiz' => $instance, 'userid' => $userid],
            'id DESC',
            'id, grade',
            0,
            1
        );
        $rec = !empty($records) ? reset($records) : null;
        return ($rec && $rec->grade !== null) ? (float)$rec->grade : null;
    } else if ($mod === 'forum' && (int)$gradeitem->itemnumber === 1) {
        // itemnumber 1 is whole-forum grading ({forum_grades}); itemnumber 0 is ratings ({rating}).
        $known = true;
        $records = $DB->get_records_select(
            'forum_grades',
            'forum = ? AND userid = ? AND grade >= 0',
            [$instance, $userid],
            'id DESC',
            'id, grade',
            0,
            1
        );
        $rec = !empty($records) ? reset($records) : null;
        return ($rec && $rec->grade !== null) ? (float)$rec->grade : null;
    }

    return null;
}

/**
 * Synchronizes the XP Store bonus points and feedback annotation for a specific activity ('G')
 * or manual grade item ('M') without locking it as overridden, allowing teachers and activities
 * (Assignments, Quizzes, Forums, etc.) to grade normally at any time.
 *
 * @param int $courseid The course ID.
 * @param stdClass $gradeitem The grade_items record.
 * @param int $userid The user ID.
 * @param bool $fromobserver True when triggered from \core\event\user_graded.
 * @return void
 */
function local_xpstore_sync_item_grade_bonus($courseid, $gradeitem, $userid, $fromobserver = false) {
    global $DB, $CFG;
    static $syncingitem = [];

    if (!$gradeitem || empty($gradeitem->id)) {
        return;
    }

    $courseid = (int)$courseid;
    $userid = (int)$userid;
    $lockkey = (int)$gradeitem->id . '_' . $userid;
    if (!empty($syncingitem[$lockkey])) {
        return;
    }
    $syncingitem[$lockkey] = true;

    try {
        require_once($CFG->libdir . '/gradelib.php');

        // Only apply numeric grade bonuses to GRADE_TYPE_VALUE items (never scales or text-only items).
        if ((int)$gradeitem->gradetype !== GRADE_TYPE_VALUE) {
            $syncingitem[$lockkey] = false;
            return;
        }

        $bonusinfo = local_xpstore_get_user_item_bonus_info($courseid, $gradeitem, $userid);
        $totalbonus = (float)$bonusinfo['bonus'];
        $bonusmode = (int)$bonusinfo['bonusmode'];

        if ($totalbonus <= 0 && $fromobserver) {
            $syncingitem[$lockkey] = false;
            return;
        }

        $state = $DB->get_record('local_xpstore_grade_state', [
            'itemid' => $gradeitem->id,
            'userid' => $userid,
        ]);
        $grade = $DB->get_record('grade_grades', [
            'itemid' => $gradeitem->id,
            'userid' => $userid,
        ]);

        if (
            $totalbonus <= 0 &&
            !$state &&
            (!$grade || !local_xpstore_has_bonus_feedback($grade->feedback ?? ''))
        ) {
            $syncingitem[$lockkey] = false;
            return;
        }

        $grademax = (float)$gradeitem->grademax;
        $grademin = (float)$gradeitem->grademin;

        if ($totalbonus > 0) {
            $modknown = false;
            $modgrade = local_xpstore_get_module_raw_grade($gradeitem, $userid, $modknown);

            if ($grade) {
                if ($modknown && empty($grade->overridden)) {
                    // Authoritative score directly from {assign_grades}, {quiz_grades}, or {forum_grades}.
                    $purebase = $modgrade;
                } else if (!$state) {
                    $purebase = ($grade->finalgrade !== null) ? (float)$grade->finalgrade : null;
                } else if ($grade->finalgrade === null) {
                    $purebase = null;
                } else if (
                    $state->appliedgrade !== null &&
                    abs((float)$grade->finalgrade - (float)$state->appliedgrade) < 0.0001
                ) {
                    $purebase = ($state->basegrade !== null) ? (float)$state->basegrade : null;
                } else {
                    $purebase = (float)$grade->finalgrade;
                }

                if ($purebase === null && $bonusmode === 1) {
                    $newvalue = null;
                } else {
                    $baseval = ($purebase !== null) ? $purebase : $grademin;
                    $newvalue = min($grademax, max($grademin, $baseval + $totalbonus));
                }

                local_xpstore_save_grade_state($courseid, $gradeitem->id, $userid, $purebase, $newvalue);

                // Set both rawgrade and finalgrade so Moodle's grade_regrade_final_grades() preserves
                // finalgrade while overridden remains 0 (keeping Assignment/Forum grading unlocked).
                $grade->rawgrade = $newvalue;
                $grade->finalgrade = $newvalue;
                $grade->overridden = 0;
                $grade->feedback = local_xpstore_merge_grade_feedback($grade->feedback ?? '', $totalbonus);
                $grade->feedbackformat = FORMAT_HTML;
                $grade->timemodified = time();
                $DB->update_record('grade_grades', $grade);
            } else {
                $purebase = $modknown ? $modgrade : null;
                if ($purebase === null && $bonusmode === 1) {
                    $newvalue = null;
                } else {
                    $baseval = ($purebase !== null) ? $purebase : $grademin;
                    $newvalue = min($grademax, max($grademin, $baseval + $totalbonus));
                }

                local_xpstore_save_grade_state($courseid, $gradeitem->id, $userid, $purebase, $newvalue);

                $newgrade = (object)[
                    'itemid' => $gradeitem->id,
                    'userid' => $userid,
                    'rawgrade' => $newvalue,
                    'rawgrademax' => $grademax,
                    'rawgrademin' => $grademin,
                    'finalgrade' => $newvalue,
                    'overridden' => 0,
                    'feedback' => local_xpstore_merge_grade_feedback('', $totalbonus),
                    'feedbackformat' => FORMAT_HTML,
                    'timecreated' => time(),
                    'timemodified' => time(),
                ];
                $DB->insert_record('grade_grades', $newgrade);
            }
        } else {
            if ($grade) {
                $modknown = false;
                $modgrade = local_xpstore_get_module_raw_grade($gradeitem, $userid, $modknown);
                if ($modknown && empty($grade->overridden)) {
                    $origgrade = $modgrade;
                } else if ($state) {
                    $origgrade = ($state->basegrade !== null) ? (float)$state->basegrade : null;
                } else if (
                    $grade->finalgrade !== null &&
                    !empty($grade->feedback) &&
                    preg_match('/\+([0-9]+(?:\.[0-9]+)?)\s+/u', strip_tags($grade->feedback), $m)
                ) {
                    $origgrade = max($grademin, (float)$grade->finalgrade - (float)$m[1]);
                } else {
                    $origgrade = null;
                }

                $grade->rawgrade = $origgrade;
                $grade->finalgrade = $origgrade;
                $grade->overridden = 0;
                $grade->feedback = local_xpstore_merge_grade_feedback($grade->feedback ?? '', 0);
                $grade->timemodified = time();
                $DB->update_record('grade_grades', $grade);
            }

            if ($state) {
                $DB->delete_records('local_xpstore_grade_state', ['id' => $state->id]);
            }
        }
    } catch (\Throwable $e) {
        debugging('local_xpstore_sync_item_grade_bonus error: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }

    $syncingitem[$lockkey] = false;

    if (!$fromobserver) {
        try {
            $gitem = \grade_item::fetch(['id' => $gradeitem->id]);
            if ($gitem) {
                $gitem->force_regrading();
            }
            if (local_xpstore_get_user_course_bonus($courseid, $userid) > 0) {
                local_xpstore_regrade_course_with_bonus($courseid, $userid);
            } else {
                grade_regrade_final_grades($courseid);
            }
        } catch (\Throwable $e) {
            debugging('local_xpstore regrade error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}

/**
 * Gets the current Level Up! (block_xp) level of a user in a course.
 *
 * @param int $userid The user ID.
 * @param int $courseid The course ID.
 * @return int The user's level (defaults to 1).
 */
function local_xpstore_get_user_level($userid, $courseid) {
    global $DB;

    $lvl = null;
    if (class_exists('\block_xp\di')) {
        try {
            $world = \block_xp\di::get('course_world_factory')->get_world($courseid);
            if ($world) {
                $state = $world->get_store()->get_state($userid);
                if ($state && method_exists($state, 'get_level')) {
                    $levelobj = $state->get_level();
                    if ($levelobj && method_exists($levelobj, 'get_level')) {
                        return max(1, (int)$levelobj->get_level());
                    }
                }
            }
        } catch (\Throwable $e) {
            $lvl = null;
        }
    }

    $lvl = $DB->get_field('block_xp', 'lvl', ['userid' => $userid, 'courseid' => $courseid]);
    return $lvl ? max(1, (int)$lvl) : 1;
}

/**
 * Captures a JSON snapshot of any pre-existing user override, group membership, or forum role
 * prior to applying an XP Store benefit so undoing/resetting preserves teacher accommodations.
 *
 * @param int $courseid The course ID.
 * @param int $userid The user ID.
 * @param string $type Product type.
 * @param int $cmid Course module or grade item ID.
 * @param string $rewardname Product display name.
 * @return string|null JSON snapshot or null.
 */
function local_xpstore_capture_prevdata($courseid, $userid, $type, $cmid, $rewardname): ?string {
    global $DB, $CFG;

    $courseid = (int)$courseid;
    $userid = (int)$userid;
    $cmid = (int)$cmid;

    try {
        if ($type === 'Q') {
            $cm = $DB->get_record('course_modules', ['id' => $cmid, 'course' => $courseid]);
            if (!$cm) {
                return null;
            }
            $ov = $DB->get_record('quiz_overrides', ['quiz' => $cm->instance, 'userid' => $userid]);
            if (!$ov) {
                return json_encode(['had_override' => false]);
            }
            return json_encode([
                'had_override' => true,
                'attempts' => $ov->attempts,
                'timeclose' => $ov->timeclose,
                'timeopen' => $ov->timeopen,
                'timelimit' => $ov->timelimit,
                'password' => $ov->password,
            ]);
        } else if ($type === 'A') {
            $cm = $DB->get_record('course_modules', ['id' => $cmid, 'course' => $courseid]);
            if (!$cm) {
                return null;
            }
            $ov = $DB->get_record('assign_overrides', ['assignid' => $cm->instance, 'userid' => $userid]);
            $flag = $DB->get_record('assign_user_flags', ['assignment' => $cm->instance, 'userid' => $userid]);
            return json_encode([
                'had_override' => (bool)$ov,
                'duedate' => $ov ? $ov->duedate : null,
                'cutoffdate' => $ov ? $ov->cutoffdate : null,
                'allowsubmissionsfromdate' => $ov ? $ov->allowsubmissionsfromdate : null,
                'timelimit' => $ov ? $ov->timelimit : null,
                'had_flag' => (bool)$flag,
                'extensionduedate' => $flag ? (int)$flag->extensionduedate : 0,
                'locked' => $flag ? (int)$flag->locked : 0,
            ]);
        } else if ($type === 'S') {
            require_once($CFG->dirroot . '/group/lib.php');
            $group = $DB->get_record('groups', ['courseid' => $courseid, 'name' => $rewardname]);
            return json_encode([
                'was_member' => ($group && groups_is_member($group->id, $userid)),
                'groupname' => $rewardname,
            ]);
        } else if ($type === 'F') {
            $cmcontext = \context_module::instance($cmid, IGNORE_MISSING);
            $role = $DB->get_record('role', ['shortname' => 'xpstore_forum_ext']);
            $hadrole = ($cmcontext && $role)
                ? user_has_role_assignment($userid, $role->id, $cmcontext->id)
                : false;
            return json_encode(['had_role' => (bool)$hadrole]);
        }
    } catch (\Throwable $e) {
        debugging('local_xpstore_capture_prevdata error: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }

    return null;
}

/**
 * Registers a purchase in the database (legacy helper; prefer local_xpstore_process_redemption).
 *
 * @param int $userid The user ID.
 * @param string $type The item type.
 * @param int $cmid The course module ID.
 * @param int $cost The cost of the item.
 * @param int $courseid The course ID.
 * @return bool True if successful, false otherwise.
 */
function local_xpstore_purchase($userid, $type, $cmid, $cost, $courseid) {
    global $DB;
    $cost = max(0, (int)$cost);
    if (local_xpstore_get_balance($userid, $courseid) >= $cost) {
        $record = new stdClass();
        $record->courseid = (int)$courseid;
        $record->userid = (int)$userid;
        $record->itemtype = clean_param($type, PARAM_ALPHA);
        $record->itemid = (int)$cmid;
        $record->amount = $cost;
        $record->timecreated = time();
        $DB->insert_record('local_xpstore_gastos', $record);
        return true;
    }
    return false;
}

/**
 * Atomically validates, locks, charges, and delivers a store redemption using authoritative
 * server-side catalog settings, rolling back the XP deduction if benefit delivery fails.
 *
 * @param stdClass $course The course record.
 * @param int $userid The user ID redeeming the item.
 * @param string $tipo Product type code.
 * @param int $cmid Target course module ID or grade_items ID.
 * @return array ['success' => bool, 'message' => string, ...]
 */
function local_xpstore_process_redemption($course, $userid, $tipo, $cmid): array {
    global $DB;

    $courseid = (int)$course->id;
    $userid = (int)$userid;
    $tipo = strtoupper(trim((string)$tipo));
    $cmid = (int)$cmid;

    // 1. Validate item against authoritative course catalog (never trust client cost/type).
    $catalog = local_xpstore_parse_catalog($courseid);
    $key = $tipo . $cmid;
    if (!isset($catalog[$key])) {
        return [
            'success' => false,
            'code' => 'invalid',
            'message' => get_string('error_transaction', 'local_xpstore'),
        ];
    }

    $catitem = $catalog[$key];
    $realcost = max(0, (int)$catitem['costo']);
    $limite = max(0, (int)$catitem['limite']);
    $requisito = max(0, (int)$catitem['requisito']);
    $minlevel = max(0, (int)$catitem['minlevel']);
    $rewardname = ($catitem['nombre'] !== '') ? $catitem['nombre'] : 'VIP';
    $gradevalue = in_array($tipo, ['G', 'M', 'C'], true) ? (float)$catitem['valornota_float'] : null;
    $bonusmode = in_array($tipo, ['G', 'M', 'C'], true) ? (int)$catitem['bonusmode'] : 0;

    // 2. Verify target activity or grade item belongs to this course and is valid before charging XP.
    $modinfo = get_fast_modinfo($course);
    $activityname = '';
    $activityurl = '';

    if ($tipo === 'M' || $tipo === 'C') {
        $expecteditemtype = ($tipo === 'C') ? 'course' : 'manual';
        $gi = $DB->get_record('grade_items', [
            'id' => $cmid,
            'courseid' => $courseid,
            'itemtype' => $expecteditemtype,
        ]);
        if (!$gi || (int)$gi->gradetype !== 1) {
            return [
                'success' => false,
                'code' => 'invalid',
                'message' => get_string('error_transaction', 'local_xpstore'),
            ];
        }
        $activityname = ($tipo === 'C')
            ? local_xpstore_get_course_grade_item_name($cmid)
            : format_string($gi->itemname);
    } else {
        if (!isset($modinfo->cms[$cmid]) || !empty($modinfo->cms[$cmid]->deletioninprogress)) {
            return [
                'success' => false,
                'code' => 'invalid',
                'message' => get_string('error_transaction', 'local_xpstore'),
            ];
        }
        $cm = $modinfo->cms[$cmid];
        $activityname = format_string($cm->name);
        if ($cm->url) {
            $activityurl = $cm->url->out(false);
        } else {
            $activityurl = (new moodle_url('/mod/' . $cm->modname . '/view.php', ['id' => $cm->id]))->out(false);
        }

        if ($tipo === 'G') {
            $gitems = local_xpstore_get_mod_grade_items($courseid, $cm->modname, $cm->instance);
            $hasvaluegrade = false;
            foreach ($gitems as $gitem) {
                if ((int)$gitem->gradetype === 1) {
                    $hasvaluegrade = true;
                    break;
                }
            }
            if (!$hasvaluegrade) {
                return [
                    'success' => false,
                    'code' => 'invalid',
                    'message' => get_string('error_transaction', 'local_xpstore'),
                ];
            }
        }
    }

    // 3. Enforce minimum Level Up! level requirement.
    if ($minlevel > 0) {
        $userlevel = local_xpstore_get_user_level($userid, $courseid);
        if ($userlevel < $minlevel) {
            return [
                'success' => false,
                'code' => 'req_failed',
                'message' => get_string('error_minlevel', 'local_xpstore', $minlevel),
            ];
        }
    }

    // 4. Enforce prerequisite activity completion (fail-closed if prerequisite activity was deleted).
    if ($requisito > 0) {
        require_once($GLOBALS['CFG']->libdir . '/completionlib.php');
        if (!isset($modinfo->cms[$requisito]) || !empty($modinfo->cms[$requisito]->deletioninprogress)) {
            return [
                'success' => false,
                'code' => 'req_failed',
                'message' => get_string('error_prereq', 'local_xpstore'),
            ];
        }
        $completion = new \completion_info($course);
        $cdata = $completion->get_data($modinfo->cms[$requisito], false, $userid);
        if (
            $cdata->completionstate != COMPLETION_COMPLETE &&
            $cdata->completionstate != COMPLETION_COMPLETE_PASS
        ) {
            return [
                'success' => false,
                'code' => 'req_failed',
                'message' => get_string('error_prereq', 'local_xpstore'),
            ];
        }
    }

    // 5. Serialize concurrent redemptions per user via Moodle's lock API.
    $lockfactory = \core\lock\lock_config::get_lock_factory('local_xpstore_purchase');
    $lock = $lockfactory ? $lockfactory->get_lock('user_' . $userid, 10) : false;
    if (!$lock) {
        return [
            'success' => false,
            'code' => 'error',
            'message' => get_string('error_transaction', 'local_xpstore'),
        ];
    }

    try {
        if ($limite > 0) {
            $purchasedcount = (int)$DB->count_records_select(
                'local_xpstore_gastos',
                'userid = ? AND itemtype = ? AND itemid = ? AND (courseid = ? OR courseid = 0)',
                [$userid, $tipo, $cmid, $courseid]
            );
            if ($purchasedcount >= $limite) {
                return [
                    'success' => false,
                    'code' => 'limit',
                    'message' => get_string('limitreached', 'local_xpstore'),
                ];
            }
        }

        if (local_xpstore_get_balance($userid, $courseid) < $realcost) {
            return [
                'success' => false,
                'code' => 'insufficient',
                'message' => get_string('insuficiente', 'local_xpstore'),
            ];
        }

        $prevdata = local_xpstore_capture_prevdata($courseid, $userid, $tipo, $cmid, $rewardname);

        $record = (object)[
            'courseid' => $courseid,
            'userid' => $userid,
            'itemtype' => $tipo,
            'itemid' => $cmid,
            'amount' => $realcost,
            'gradevalue' => $gradevalue,
            'bonusmode' => $bonusmode,
            'rewardname' => $rewardname,
            'prevdata' => $prevdata,
            'timecreated' => time(),
        ];
        $gastoid = $DB->insert_record('local_xpstore_gastos', $record);

        $delivered = local_xpstore_deliver_product($userid, $cmid, $tipo, $courseid);
        if (!$delivered) {
            $DB->delete_records('local_xpstore_gastos', ['id' => $gastoid]);
            return [
                'success' => false,
                'code' => 'error',
                'message' => get_string('error_transaction', 'local_xpstore'),
            ];
        }

        $displayname = ($catitem['nombre'] !== '') ? $catitem['nombre'] : $activityname;

        return [
            'success' => true,
            'code' => 'ok',
            'gastoid' => $gastoid,
            'cost' => $realcost,
            'itemname' => $displayname,
            'activityname' => $activityname,
            'activityurl' => $activityurl,
            'isunlock' => ($tipo === 'S' || $tipo === 'U'),
        ];
    } finally {
        $lock->release();
    }
}

/**
 * Delivers the purchased product to the user modifying overrides or grades.
 *
 * @param int $userid The user ID.
 * @param int $cmid The course module ID or grade item ID.
 * @param string $type The item type.
 * @param int $courseid The course ID (optional).
 * @return bool True if successful, false otherwise.
 */
function local_xpstore_deliver_product($userid, $cmid, $type, $courseid = null) {
    global $DB, $CFG;

    try {
        $userid = (int)$userid;
        $cmid = (int)$cmid;
        $cm = null;

        if ($type !== 'M' && $type !== 'C') {
            $params = ['id' => $cmid];
            if ($courseid) {
                $params['course'] = (int)$courseid;
            }
            $cm = $DB->get_record('course_modules', $params, '*', MUST_EXIST);
            if (!$courseid) {
                $courseid = (int)$cm->course;
            }
        } else {
            $params = ['id' => $cmid];
            if ($courseid) {
                $params['courseid'] = (int)$courseid;
            }
            $gi = $DB->get_record('grade_items', $params, '*', MUST_EXIST);
            if (!$courseid) {
                $courseid = (int)$gi->courseid;
            }
        }

        $catalog = local_xpstore_parse_catalog($courseid);
        $catitem = $catalog[$type . $cmid] ?? null;
        $rewardname = ($catitem && $catitem['nombre'] !== '') ? $catitem['nombre'] : 'VIP';

        if ($type === 'Q') {
            $quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
            $override = $DB->get_record('quiz_overrides', ['quiz' => $quiz->id, 'userid' => $userid]);
            $extension = time() + (3 * DAYSECS);

            $curattempts = ($override && $override->attempts !== null)
                ? (int)$override->attempts
                : (int)$quiz->attempts;
            // In Moodle quiz, attempts == 0 means unlimited; never cap an unlimited quiz.
            if ($curattempts === 0) {
                $newattempts = ($override && $override->attempts !== null) ? 0 : null;
            } else {
                $newattempts = $curattempts + 1;
            }

            $curclose = ($override && $override->timeclose !== null)
                ? (int)$override->timeclose
                : (int)$quiz->timeclose;
            // In Moodle quiz, timeclose == 0 means no closing deadline; never impose one if unset.
            if ($curclose === 0) {
                $newclose = ($override && $override->timeclose !== null) ? $override->timeclose : null;
            } else {
                $newclose = max($curclose, $extension);
            }

            if ($override) {
                $override->attempts = $newattempts;
                $override->timeclose = $newclose;
                $DB->update_record('quiz_overrides', $override);
            } else if ($newattempts !== null || $newclose !== null) {
                $new = (object)[
                    'quiz' => $quiz->id,
                    'userid' => $userid,
                    'attempts' => $newattempts,
                    'timeclose' => $newclose,
                ];
                $DB->insert_record('quiz_overrides', $new);
            }
        } else if ($type === 'A') {
            $assign = $DB->get_record('assign', ['id' => $cm->instance], '*', MUST_EXIST);
            $override = $DB->get_record('assign_overrides', ['assignid' => $assign->id, 'userid' => $userid]);
            $flag = $DB->get_record('assign_user_flags', ['assignment' => $assign->id, 'userid' => $userid]);

            // Extend by +3 days from whichever is later: right now or the current due/extension date.
            $curdue = max(
                (int)$assign->duedate,
                (int)($override->duedate ?? 0),
                (int)($flag->extensionduedate ?? 0)
            );
            $newdeadline = max(time(), $curdue) + (3 * DAYSECS);

            $curcutoff = ($override && $override->cutoffdate !== null)
                ? (int)$override->cutoffdate
                : (int)$assign->cutoffdate;
            $newcutoff = ($curcutoff === 0)
                ? (($override && $override->cutoffdate !== null) ? $override->cutoffdate : null)
                : max($curcutoff, $newdeadline);

            if ($override) {
                $override->duedate = $newdeadline;
                $override->cutoffdate = $newcutoff;
                $DB->update_record('assign_overrides', $override);
            } else {
                $new = (object)[
                    'assignid' => $assign->id,
                    'userid' => $userid,
                    'duedate' => $newdeadline,
                    'cutoffdate' => $newcutoff,
                ];
                $DB->insert_record('assign_overrides', $new);
            }

            if ($flag) {
                $flag->extensionduedate = $newdeadline;
                $flag->locked = 0;
                $DB->update_record('assign_user_flags', $flag);
            } else {
                $newflag = (object)[
                    'assignment' => $assign->id,
                    'userid' => $userid,
                    'extensionduedate' => $newdeadline,
                    'locked' => 0,
                    'mailed' => 0,
                ];
                $DB->insert_record('assign_user_flags', $newflag);
            }
        } else if ($type === 'G' || $type === 'M') {
            if ($type === 'G') {
                $itemmodule = $DB->get_field('modules', 'name', ['id' => $cm->module]);
                $gradeitems = local_xpstore_get_mod_grade_items($cm->course, $itemmodule, $cm->instance);
                foreach ($gradeitems as $gradeitem) {
                    local_xpstore_sync_item_grade_bonus($courseid, $gradeitem, $userid, false);
                }
            } else {
                $gradeitem = $DB->get_record('grade_items', ['id' => $cmid, 'courseid' => $courseid]);
                if ($gradeitem) {
                    local_xpstore_sync_item_grade_bonus($courseid, $gradeitem, $userid, false);
                }
            }
        } else if ($type === 'C') {
            local_xpstore_sync_course_grade_bonus($courseid, $userid, false);
        } else if ($type === 'S') {
            require_once($CFG->dirroot . '/group/lib.php');
            $group = $DB->get_record('groups', ['courseid' => $cm->course, 'name' => $rewardname]);

            if (!$group) {
                $newgroup = new stdClass();
                $newgroup->courseid = $cm->course;
                $newgroup->name = $rewardname;
                $groupid = groups_create_group($newgroup);
            } else {
                $groupid = $group->id;
            }

            if (!groups_is_member($groupid, $userid)) {
                groups_add_member($groupid, $userid);
            }
        } else if ($type === 'F') {
            $context = context_module::instance($cm->id);
            $roleid = local_xpstore_get_or_create_forum_role();
            if (!$roleid) {
                return false;
            }
            role_assign($roleid, $userid, $context->id);
        }

        if ($courseid) {
            rebuild_course_cache($courseid, true);
        }
        return true;
    } catch (\Throwable $e) {
        debugging('local_xpstore_deliver_product error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }
}

/**
 * Restores non-grade benefits ('Q', 'A', 'S', 'F') using captured prevdata so pre-existing
 * teacher accommodations (extra time, passwords, open dates, groups, roles) are preserved.
 *
 * @param int $courseid The course ID.
 * @param int $userid The user ID.
 * @param string $type Benefit type ('Q', 'A', 'S', 'F').
 * @param int $itemid Course module ID.
 * @param int|null $instance Module instance ID (for Q/A).
 * @param array $deletedrows Array of deleted {local_xpstore_gastos} records for this user+item.
 * @param int $remainingcount Number of remaining active purchases for this user+item.
 * @param string $fallbackrewardname Fallback group name from catalog if rewardname was null.
 * @return void
 */
function local_xpstore_revert_nongrade_benefit(
    int $courseid,
    int $userid,
    string $type,
    int $itemid,
    $instance,
    array $deletedrows,
    int $remainingcount,
    string $fallbackrewardname = 'VIP'
): void {
    global $DB, $CFG;

    // Sort deleted rows chronologically so the earliest snapshot reflects the state prior to XP Store.
    usort($deletedrows, function ($a, $b) {
        if ((int)$a->timecreated === (int)$b->timecreated) {
            return (int)$a->id <=> (int)$b->id;
        }
        return (int)$a->timecreated <=> (int)$b->timecreated;
    });

    $earliest = reset($deletedrows);
    $earliestprev = ($earliest && !empty($earliest->prevdata))
        ? json_decode($earliest->prevdata, true)
        : null;
    if (!is_array($earliestprev)) {
        $earliestprev = null;
    }

    if ($type === 'Q' && !empty($instance)) {
        $override = $DB->get_record('quiz_overrides', ['quiz' => $instance, 'userid' => $userid]);
        if (!$override) {
            return;
        }

        if ($remainingcount === 0) {
            if ($earliestprev && !empty($earliestprev['had_override'])) {
                $override->attempts = $earliestprev['attempts'] ?? null;
                $override->timeclose = $earliestprev['timeclose'] ?? null;
                $DB->update_record('quiz_overrides', $override);
            } else if (
                $override->timelimit !== null ||
                $override->timeopen !== null ||
                ($override->password !== null && $override->password !== '')
            ) {
                // Keep teacher's accommodation fields, clear only attempts/timeclose.
                $override->attempts = null;
                $override->timeclose = null;
                $DB->update_record('quiz_overrides', $override);
            } else {
                $DB->delete_records('quiz_overrides', ['id' => $override->id]);
            }
        } else if ($override->attempts !== null && (int)$override->attempts > 1) {
            $removedcount = max(1, count($deletedrows));
            $override->attempts = max(1, (int)$override->attempts - $removedcount);
            $DB->update_record('quiz_overrides', $override);
        }
    } else if ($type === 'A' && !empty($instance)) {
        $override = $DB->get_record('assign_overrides', ['assignid' => $instance, 'userid' => $userid]);
        $flag = $DB->get_record('assign_user_flags', ['assignment' => $instance, 'userid' => $userid]);

        if ($remainingcount === 0) {
            if ($override) {
                if ($earliestprev && !empty($earliestprev['had_override'])) {
                    $override->duedate = $earliestprev['duedate'] ?? null;
                    $override->cutoffdate = $earliestprev['cutoffdate'] ?? null;
                    $DB->update_record('assign_overrides', $override);
                } else if (
                    $override->timelimit !== null ||
                    $override->allowsubmissionsfromdate !== null
                ) {
                    $override->duedate = null;
                    $override->cutoffdate = null;
                    $DB->update_record('assign_overrides', $override);
                } else {
                    $DB->delete_records('assign_overrides', ['id' => $override->id]);
                }
            }
            if ($flag) {
                $flag->extensionduedate = $earliestprev ? (int)($earliestprev['extensionduedate'] ?? 0) : 0;
                $DB->update_record('assign_user_flags', $flag);
            }
        } else {
            $assign = $DB->get_record('assign', ['id' => $instance]);
            $mindue = $assign ? (int)$assign->duedate : 0;
            $delta = max(1, count($deletedrows)) * 3 * DAYSECS;
            if ($override && $override->duedate !== null) {
                $override->duedate = max($mindue, (int)$override->duedate - $delta);
                if ($override->cutoffdate !== null && (int)$override->cutoffdate > 0) {
                    $mincut = $assign ? (int)$assign->cutoffdate : 0;
                    $override->cutoffdate = max($mincut, $override->duedate);
                }
                $DB->update_record('assign_overrides', $override);
            }
            if ($flag && !empty($flag->extensionduedate)) {
                $flag->extensionduedate = max($mindue, (int)$flag->extensionduedate - $delta);
                $DB->update_record('assign_user_flags', $flag);
            }
        }
    } else if ($type === 'S' && $remainingcount === 0) {
        if ($earliestprev && !empty($earliestprev['was_member'])) {
            return;
        }
        require_once($CFG->dirroot . '/group/lib.php');
        $groupnames = [$fallbackrewardname => true];
        foreach ($deletedrows as $drow) {
            if (!empty($drow->rewardname)) {
                $groupnames[$drow->rewardname] = true;
            }
        }
        if ($earliestprev && !empty($earliestprev['groupname'])) {
            $groupnames[$earliestprev['groupname']] = true;
        }
        foreach (array_keys($groupnames) as $gname) {
            if ($gname === '') {
                continue;
            }
            $group = $DB->get_record('groups', ['courseid' => $courseid, 'name' => $gname]);
            if ($group && groups_is_member($group->id, $userid)) {
                groups_remove_member($group->id, $userid);
            }
        }
    } else if ($type === 'F' && $remainingcount === 0) {
        if ($earliestprev && !empty($earliestprev['had_role'])) {
            return;
        }
        $cmcontext = context_module::instance($itemid, IGNORE_MISSING);
        $role = $DB->get_record('role', ['shortname' => 'xpstore_forum_ext']);
        if ($cmcontext && $role) {
            role_unassign($role->id, $userid, $cmcontext->id);
        }
    }
}

/**
 * Add elements to the reset course form.
 *
 * @param MoodleQuickForm $mform The form.
 * @return void
 */
function local_xpstore_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'tiendagamificadaheader', get_string('tiendaxp', 'local_xpstore'));
    $mform->addElement('advcheckbox', 'reset_tiendagamificada_gastos', get_string('resethistory', 'local_xpstore'));
}

/**
 * Scans grade_grades and local_xpstore_grade_state in a course and reverts any leftover
 * XP Store bonus or feedback for items where the user no longer has active bonus purchases.
 *
 * @param int $courseid The course ID.
 * @param int|null $userid Optional user ID filter.
 * @return void
 */
function local_xpstore_cleanup_orphan_grade_bonuses($courseid, $userid = null) {
    global $DB;

    $courseid = (int)$courseid;
    $like1 = $DB->sql_like('gg.feedback', '?', false);
    $like2 = $DB->sql_like('gg.feedback', '?', false);
    $params = [$courseid, $courseid, '%xpstore-fb%', '%XP Store%'];
    $usersql = '';
    if ($userid !== null) {
        $usersql = ' AND (gg.userid = ? OR gs.userid = ?)';
        $params[] = (int)$userid;
        $params[] = (int)$userid;
    }

    $sql = "SELECT DISTINCT gi.id AS giid, COALESCE(gg.userid, gs.userid) AS targetuserid, gi.*
              FROM {grade_items} gi
         LEFT JOIN {grade_grades} gg ON gg.itemid = gi.id
         LEFT JOIN {local_xpstore_grade_state} gs ON gs.itemid = gi.id AND (gg.id IS NULL OR gs.userid = gg.userid)
             WHERE gi.courseid = ?
               AND (gs.courseid = ? OR {$like1} OR {$like2}){$usersql}";
    $records = $DB->get_recordset_sql($sql, $params);

    foreach ($records as $row) {
        $uid = (int)$row->targetuserid;
        if ($uid <= 0) {
            continue;
        }
        if ($row->itemtype === 'course') {
            if (local_xpstore_get_user_course_bonus($courseid, $uid) <= 0) {
                local_xpstore_sync_course_grade_bonus($courseid, $uid, false);
            }
        } else if ($row->itemtype === 'mod' || $row->itemtype === 'manual') {
            if (local_xpstore_get_user_item_bonus($courseid, $row, $uid) <= 0) {
                local_xpstore_sync_item_grade_bonus($courseid, $row, $uid, false);
            }
        }
    }
    $records->close();
}

/**
 * Resets purchases for a course (or a single user in a course) and restores any grades,
 * overrides, groups, and roles modified by XP Store benefits.
 *
 * @param int $courseid The course ID.
 * @param int|null $userid Optional user ID to reset only that user.
 * @return void
 */
function local_xpstore_reset_purchases($courseid, $userid = null) {
    global $DB, $CFG;

    $courseid = (int)$courseid;
    $params = [$courseid, $courseid, $courseid];
    $usersql = '';
    if ($userid !== null) {
        $usersql = ' AND g.userid = ?';
        $params[] = (int)$userid;
    }

    // Collect all purchases before deleting them so we can revert benefits accurately.
    $sql = "SELECT g.*, cm.module, cm.instance
              FROM {local_xpstore_gastos} g
         LEFT JOIN {course_modules} cm ON g.itemid = cm.id AND g.itemtype NOT IN ('M', 'C')
         LEFT JOIN {grade_items} gi ON g.itemid = gi.id AND g.itemtype IN ('M', 'C')
             WHERE (g.courseid = ? OR (g.courseid = 0 AND (cm.course = ? OR gi.courseid = ?))){$usersql}";
    $records = $DB->get_records_sql($sql, $params);

    if (empty($records)) {
        if ($userid !== null) {
            local_xpstore_sync_course_grade_bonus($courseid, (int)$userid, false);
        }
        local_xpstore_cleanup_orphan_grade_bonuses($courseid, $userid);
        return;
    }

    $catalog = local_xpstore_parse_catalog($courseid);

    $gastoids = [];
    $coursebonususers = [];
    $itembonuses = [];
    $nongradegroups = [];

    foreach ($records as $rec) {
        $gastoids[] = $rec->id;
        $uid = (int)$rec->userid;
        if ($rec->itemtype === 'C') {
            $coursebonususers[$uid] = $uid;
        } else if ($rec->itemtype === 'G' || $rec->itemtype === 'M') {
            $key = $rec->itemtype . '_' . $rec->itemid . '_' . $uid;
            if (!isset($itembonuses[$key])) {
                $itembonuses[$key] = [
                    'userid' => $uid,
                    'itemtype' => $rec->itemtype,
                    'itemid' => (int)$rec->itemid,
                    'module' => $rec->module,
                    'instance' => $rec->instance,
                ];
            }
        } else if (in_array($rec->itemtype, ['Q', 'A', 'S', 'F'], true)) {
            $ngkey = $rec->itemtype . '_' . (int)$rec->itemid . '_' . $uid;
            if (!isset($nongradegroups[$ngkey])) {
                $nongradegroups[$ngkey] = [
                    'userid' => $uid,
                    'type' => $rec->itemtype,
                    'itemid' => (int)$rec->itemid,
                    'instance' => $rec->instance,
                    'rows' => [],
                ];
            }
            $nongradegroups[$ngkey]['rows'][] = $rec;
        }
    }

    $DB->delete_records_list('local_xpstore_gastos', 'id', $gastoids);

    foreach ($nongradegroups as $ng) {
        $catkey = $ng['type'] . $ng['itemid'];
        $fallbackname = isset($catalog[$catkey]) && $catalog[$catkey]['nombre'] !== ''
            ? $catalog[$catkey]['nombre']
            : 'VIP';
        local_xpstore_revert_nongrade_benefit(
            $courseid,
            $ng['userid'],
            $ng['type'],
            $ng['itemid'],
            $ng['instance'],
            $ng['rows'],
            0,
            $fallbackname
        );
    }

    require_once($CFG->libdir . '/gradelib.php');

    // Revert activity ('G') and manual item ('M') bonuses and feedback.
    foreach ($itembonuses as $ib) {
        $gradeitems = [];
        if ($ib['itemtype'] === 'G' && !empty($ib['module'])) {
            $itemmodule = $DB->get_field('modules', 'name', ['id' => $ib['module']]);
            if ($itemmodule) {
                $gradeitems = local_xpstore_get_mod_grade_items($courseid, $itemmodule, $ib['instance']);
            }
        } else if ($ib['itemtype'] === 'M') {
            $gi = $DB->get_record('grade_items', ['id' => $ib['itemid'], 'courseid' => $courseid]);
            if ($gi) {
                $gradeitems = [$gi];
            }
        }

        foreach ($gradeitems as $gradeitem) {
            local_xpstore_sync_item_grade_bonus($courseid, $gradeitem, (int)$ib['userid'], false);
        }
    }

    // Revert course total ('C') bonuses and feedback.
    foreach ($coursebonususers as $uid) {
        local_xpstore_sync_course_grade_bonus($courseid, $uid, false);
    }

    local_xpstore_cleanup_orphan_grade_bonuses($courseid, $userid);
    rebuild_course_cache($courseid, true);
}

/**
 * Deletes/undoes a single purchase record in a course, refunds the XP spent,
 * and reverts the corresponding bonus or benefit ('C', 'G', 'M', 'Q', 'A', 'S', 'F').
 *
 * @param int $courseid The course ID.
 * @param int $gastoid The ID of the purchase in {local_xpstore_gastos}.
 * @return int|null The user ID whose purchase was undone, or null if not found.
 */
function local_xpstore_delete_single_purchase($courseid, $gastoid) {
    global $DB, $CFG;

    $courseid = (int)$courseid;
    $sql = "SELECT g.*, cm.module, cm.instance
              FROM {local_xpstore_gastos} g
         LEFT JOIN {course_modules} cm ON g.itemid = cm.id AND g.itemtype NOT IN ('M', 'C')
         LEFT JOIN {grade_items} gi ON g.itemid = gi.id AND g.itemtype IN ('M', 'C')
             WHERE g.id = ?
               AND (g.courseid = ? OR (g.courseid = 0 AND (cm.course = ? OR gi.courseid = ?)))";
    $rec = $DB->get_record_sql($sql, [(int)$gastoid, $courseid, $courseid, $courseid]);
    if (!$rec) {
        return null;
    }

    $userid = (int)$rec->userid;
    $type = $rec->itemtype;
    $itemid = (int)$rec->itemid;

    // Delete the single purchase row (refunds XP balance and restores stock/limits).
    $DB->delete_records('local_xpstore_gastos', ['id' => $rec->id]);

    $remainingcount = (int)$DB->count_records_select(
        'local_xpstore_gastos',
        'userid = ? AND itemtype = ? AND itemid = ? AND (courseid = ? OR courseid = 0)',
        [$userid, $type, $itemid, $courseid]
    );

    $catalog = local_xpstore_parse_catalog($courseid);
    $catitem = $catalog[$type . $itemid] ?? null;
    $fallbackname = ($catitem && $catitem['nombre'] !== '') ? $catitem['nombre'] : 'VIP';

    if ($type === 'C') {
        local_xpstore_sync_course_grade_bonus($courseid, $userid, false);
    } else if ($type === 'G' || $type === 'M') {
        require_once($CFG->libdir . '/gradelib.php');
        $gradeitems = [];
        if ($type === 'G' && !empty($rec->module)) {
            $itemmodule = $DB->get_field('modules', 'name', ['id' => $rec->module]);
            if ($itemmodule) {
                $gradeitems = local_xpstore_get_mod_grade_items($courseid, $itemmodule, $rec->instance);
            }
        } else if ($type === 'M') {
            $gi = $DB->get_record('grade_items', ['id' => $itemid, 'courseid' => $courseid]);
            if ($gi) {
                $gradeitems = [$gi];
            }
        }

        foreach ($gradeitems as $gradeitem) {
            local_xpstore_sync_item_grade_bonus($courseid, $gradeitem, $userid, false);
        }
    } else if (in_array($type, ['Q', 'A', 'S', 'F'], true)) {
        local_xpstore_revert_nongrade_benefit(
            $courseid,
            $userid,
            $type,
            $itemid,
            $rec->instance,
            [$rec],
            $remainingcount,
            $fallbackname
        );
    }

    rebuild_course_cache($courseid, true);
    return $userid;
}

/**
 * Reset userdata for the store.
 *
 * @param stdClass $data The data submitted.
 * @return array Status array.
 */
function local_xpstore_reset_userdata($data) {
    if (!empty($data->reset_tiendagamificada_gastos)) {
        local_xpstore_reset_purchases($data->courseid);
    }
    return [];
}

/**
 * Gets or creates the custom role for extending forum deadlines.
 *
 * @return int|bool The role ID or false.
 */
function local_xpstore_get_or_create_forum_role() {
    global $DB, $CFG;
    require_once($CFG->libdir . '/accesslib.php');

    $shortname = 'xpstore_forum_ext';
    if ($role = $DB->get_record('role', ['shortname' => $shortname])) {
        return $role->id;
    }

    // Never attempt system-level role creation from an unprivileged student session;
    // the role is created during plugin install/upgrade or teacher configuration.
    $syscontext = context_system::instance();
    if (
        !CLI_SCRIPT &&
        !during_initial_install() &&
        empty($CFG->upgraderunning) &&
        !has_capability('moodle/role:manage', $syscontext) &&
        !is_siteadmin()
    ) {
        return false;
    }

    $roleid = create_role(
        'XP Store Forum Extension',
        $shortname,
        'Auto-created role to allow extending forum deadlines via XP Store.'
    );
    if ($roleid) {
        set_role_contextlevels($roleid, [CONTEXT_MODULE]);
        assign_capability('mod/forum:canoverridecutoff', CAP_ALLOW, $roleid, $syscontext->id, true);
        return $roleid;
    }

    return false;
}

/**
 * Automates group restriction injection for Special rewards.
 *
 * @param int $cmid The course module ID.
 * @param int $groupid The group ID to restrict access to.
 * @param int $courseid The course ID for cache rebuild.
 * @param int $replacegroupid Optional previous group ID to replace in-place when renaming a reward.
 */
function local_xpstore_apply_special_restriction($cmid, $groupid, $courseid, $replacegroupid = 0) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');

    $cm = $DB->get_record('course_modules', ['id' => (int)$cmid, 'course' => (int)$courseid]);
    if (!$cm) {
        return;
    }

    $availability = $cm->availability;
    $newrestriction = ['type' => 'group', 'id' => (int)$groupid];

    if (empty($availability)) {
        $tree = [
            'op' => '&',
            'c' => [$newrestriction],
            'showc' => [false],
            'show' => false,
        ];
        $availability = json_encode($tree);
    } else {
        $tree = json_decode($availability, true);
        if (!is_array($tree) || !isset($tree['c']) || !isset($tree['showc'])) {
            // Invalid JSON, overwrite safely.
            $tree = [
                'op' => '&',
                'c' => [$newrestriction],
                'showc' => [false],
                'show' => false,
            ];
            $availability = json_encode($tree);
        } else {
            $exists = false;
            $replaced = false;
            foreach ($tree['c'] as $idx => $cond) {
                if (isset($cond['type']) && $cond['type'] === 'group' && isset($cond['id'])) {
                    if ((int)$cond['id'] === (int)$groupid) {
                        $exists = true;
                        break;
                    }
                    if ($replacegroupid > 0 && (int)$cond['id'] === (int)$replacegroupid) {
                        $tree['c'][$idx]['id'] = (int)$groupid;
                        $replaced = true;
                    }
                }
            }
            if ($exists && $replaced) {
                // Remove duplicate group entries if both old and new were present.
                $seen = [];
                $newc = [];
                $newshowc = [];
                foreach ($tree['c'] as $idx => $cond) {
                    $gkey = (isset($cond['type']) && $cond['type'] === 'group' && isset($cond['id']))
                        ? 'group_' . (int)$cond['id']
                        : 'other_' . $idx;
                    if (!isset($seen[$gkey])) {
                        $seen[$gkey] = true;
                        $newc[] = $cond;
                        $newshowc[] = $tree['showc'][$idx] ?? false;
                    }
                }
                $tree['c'] = $newc;
                $tree['showc'] = $newshowc;
                $availability = json_encode($tree);
            } else if ($replaced) {
                $availability = json_encode($tree);
            } else if (!$exists) {
                $tree['c'][] = $newrestriction;
                $tree['showc'][] = false; // Add 'eye closed'.
                $tree['show'] = false; // Ensure root show is false.
                $availability = json_encode($tree);
            }
        }
    }

    if ($cm->availability !== $availability) {
        $DB->set_field('course_modules', 'availability', $availability, ['id' => $cmid]);
        rebuild_course_cache($courseid, true);
    }
}

/**
 * Helper to get the navigation tabs data.
 *
 * @param int $courseid
 * @param string $activetab The currently active tab (store, redemptions, analytics, products, settings)
 * @return array
 */
function local_xpstore_get_navigation_data($courseid, $activetab) {
    return [
        'storeurl' => (new moodle_url('/local/xpstore/index.php', ['id' => $courseid]))->out(false),
        'reporturl' => (new moodle_url('/local/xpstore/report.php', ['id' => $courseid]))->out(false),
        'analyticsurl' => (new moodle_url('/local/xpstore/analytics.php', ['id' => $courseid]))->out(false),
        'productsurl' => (new moodle_url('/local/xpstore/config.php', ['id' => $courseid, 'tab' => 'products']))->out(false),
        'settingsurl' => (new moodle_url('/local/xpstore/config.php', ['id' => $courseid, 'tab' => 'settings']))->out(false),
        'str_store' => get_string('tiendaxp', 'local_xpstore'),
        'str_audit' => get_string('audit', 'local_xpstore'),
        'str_analytics' => get_string('analytics', 'local_xpstore'),
        'str_products' => get_string('products', 'local_xpstore'),

        'str_settings' => get_string('settings', 'local_xpstore'),
        'str_preview' => get_string('preview', 'local_xpstore'),
        'str_style' => get_string('style', 'local_xpstore'),

        'tab_store' => ($activetab === 'store'),
        'tab_redemptions' => ($activetab === 'redemptions'),
        'tab_analytics' => ($activetab === 'analytics'),
        'tab_products' => ($activetab === 'products'),
        'tab_settings' => ($activetab === 'settings'),
    ];
}

/**
 * Automates xpstore restriction injection for Unlock rewards.
 *
 * @param int $cmid The course module ID.
 * @param string $productid The product ID to restrict access to (e.g. U123).
 * @param int $courseid The course ID for cache rebuild.
 * @param int $hiddenwhenlocked Whether to completely hide the activity when locked (1 = hide, 0 = show restricted).
 */
function local_xpstore_apply_unlock_restriction($cmid, $productid, $courseid, $hiddenwhenlocked = 0) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');

    $cm = $DB->get_record('course_modules', ['id' => (int)$cmid, 'course' => (int)$courseid]);
    if (!$cm) {
        return;
    }

    $availability = $cm->availability;
    $newrestriction = ['type' => 'xpstore', 'productid' => (string)$productid];

    $showstatus = empty($hiddenwhenlocked) ? true : false;

    if (empty($availability)) {
        $tree = [
            'op' => '&',
            'c' => [$newrestriction],
            'showc' => [$showstatus],
            'show' => $showstatus,
        ];
        $availability = json_encode($tree);
    } else {
        $tree = json_decode($availability, true);
        if (!is_array($tree) || !isset($tree['c']) || !isset($tree['showc'])) {
            // Invalid JSON, overwrite safely.
            $tree = [
                'op' => '&',
                'c' => [$newrestriction],
                'showc' => [$showstatus],
                'show' => $showstatus,
            ];
            $availability = json_encode($tree);
        } else {
            // Check if this specific xpstore restriction already exists.
            $exists = false;
            foreach ($tree['c'] as $index => $cond) {
                if (
                    isset($cond['type']) && $cond['type'] === 'xpstore' &&
                    isset($cond['productid']) && $cond['productid'] == $productid
                ) {
                    $exists = true;
                    // Update existing visibility.
                    if (isset($tree['showc'][$index])) {
                        $tree['showc'][$index] = $showstatus;
                    }
                    break;
                }
            }
            if (!$exists) {
                $tree['c'][] = $newrestriction;
                $tree['showc'][] = $showstatus;
            }

            // Recompute root show property based on all showc properties.
            $allhidden = true;
            foreach ($tree['showc'] as $sc) {
                if ($sc) {
                    $allhidden = false;
                    break;
                }
            }
            $tree['show'] = !$allhidden;
            $availability = json_encode($tree);
        }
    }

    if ($cm->availability !== $availability) {
        $DB->set_field('course_modules', 'availability', $availability, ['id' => $cmid]);
        rebuild_course_cache($courseid, true);
    }
}

/**
 * Returns the default FontAwesome icon name for a given reward type.
 *
 * @param string $type The reward type code (Q, A, F, G, M, C, S, U).
 * @return string The FontAwesome icon name (without 'fa-').
 */
function local_xpstore_get_default_card_icon($type) {
    $iconmap = [
        'Q' => 'bolt',
        'A' => 'file-text',
        'F' => 'comments',
        'G' => 'star',
        'M' => 'star',
        'C' => 'star',
        'S' => 'unlock',
        'U' => 'unlock',
    ];
    return $iconmap[$type] ?? 'gift';
}

/**
 * Returns the list of available FontAwesome icons for categories and cards.
 *
 * @return array Associative array of icon_name => localized_label.
 */
function local_xpstore_get_available_icons() {
    $sm = get_string_manager();
    return [
        'bolt' => get_string('icon_bolt', 'local_xpstore'),
        'clock-o' => $sm->string_exists('icon_clock', 'local_xpstore')
            ? get_string('icon_clock', 'local_xpstore') : 'Clock',
        'file-text' => $sm->string_exists('icon_file', 'local_xpstore')
            ? get_string('icon_file', 'local_xpstore') : 'Document',
        'comments' => $sm->string_exists('icon_comments', 'local_xpstore')
            ? get_string('icon_comments', 'local_xpstore') : 'Comments',
        'star' => get_string('icon_star', 'local_xpstore'),
        'unlock' => $sm->string_exists('icon_unlock', 'local_xpstore')
            ? get_string('icon_unlock', 'local_xpstore') : 'Unlock',
        'key' => $sm->string_exists('icon_key', 'local_xpstore')
            ? get_string('icon_key', 'local_xpstore') : 'Key',
        'trophy' => get_string('icon_trophy', 'local_xpstore'),
        'medal' => get_string('icon_medal', 'local_xpstore'),
        'award' => get_string('icon_award', 'local_xpstore'),
        'money' => $sm->string_exists('icon_money', 'local_xpstore')
            ? get_string('icon_money', 'local_xpstore') : 'Money',
        'diamond' => get_string('icon_diamond', 'local_xpstore'),
        'gift' => get_string('icon_gift', 'local_xpstore'),
        'shopping-cart' => get_string('icon_cart', 'local_xpstore'),
        'shield' => get_string('icon_shield', 'local_xpstore'),
        'gamepad' => get_string('icon_gamepad', 'local_xpstore'),
        'heart' => get_string('icon_heart', 'local_xpstore'),
        'ticket' => get_string('icon_ticket', 'local_xpstore'),
        'magic' => get_string('icon_magic', 'local_xpstore'),
        'rocket' => get_string('icon_rocket', 'local_xpstore'),
        'graduation-cap' => get_string('icon_graduation', 'local_xpstore'),
        'puzzle-piece' => get_string('icon_puzzle', 'local_xpstore'),
        'music' => get_string('icon_music', 'local_xpstore'),
        'camera' => get_string('icon_camera', 'local_xpstore'),
        'globe' => get_string('icon_globe', 'local_xpstore'),
        'book' => get_string('icon_book', 'local_xpstore'),
    ];
}

/**
 * Checks whether the given card icon value represents a custom uploaded image.
 *
 * @param string $icon The icon identifier stored in the catalog.
 * @return bool True if it is a custom uploaded image identifier (img_<itemid>).
 */
function local_xpstore_is_custom_card_image($icon) {
    return is_string($icon) && strpos($icon, 'img_') === 0 && (int)substr($icon, 4) > 0;
}

/**
 * Resolves the Moodle pluginfile URL for a custom uploaded card image.
 *
 * @param int $courseid The course ID.
 * @param string $icon The icon identifier (e.g. img_123456).
 * @return string|null The image URL or null if not found.
 */
function local_xpstore_get_card_image_url($courseid, $icon) {
    if (!local_xpstore_is_custom_card_image($icon)) {
        return null;
    }
    $itemid = (int)substr($icon, 4);
    $context = context_course::instance($courseid);
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'local_xpstore', 'cardimage', $itemid, 'id DESC', false);
    if (!empty($files)) {
        $file = reset($files);
        return moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        )->out(false);
    }
    return null;
}

/**
 * Deletes a custom uploaded card image from Moodle file storage.
 *
 * @param int $courseid The course ID.
 * @param string $icon The icon identifier (e.g. img_123456).
 * @return void
 */
function local_xpstore_delete_card_image($courseid, $icon) {
    if (!local_xpstore_is_custom_card_image($icon)) {
        return;
    }
    $itemid = (int)substr($icon, 4);
    $context = context_course::instance($courseid);
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'local_xpstore', 'cardimage', $itemid);
}

/**
 * Validates and saves an uploaded card image (max 256 KB) into Moodle file storage.
 *
 * @param int $courseid The course ID.
 * @param array $uploadedfile The $_FILES entry for the uploaded image.
 * @param string $existingicon Optional previous icon identifier to clean up if it was a custom image.
 * @param string|null $errormsg Output error message if validation fails.
 * @return string|false New icon identifier ('img_' . $itemid) on success, or false on failure.
 */
function local_xpstore_save_card_image($courseid, array $uploadedfile, $existingicon = '', &$errormsg = null) {
    $maxbytes = 256 * 1024; // 256 KB.
    $sm = get_string_manager();

    if (
        !isset($uploadedfile['error']) ||
        $uploadedfile['error'] === UPLOAD_ERR_INI_SIZE ||
        $uploadedfile['error'] === UPLOAD_ERR_FORM_SIZE ||
        (isset($uploadedfile['size']) && $uploadedfile['size'] > $maxbytes)
    ) {
        $errormsg = $sm->string_exists('error_imagesize', 'local_xpstore')
            ? get_string('error_imagesize', 'local_xpstore')
            : 'La imagen supera el tamaño máximo permitido de 256 KB.';
        return false;
    }

    if (
        $uploadedfile['error'] !== UPLOAD_ERR_OK ||
        empty($uploadedfile['tmp_name']) ||
        !is_uploaded_file($uploadedfile['tmp_name'])
    ) {
        $errormsg = get_string('error');
        return false;
    }

    $imginfo = @getimagesize($uploadedfile['tmp_name']);
    $allowedmimes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
    if ($imginfo === false || !in_array($imginfo['mime'], $allowedmimes, true)) {
        $errormsg = $sm->string_exists('error_imagetype', 'local_xpstore')
            ? get_string('error_imagetype', 'local_xpstore')
            : 'Formato de imagen no válido (usa PNG, JPG, GIF o WEBP).';
        return false;
    }

    // Delete old custom image if replacing one.
    if (!empty($existingicon)) {
        local_xpstore_delete_card_image($courseid, $existingicon);
    }

    $context = context_course::instance($courseid);
    $fs = get_file_storage();

    // Generate a unique 9-digit itemid to bust browser caches upon image replacement.
    $itemid = (int)(substr((string)time(), -6) . mt_rand(100, 999));
    while ($fs->get_area_files($context->id, 'local_xpstore', 'cardimage', $itemid, 'id', false)) {
        $itemid = (int)(substr((string)time(), -6) . mt_rand(100, 999));
    }

    $filename = clean_param($uploadedfile['name'], PARAM_FILE);
    if (empty($filename)) {
        $filename = 'card_' . $itemid . '.png';
    }

    $filerecord = [
        'contextid' => $context->id,
        'component' => 'local_xpstore',
        'filearea'  => 'cardimage',
        'itemid'    => $itemid,
        'filepath'  => '/',
        'filename'  => $filename,
    ];

    $fs->create_file_from_pathname($filerecord, $uploadedfile['tmp_name']);
    return 'img_' . $itemid;
}

/**
 * Returns the student-facing badge label for a product type.
 *
 * @param string $tipo Product type character ('Q', 'A', 'F', 'G', 'M', 'C', 'S', 'U').
 * @param string $boost Optional bonus points value for grade boost products.
 * @return string Localized student-facing label.
 */
function local_xpstore_get_student_badge_label($tipo, $boost = '0') {
    $tipo = strtoupper((string)$tipo);
    $booststr = (string)$boost;
    if (in_array($tipo, ['G', 'M', 'C'], true)) {
        if ($booststr !== '' && $booststr !== '0') {
            return '+' . $booststr . ' ' . get_string('points', 'local_xpstore');
        }
        return get_string('type_g', 'local_xpstore');
    }
    if ($tipo === 'S' || $tipo === 'U') {
        return get_string('badge_unlock', 'local_xpstore');
    }
    if ($tipo === 'A' || $tipo === 'F') {
        return get_string('badge_extension', 'local_xpstore');
    }
    if ($tipo === 'Q') {
        return get_string('badge_extra_attempt', 'local_xpstore');
    }
    return '';
}

/**
 * Serves files from the local_xpstore file areas.
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object.
 * @param context $context The context.
 * @param string $filearea The name of the file area.
 * @param array $args Extra arguments (itemid, path).
 * @param bool $forcedownload Whether or not force download.
 * @param array $options Additional options affecting the file serving.
 * @return bool False if the file not found, just send the file otherwise and do not return anything.
 */
function local_xpstore_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_COURSE) {
        return false;
    }

    if ($filearea !== 'cardimage') {
        return false;
    }

    require_login($course, false, $cm);

    $itemid = (int)array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_xpstore', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 86400, 0, $forcedownload, $options);
}
