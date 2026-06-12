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
 * @package    local_xpstore
 * @copyright  2026 Yeison Díaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    return {
        init: function() {
            var typeSelect = document.getElementById('type_select');
            var activitySelect = document.getElementById('activity_select');
            if (!typeSelect || !activitySelect) return;

            var allOptions = Array.from(activitySelect.querySelectorAll('option:not([disabled])'));
            
            // Fallback original options for older browsers that don't support hiding options well
            var backupOptions = allOptions.map(function(opt) {
                return {
                    value: opt.value,
                    text: opt.text,
                    modname: opt.getAttribute('data-modname'),
                    selected: opt.selected
                };
            });

            function filterActivities() {
                var selectedType = typeSelect.value;
                var currentSelectedValue = activitySelect.value;
                
                // Clear all options except the first disabled one
                while (activitySelect.options.length > 1) {
                    activitySelect.remove(1);
                }

                backupOptions.forEach(function(optData) {
                    var show = false;
                    if (selectedType === 'Q') {
                        show = (optData.modname === 'quiz');
                    } else if (selectedType === 'A') {
                        show = (optData.modname === 'assign');
                    } else if (selectedType === 'G') {
                        var gradables = ['quiz', 'assign', 'forum', 'workshop', 'scorm', 'lesson', 'h5pactivity'];
                        show = gradables.indexOf(optData.modname) !== -1;
                    } else {
                        show = true;
                    }

                    if (show) {
                        var newOption = document.createElement('option');
                        newOption.value = optData.value;
                        newOption.text = optData.text;
                        newOption.setAttribute('data-modname', optData.modname);
                        if (optData.value === currentSelectedValue) {
                            newOption.selected = true;
                        }
                        activitySelect.appendChild(newOption);
                    }
                });
                
                // Handle Bonus field visibility
                var bonusContainer = document.getElementById('bonus_container');
                if (bonusContainer) {
                    if (selectedType === 'G') {
                        bonusContainer.style.display = '';
                    } else {
                        bonusContainer.style.display = 'none';
                    }
                }
                
                // Reset selection if it disappeared
                if (activitySelect.selectedIndex === -1) {
                    activitySelect.selectedIndex = 0;
                }
            }

            typeSelect.addEventListener('change', filterActivities);
            filterActivities();
        }
    };
});
