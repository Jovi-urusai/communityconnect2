(function () {
    'use strict';

    /* ---------- Generic tab switcher ----------
       <div data-tabs>
           <button data-tab-btn="key">Label</button> ...
       </div>
       <div data-tab-panel="key"> ... </div> ... */
    document.querySelectorAll('[data-tabs]').forEach(function (tabGroup) {
        var buttons = tabGroup.querySelectorAll('[data-tab-btn]');

        function activate(key) {
            buttons.forEach(function (btn) {
                btn.classList.toggle('active', btn.getAttribute('data-tab-btn') === key);
            });
            document.querySelectorAll('[data-tab-panel]').forEach(function (panel) {
                panel.hidden = panel.getAttribute('data-tab-panel') !== key;
            });
        }

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                activate(btn.getAttribute('data-tab-btn'));
            });
        });
    });

    /* ---------- Generic dropdown menu ----------
       <div data-dropdown>
           <button data-dropdown-toggle>...</button>
           <div data-dropdown-menu hidden>...</div>
       </div> */
    var openDropdowns = [];

    function closeAllDropdowns() {
        openDropdowns.forEach(function (menu) { menu.hidden = true; });
        openDropdowns = [];
    }

    document.querySelectorAll('[data-dropdown]').forEach(function (wrap) {
        var toggle = wrap.querySelector('[data-dropdown-toggle]');
        var menu = wrap.querySelector('[data-dropdown-menu]');
        if (!toggle || !menu) return;

        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            var wasHidden = menu.hidden;
            closeAllDropdowns();
            if (wasHidden) {
                menu.hidden = false;
                openDropdowns.push(menu);
            }
        });
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('[data-dropdown]')) {
            closeAllDropdowns();
        }
    });

    /* ---------- Toggle a post's comment section open/closed ---------- */
    document.querySelectorAll('[data-toggle-comments]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var panel = document.getElementById(btn.getAttribute('data-toggle-comments'));
            if (!panel) return;
            var wasHidden = panel.hidden;
            panel.hidden = !wasHidden;
            if (wasHidden) {
                var input = panel.querySelector('input[type="text"]');
                panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                if (input) input.focus();
            }
        });
    });

    // Auto-open the comment panel when arriving via a #post-N share link.
    if (location.hash.indexOf('#post-') === 0) {
        var jumpPanel = document.getElementById('post-comments-' + location.hash.slice('#post-'.length));
        if (jumpPanel) jumpPanel.hidden = false;
    }

    /* ---------- Reaction button: tap = default Like, long-press = picker ----------
       <div class="like-btn-wrap">
           <form>...<button class="interaction-btn">...</button></form>
           <div class="reaction-popup">...</div>
       </div> */
    var LONG_PRESS_MS = 450;

    document.querySelectorAll('.like-btn-wrap').forEach(function (wrap) {
        var btn = wrap.querySelector('.interaction-btn');
        var popup = wrap.querySelector('.reaction-popup');
        if (!btn || !popup) return;

        var pressTimer = null;
        var longPressFired = false;

        function openPopup() {
            longPressFired = true;
            popup.classList.add('force-open');
        }

        function closePopup() {
            popup.classList.remove('force-open');
        }

        function startPress() {
            longPressFired = false;
            clearTimeout(pressTimer);
            pressTimer = setTimeout(openPopup, LONG_PRESS_MS);
        }

        function cancelPress() {
            clearTimeout(pressTimer);
        }

        btn.addEventListener('mousedown', startPress);
        btn.addEventListener('mouseup', cancelPress);
        btn.addEventListener('mouseleave', cancelPress);
        btn.addEventListener('touchstart', startPress, { passive: true });
        btn.addEventListener('touchend', cancelPress);
        btn.addEventListener('touchmove', cancelPress);

        btn.addEventListener('click', function (e) {
            if (longPressFired) {
                e.preventDefault();
                longPressFired = false;
            }
        });

        popup.querySelectorAll('button').forEach(function (reactionBtn) {
            reactionBtn.addEventListener('click', closePopup);
        });

        document.addEventListener('click', function (e) {
            if (!wrap.contains(e.target)) closePopup();
        });
    });

    /* ---------- Multi-photo upload with thumbnail previews ---------- */
    document.querySelectorAll('input[type="file"][data-multi-photo]').forEach(function (input) {
        var previewId = input.getAttribute('data-preview-target');
        var preview = previewId ? document.getElementById(previewId) : null;
        var maxFiles = parseInt(input.getAttribute('data-max-files'), 10) || 6;
        var files = [];

        function syncInputFiles() {
            var dt = new DataTransfer();
            files.forEach(function (f) { dt.items.add(f); });
            input.files = dt.files;
        }

        function renderPreview() {
            if (!preview) return;
            preview.innerHTML = '';

            files.forEach(function (file, index) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    var item = document.createElement('div');
                    item.className = 'photo-thumb';

                    var img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = 'Selected photo ' + (index + 1);

                    var removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'photo-thumb-remove';
                    removeBtn.setAttribute('aria-label', 'Remove this photo');
                    removeBtn.innerHTML = '&times;';
                    removeBtn.addEventListener('click', function () {
                        files.splice(index, 1);
                        syncInputFiles();
                        renderPreview();
                    });

                    item.appendChild(img);
                    item.appendChild(removeBtn);
                    preview.appendChild(item);
                };
                reader.readAsDataURL(file);
            });
        }

        input.addEventListener('change', function () {
            Array.prototype.forEach.call(input.files || [], function (file) {
                if (file.type.indexOf('image/') !== 0) return;
                if (files.length >= maxFiles) return;
                files.push(file);
            });
            syncInputFiles();
            renderPreview();
        });
    });

    /* ---------- Photo lightbox (used by any .gallery-photo-trigger) ---------- */
    var lightboxOverlay = document.getElementById('lightboxOverlay');
    var lightboxImage = document.getElementById('lightboxImage');
    var lightboxClose = document.getElementById('lightboxClose');
    var lightboxTriggers = document.querySelectorAll('.gallery-photo-trigger');

    function openLightbox(src, alt) {
        if (!lightboxOverlay || !lightboxImage) return;
        lightboxImage.src = src;
        lightboxImage.alt = alt || '';
        lightboxOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        if (!lightboxOverlay || !lightboxImage) return;
        lightboxOverlay.classList.remove('active');
        lightboxImage.src = '';
        document.body.style.overflow = '';
    }

    lightboxTriggers.forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            openLightbox(trigger.getAttribute('data-lightbox-src'), trigger.getAttribute('data-lightbox-alt'));
        });
    });

    if (lightboxClose) {
        lightboxClose.addEventListener('click', closeLightbox);
    }

    if (lightboxOverlay) {
        lightboxOverlay.addEventListener('click', function (e) {
            if (e.target === lightboxOverlay) {
                closeLightbox();
            }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && lightboxOverlay && lightboxOverlay.classList.contains('active')) {
            closeLightbox();
        }
    });
})();
