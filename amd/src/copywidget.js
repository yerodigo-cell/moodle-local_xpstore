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
                                notification.addNotification({ message: copyAlertStr, type: 'success' });
                            } else {
                                notification.addNotification({ message: copyErrorStr, type: 'error' });
                            }
                        } catch (err) {
                            notification.addNotification({ message: copyErrorStr, type: 'error' });
                        }
                        document.body.removeChild(textArea);
                    };

                    if (!navigator.clipboard) {
                        fallbackCopyTextToClipboard(code);
                        return;
                    }

                    navigator.clipboard.writeText(code).then(
                        function() {
                            notification.addNotification({ message: copyAlertStr, type: 'success' });
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
