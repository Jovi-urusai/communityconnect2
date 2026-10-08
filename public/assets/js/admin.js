(function () {
    'use strict';

    /* ---------- Preserve scroll position across delete/status reloads ----------
       Admin actions (delete, status change) submit a form and reload the page,
       which otherwise resets both the page scroll and any .table-scroll's
       horizontal scroll back to the top-left. We stash the scroll position in
       sessionStorage right before the page navigates away, then restore it
       once the new page loads. */
    var SCROLL_KEY = 'cc_admin_scroll:' + window.location.pathname;

    function saveScrollPosition() {
        var tableScrolls = document.querySelectorAll('.table-scroll');
        var state = {
            y: window.scrollY,
            tables: Array.prototype.map.call(tableScrolls, function (el) { return el.scrollLeft; })
        };
        try {
            sessionStorage.setItem(SCROLL_KEY, JSON.stringify(state));
        } catch (e) { /* sessionStorage unavailable — ignore */ }
    }

    function restoreScrollPosition() {
        var raw;
        try {
            raw = sessionStorage.getItem(SCROLL_KEY);
        } catch (e) {
            return;
        }
        if (!raw) return;

        var state;
        try {
            state = JSON.parse(raw);
        } catch (e) {
            return;
        }

        function apply() {
            window.scrollTo(0, state.y || 0);
            var tableScrolls = document.querySelectorAll('.table-scroll');
            (state.tables || []).forEach(function (left, i) {
                if (tableScrolls[i]) tableScrolls[i].scrollLeft = left;
            });
        }

        apply();
        // Re-apply once everything (images, etc.) has finished loading, since
        // late layout shifts can throw off the first scrollTo.
        window.addEventListener('load', function () {
            apply();
            try { sessionStorage.removeItem(SCROLL_KEY); } catch (e) { /* ignore */ }
        });
    }

    // Any normal form submit (Edit/Delete buttons) fires the native 'submit'
    // event, so a single capture-phase listener covers them.
    document.addEventListener('submit', saveScrollPosition, true);

    // Status-change <select onchange="..."> dropdowns call form.submit()
    // directly, which does NOT fire the 'submit' event — expose a helper for
    // those to call instead so their reload also preserves scroll.
    window.ccAdminSubmit = function (form) {
        saveScrollPosition();
        form.submit();
    };

    restoreScrollPosition();

    document.querySelectorAll('[data-bulk-table]').forEach(function (table) {
        var bar = document.getElementById(table.getAttribute('data-bulk-table'));
        if (!bar) return;

        var selectAll = table.querySelector('[data-select-all]');
        var countEl = bar.querySelector('[data-selected-count]');
        var deleteBtn = bar.querySelector('[data-bulk-delete-btn]');
        var deleteUrl = bar.getAttribute('data-delete-url');
        var itemLabel = bar.getAttribute('data-item-label') || 'item';

        function rowChecks() {
            return table.querySelectorAll('.row-check');
        }

        function updateBar() {
            var all = rowChecks();
            var checked = Array.prototype.filter.call(all, function (cb) { return cb.checked; });

            bar.hidden = checked.length === 0;
            if (countEl) {
                countEl.textContent = checked.length + ' ' + itemLabel + (checked.length === 1 ? '' : 's') + ' selected';
            }
            if (selectAll) {
                selectAll.checked = all.length > 0 && checked.length === all.length;
                selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
            }
        }

        table.addEventListener('change', function (e) {
            if (e.target.classList.contains('row-check')) {
                updateBar();
            }
        });

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                rowChecks().forEach(function (cb) { cb.checked = selectAll.checked; });
                updateBar();
            });
        }

        if (deleteBtn && deleteUrl) {
            deleteBtn.addEventListener('click', function () {
                var checked = Array.prototype.filter.call(rowChecks(), function (cb) { return cb.checked; });
                if (!checked.length) return;

                var msg = 'Delete ' + checked.length + ' selected ' + itemLabel +
                    (checked.length === 1 ? '' : 's') + '? This cannot be undone.';
                if (!window.confirm(msg)) return;

                var form = document.createElement('form');
                form.method = 'post';
                form.action = deleteUrl;
                form.style.display = 'none';

                checked.forEach(function (cb) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = cb.value;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                saveScrollPosition();
                form.submit();
            });
        }

        updateBar();
    });
})();
