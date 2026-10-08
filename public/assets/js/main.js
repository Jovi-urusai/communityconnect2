(function () {
    'use strict';

    /* ---------- Copy-to-clipboard buttons ---------- */
    document.querySelectorAll('[data-copy-value]').forEach(function (btn) {
        var originalLabel = btn.textContent;
        var resetTimer = null;

        btn.addEventListener('click', function () {
            var value = btn.getAttribute('data-copy-value') || '';

            function showCopied() {
                clearTimeout(resetTimer);
                btn.textContent = 'Copied!';
                resetTimer = setTimeout(function () {
                    btn.textContent = originalLabel;
                }, 2000);
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(value).then(showCopied, function () {
                    fallbackCopy(value);
                    showCopied();
                });
            } else {
                fallbackCopy(value);
                showCopied();
            }
        });
    });

    function fallbackCopy(value) {
        var temp = document.createElement('textarea');
        temp.value = value;
        temp.style.position = 'fixed';
        temp.style.opacity = '0';
        document.body.appendChild(temp);
        temp.focus();
        temp.select();
        try {
            document.execCommand('copy');
        } catch (e) { /* ignore */ }
        document.body.removeChild(temp);
    }

    /* ---------- Theme toggle ---------- */
    var root = document.documentElement;
    var themeBtn = document.getElementById('themeToggle');
    var themeBtnLabel = document.getElementById('themeToggleLabel');

    function applyTheme(theme) {
        if (theme === 'light') {
            root.setAttribute('data-theme', 'light');
        } else {
            root.setAttribute('data-theme', 'dark');
        }
        if (themeBtnLabel) {
            themeBtnLabel.textContent = theme === 'light' ? 'Dark mode' : 'Light mode';
        }
    }

    var savedTheme = localStorage.getItem('cc_theme') || 'dark';
    applyTheme(savedTheme);

    if (themeBtn) {
        themeBtn.addEventListener('click', function () {
            var current = root.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
            var next = current === 'light' ? 'dark' : 'light';
            localStorage.setItem('cc_theme', next);
            applyTheme(next);
        });
    }

    /* ---------- Urgent banner dismiss (version-aware, with undo) ---------- */
    var banner = document.getElementById('urgentBanner');
    var DISMISS_DELAY_MS = 5000;

    if (banner) {
        var bannerVersion = banner.getAttribute('data-version');
        var dismissedVersion = sessionStorage.getItem('cc_banner_dismissed_version');

        if (dismissedVersion === bannerVersion) {
            banner.style.display = 'none';
        } else {
            var bannerIcon = document.getElementById('urgentBannerIcon');
            var bannerBody = document.getElementById('urgentBannerBody');
            var originalIconHTML = bannerIcon.innerHTML;
            var originalBodyHTML = bannerBody.innerHTML;
            var hideTimer = null;

            function restoreBanner() {
                if (hideTimer) {
                    clearTimeout(hideTimer);
                    hideTimer = null;
                }
                bannerIcon.innerHTML = originalIconHTML;
                bannerBody.innerHTML = originalBodyHTML;
                banner.classList.remove('dismissing');
                var closeBtn = document.getElementById('urgentBannerClose');
                if (closeBtn) closeBtn.style.display = '';
            }

            banner.addEventListener('click', function (e) {
                if (e.target && e.target.id === 'urgentBannerClose') {
                    banner.classList.add('dismissing');
                    bannerIcon.innerHTML = '&#8635;';
                    bannerBody.innerHTML = '<strong>Advisory dismissed</strong><p>This will disappear shortly. <button type="button" id="undoDismiss" class="undo-btn">Undo</button></p>';
                    e.target.style.display = 'none';

                    hideTimer = setTimeout(function () {
                        banner.style.display = 'none';
                        sessionStorage.setItem('cc_banner_dismissed_version', bannerVersion);
                    }, DISMISS_DELAY_MS);
                } else if (e.target && e.target.id === 'undoDismiss') {
                    restoreBanner();
                }
            });
        }
    }

    /* ---------- Login modal ---------- */
    var loginBtn = document.getElementById('openLoginModal');
    var loginModal = document.getElementById('loginModal');
    var loginCancel = document.getElementById('loginCancel');
    var loginForm = document.getElementById('loginForm');

    function resetLoginForm() {
        if (loginForm) {
            loginForm.reset();
            var u = document.getElementById('username');
            var p = document.getElementById('password');
            if (u) u.value = '';
            if (p) p.value = '';
            if (p) p.type = 'password';
            var eyeIcon = document.getElementById('eyeIcon');
            var toggleBtn = document.getElementById('togglePassword');
            if (eyeIcon) eyeIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"></path><circle cx="12" cy="12" r="3"></circle>';
            if (toggleBtn) toggleBtn.setAttribute('aria-label', 'Show password');
        }
    }

    function openModal() {
        if (loginModal) loginModal.classList.add('open');
        resetLoginForm();
    }
    function closeModal() {
        if (loginModal) loginModal.classList.remove('open');
        resetLoginForm();
    }

    if (loginBtn) loginBtn.addEventListener('click', openModal);
    if (loginCancel) loginCancel.addEventListener('click', closeModal);
    if (loginModal) {
        loginModal.addEventListener('click', function (e) {
            if (e.target === loginModal) closeModal();
        });
    }

    resetLoginForm();
    window.addEventListener('pageshow', resetLoginForm);

    /* ---------- Password visibility toggle ---------- */
    var togglePassword = document.getElementById('togglePassword');
    var passwordInput = document.getElementById('password');
    var eyeIcon = document.getElementById('eyeIcon');
    var EYE_OPEN = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"></path><circle cx="12" cy="12" r="3"></circle>';
    var EYE_CLOSED = '<path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a20.7 20.7 0 0 1 5.06-5.94M9.9 4.24A10.4 10.4 0 0 1 12 4c7 0 11 8 11 8a20.7 20.7 0 0 1-3.22 4.36M14.12 14.12a3 3 0 1 1-4.24-4.24"></path><path d="M1 1l22 22"></path>';

    if (togglePassword && passwordInput && eyeIcon) {
        togglePassword.addEventListener('click', function () {
            var isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            eyeIcon.innerHTML = isHidden ? EYE_CLOSED : EYE_OPEN;
            togglePassword.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        });
    }

    /* ---------- Search + category filter (client-side) ---------- */
    var searchInput = document.getElementById('searchInput');
    var pills = document.querySelectorAll('.pill[data-category]');
    var cards = document.querySelectorAll('[data-card]');

    function filterCards() {
        var query = (searchInput && searchInput.value || '').trim().toLowerCase();
        var activePill = document.querySelector('.pill.active');
        var activeCategory = activePill ? activePill.getAttribute('data-category') : 'All';

        var visibleCount = 0;
        cards.forEach(function (card) {
            var title = (card.getAttribute('data-title') || '').toLowerCase();
            var content = (card.getAttribute('data-content') || '').toLowerCase();
            var category = card.getAttribute('data-category');

            var matchesQuery = !query || title.indexOf(query) !== -1 || content.indexOf(query) !== -1;
            var matchesCategory = activeCategory === 'All' || category === activeCategory;

            var visible = matchesQuery && matchesCategory;
            card.style.display = visible ? '' : 'none';
            if (visible) visibleCount++;
        });

        var emptyState = document.getElementById('emptyState');
        if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? '' : 'none';
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterCards);
    }

    pills.forEach(function (pill) {
        pill.addEventListener('click', function () {
            pills.forEach(function (p) { p.classList.remove('active'); });
            pill.classList.add('active');
            filterCards();
        });
    });

    if (cards.length) {
        filterCards();
    }
})();
