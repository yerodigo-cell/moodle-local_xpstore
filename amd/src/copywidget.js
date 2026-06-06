define([], function() {
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
                                alert(copyAlertStr);
                            } else {
                                alert(copyErrorStr);
                            }
                        } catch (err) {
                            alert(copyErrorStr + ": " + err);
                        }
                        document.body.removeChild(textArea);
                    };

                    if (!navigator.clipboard) {
                        fallbackCopyTextToClipboard(code);
                        return;
                    }

                    navigator.clipboard.writeText(code).then(
                        function() {
                            alert(copyAlertStr);
                        },
                        function() {
                            fallbackCopyTextToClipboard(code);
                        }
                    );
                }
            });
        }
    };
});
