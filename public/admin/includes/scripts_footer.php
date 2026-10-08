<script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>
<script>if (window.lucide) { lucide.createIcons(); }</script>
<script>
(function () {
    'use strict';
    var root = document.documentElement;
    var toggleBtn = document.getElementById('adminThemeToggle');
    var label = document.getElementById('adminThemeToggleLabel');

    function updateLabel() {
        if (label) {
            label.textContent = root.getAttribute('data-theme') === 'light' ? 'Dark mode' : 'Light mode';
        }
    }

    updateLabel();

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            var next = root.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
            root.setAttribute('data-theme', next);
            try { localStorage.setItem('cc_admin_theme', next); } catch (e) { /* ignore */ }
            updateLabel();
        });
    }
})();
</script>
