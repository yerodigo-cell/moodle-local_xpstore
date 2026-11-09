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
 * XP Store (local_xpstore) report accordion controller.
 *
 * @module     local_xpstore/report
 * @copyright  2026 EduPlugins Studio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    var initialized = false;

    return {
        init: function() {
            if (initialized) {
                return;
            }
            initialized = true;

            document.addEventListener('click', function(e) {
                var header = e.target.closest('.card-header-user');
                if (!header) {
                    return;
                }

                // Do not toggle accordion when clicking action links or buttons inside the header.
                if (e.target.closest('a, button')) {
                    return;
                }

                var userid = header.getAttribute('data-userid');
                var logsDiv = document.getElementById('logs-' + userid);
                var iconDiv = document.getElementById('icon-' + userid);

                if (!logsDiv) {
                    return;
                }

                var isExpanded = logsDiv.classList.contains('expanded');

                // Collapse all currently expanded logs.
                document.querySelectorAll('.card-body-logs.expanded').forEach(function(expandedDiv) {
                    expandedDiv.classList.remove('expanded');
                    var expandedId = expandedDiv.id.replace('logs-', '');
                    var expandedIcon = document.getElementById('icon-' + expandedId);
                    if (expandedIcon) {
                        expandedIcon.className = 'fa fa-chevron-down text-muted';
                    }
                });

                // Expand the clicked one if it wasn't already expanded.
                if (!isExpanded) {
                    logsDiv.classList.add('expanded');
                    if (iconDiv) {
                        iconDiv.className = 'fa fa-chevron-up text-muted';
                    }
                }
            });
        }
    };
});
