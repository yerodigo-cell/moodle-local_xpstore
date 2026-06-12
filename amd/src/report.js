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
 * @copyright  2026 Yeison Díaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    return {
        init: function() {
            document.querySelectorAll('.card-header-user').forEach(function(el) {
                el.addEventListener('click', function() {
                    var userid = this.getAttribute('data-userid');
                    var logsDiv = document.getElementById('logs-' + userid);
                    var iconDiv = document.getElementById('icon-' + userid);
                    if (logsDiv.classList.contains('expanded')) {
                        logsDiv.classList.remove('expanded');
                        iconDiv.className = 'fa fa-chevron-down text-muted';
                    } else {
                        logsDiv.classList.add('expanded');
                        iconDiv.className = 'fa fa-chevron-up text-muted';
                    }
                });
            });
        }
    };
});
