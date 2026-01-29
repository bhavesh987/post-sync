document.addEventListener('DOMContentLoaded', function () {

    var addTargetBtn = document.getElementById('ps-add-target');
    if (addTargetBtn) {
        addTargetBtn.addEventListener('click', function () {
            var table = document.getElementById('ps-targets-table').getElementsByTagName('tbody')[0];
            var rowCount = table.rows.length;
            var index = Date.now();

            var row = table.insertRow(-1); // Append to end

            var cell1 = row.insertCell(0);
            var cell2 = row.insertCell(1);
            var cell3 = row.insertCell(2);

            cell1.innerHTML = '<input type="url" name="ps_targets[' + index + '][url]" class="regular-text" placeholder="https://target.com">';
            cell2.innerHTML = '<em>' + post_sync_l10n.save_to_generate + '</em>';
            cell3.innerHTML = '<button type="button" class="button ps-remove-row">' + post_sync_l10n.remove + '</button>';
        });
    }

    document.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('ps-remove-row')) {
            e.target.closest('tr').remove();
        }
    });

    // Handle Mode Switch Visibility
    var modeRadios = document.getElementsByName('ps_mode');
    var hostSettings = document.getElementById('host-settings');
    var targetSettings = document.getElementById('target-settings');

    if (modeRadios.length > 0 && hostSettings && targetSettings) {
        for (var i = 0; i < modeRadios.length; i++) {
            modeRadios[i].addEventListener('change', function () {
                if (this.value === 'host') {
                    hostSettings.style.display = 'block';
                    targetSettings.style.display = 'none';
                } else {
                    hostSettings.style.display = 'none';
                    targetSettings.style.display = 'block';
                }
            });
        }
    }
});
