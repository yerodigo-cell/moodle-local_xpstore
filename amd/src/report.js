define([], function() {
    return {
        init: function() {
            document.querySelectorAll('.card-header-user').forEach(function(el) {
                el.addEventListener('click', function() {
                    var userid = this.getAttribute('data-userid');
                    var logsDiv = document.getElementById('logs-' + userid);
                    var iconDiv = document.getElementById('icon-' + userid);
                    if (logsDiv.style.display === 'block') {
                        logsDiv.style.display = 'none';
                        iconDiv.className = 'fa fa-chevron-down text-muted';
                    } else {
                        logsDiv.style.display = 'block';
                        iconDiv.className = 'fa fa-chevron-up text-muted';
                    }
                });
            });
        }
    };
});
