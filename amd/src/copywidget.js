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

define(['core/notification'], function(notification) {
    return {
        init: function(copyAlertStr, copyErrorStr) {
            document.addEventListener('click', function(e) {
                var btn = e.target.closest('[data-action="copy-widget"]');
                if (btn) {
                    var code = btn.getAttribute('data-code');

                    // Fallback for non-HTTPS (like localhost)
                    var fallbackCopyTextToClipboard = function(text) {
                        var textArea = document.createElement("textarea");
                        textArea.value = text;
                        textArea.style.top = "0";
                        textArea.style.left = "0";
                        textArea.style.position = "fixed";
                        document.body.appendChild(textArea);
                        textArea.focus();
                        textArea.select();
                        try {
                            var successful = document.execCommand('copy');
                            if (successful) {
                                notification.addNotification({message: copyAlertStr, type: 'success'});
                            } else {
                                notification.addNotification({message: copyErrorStr, type: 'error'});
                            }
                        } catch (err) {
                            notification.addNotification({message: copyErrorStr, type: 'error'});
                        }
                        document.body.removeChild(textArea);
                    };

                    if (!navigator.clipboard) {
                        fallbackCopyTextToClipboard(code);
                        return;
                    }

                    navigator.clipboard.writeText(code).then(
                        function() {
                            notification.addNotification({message: copyAlertStr, type: 'success'});
                            return true;
                        }
                    ).catch(function() {
                        fallbackCopyTextToClipboard(code);
                        return false;
                    });
                }
            });
        }
    };
});
