/**
 * SFP Page Config - Dashboard JavaScript
 *
 * Handles AJAX save of cursusdata, adding/removing startmomenten and
 * individual dates, and toggling the hidden state of posts.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var table = document.getElementById('sfp-dashboard-table');
        if (!table) return;

        /* =================================================================
         * Collect cursusdata from a row's inputs
         * =============================================================== */

        function collectData(row) {
            var container = row.querySelector('.sfp-startmomenten');
            if (!container) return [];
            var smRows = container.querySelectorAll('.sfp-sm-row');
            var result = [];
            smRows.forEach(function (smRow) {
                var inputs = smRow.querySelectorAll('.sfp-date-input');
                var dates = [];
                inputs.forEach(function (input) {
                    if (input.value) dates.push(input.value);
                });
                if (dates.length) result.push({ data: dates });
            });
            return result;
        }

        /* =================================================================
         * Save a single row via AJAX
         * =============================================================== */

        table.addEventListener('click', function (e) {
            var btn = e.target.closest('.sfp-save-row');
            if (!btn) return;

            var postId = btn.getAttribute('data-post-id');
            var row = btn.closest('tr');
            var data = collectData(row);

            btn.disabled = true;
            btn.textContent = 'Opslaan...';

            var formData = new FormData();
            formData.append('action', 'sfp_save_cursusdata');
            formData.append('nonce', sfpDashboard.nonce);
            formData.append('post_id', postId);
            formData.append('cursusdata', JSON.stringify(data));

            fetch(sfpDashboard.ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
            })
                .then(function (res) {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(function (res) {
                    btn.disabled = false;
                    btn.textContent = 'Opslaan';
                    if (res.success) {
                        // Update the "eerstvolgende" column.
                        var cell = row.querySelector('.sfp-eerstvolgende');
                        if (cell) cell.textContent = res.data.eerstvolgende;
                        btn.textContent = 'Opgeslagen!';
                        setTimeout(function () { btn.textContent = 'Opslaan'; }, 1500);
                    } else {
                        alert('Fout bij opslaan: ' + (res.data || 'Onbekend'));
                    }
                })
                .catch(function (err) {
                    btn.disabled = false;
                    btn.textContent = 'Opslaan';
                    alert('Fout bij opslaan: ' + err.message);
                    console.error('SFP Dashboard save error:', err);
                });
        });

        /* =================================================================
         * Add a startmoment
         * =============================================================== */

        table.addEventListener('click', function (e) {
            var btn = e.target.closest('.sfp-add-startmoment');
            if (!btn) return;

            var postId = btn.getAttribute('data-post-id');
            var container = btn.closest('td').querySelector('.sfp-startmomenten');
            var existingRows = container.querySelectorAll('.sfp-sm-row');
            var newIndex = existingRows.length;

            var html = '<div class="sfp-sm-row" data-sm-index="' + newIndex + '">'
                + '<strong>Groep ' + (newIndex + 1) + ':</strong> '
                + '<span class="sfp-dates">'
                + '<input type="date" class="sfp-date-input" value="" '
                + 'data-sm="' + newIndex + '" data-di="0" '
                + 'style="width:140px;margin:2px 4px 2px 0;" />'
                + '</span> '
                + '<button type="button" class="button-link sfp-add-date" data-sm="' + newIndex + '" title="Dag toevoegen">+</button> '
                + '<button type="button" class="button-link sfp-remove-sm" data-sm="' + newIndex + '" title="Groep verwijderen" style="color:#b32d2e;">&times;</button>'
                + '</div>';

            container.insertAdjacentHTML('beforeend', html);
        });

        /* =================================================================
         * Add a date within a startmoment
         * =============================================================== */

        table.addEventListener('click', function (e) {
            var btn = e.target.closest('.sfp-add-date');
            if (!btn) return;

            var smRow = btn.closest('.sfp-sm-row');
            var datesSpan = smRow.querySelector('.sfp-dates');
            var existingInputs = datesSpan.querySelectorAll('.sfp-date-input');
            var smIndex = btn.getAttribute('data-sm');
            var newDi = existingInputs.length;

            var input = document.createElement('input');
            input.type = 'date';
            input.className = 'sfp-date-input';
            input.setAttribute('data-sm', smIndex);
            input.setAttribute('data-di', newDi);
            input.style.cssText = 'width:140px;margin:2px 4px 2px 0;';

            datesSpan.appendChild(input);
        });

        /* =================================================================
         * Remove a startmoment
         * =============================================================== */

        table.addEventListener('click', function (e) {
            var btn = e.target.closest('.sfp-remove-sm');
            if (!btn) return;

            var smRow = btn.closest('.sfp-sm-row');
            if (confirm('Groep verwijderen?')) {
                smRow.remove();
            }
        });

        /* =================================================================
         * Toggle hide/show
         * =============================================================== */

        table.addEventListener('click', function (e) {
            var btn = e.target.closest('.sfp-toggle-hide');
            if (!btn) return;

            var postId = btn.getAttribute('data-post-id');
            var row = btn.closest('tr');

            var formData = new FormData();
            formData.append('action', 'sfp_toggle_hide');
            formData.append('nonce', sfpDashboard.nonce);
            formData.append('post_id', postId);

            fetch(sfpDashboard.ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
            })
                .then(function (res) { return res.json(); })
                .then(function (res) {
                    if (res.success) {
                        if (res.data.hidden) {
                            row.style.opacity = '0.5';
                            btn.innerHTML = '&#x1f441;';
                            btn.title = 'Tonen';
                        } else {
                            row.style.opacity = '1';
                            btn.innerHTML = '&#x1f6ab;';
                            btn.title = 'Verbergen';
                        }
                    }
                });
        });
    });
})();
