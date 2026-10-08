(function () {
    'use strict';

    var modal = document.getElementById('imageEditorModal');
    if (!modal) return;

    var imgEl = document.getElementById('imageEditorImg');
    var rotateLeftBtn = document.getElementById('ieRotateLeft');
    var rotateRightBtn = document.getElementById('ieRotateRight');
    var resetBtn = document.getElementById('ieReset');
    var skipBtn = document.getElementById('ieSkip');
    var applyBtn = document.getElementById('ieApply');

    var cropper = null;
    var currentInput = null;

    function openEditor(file, input) {
        currentInput = input;
        var reader = new FileReader();
        reader.onload = function (e) {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            imgEl.onload = function () {
                cropper = new Cropper(imgEl, {
                    viewMode: 1,
                    autoCropArea: 1,
                    background: false,
                    responsive: true,
                });
            };
            imgEl.src = e.target.result;
            modal.classList.add('open');
        };
        reader.readAsDataURL(file);
    }

    function closeEditor() {
        modal.classList.remove('open');
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        currentInput = null;
    }

    function updatePreview(input, dataUrl) {
        var previewId = input.getAttribute('data-preview-target');
        if (!previewId) return;
        var wrap = document.getElementById(previewId);
        if (!wrap) return;

        wrap.innerHTML = '';

        var img = document.createElement('img');
        img.src = dataUrl;
        img.alt = 'Edited preview';

        var editBtn = document.createElement('button');
        editBtn.type = 'button';
        editBtn.className = 'btn btn-sm';
        editBtn.textContent = 'Edit again';
        editBtn.addEventListener('click', function () {
            var file = input.files && input.files[0];
            if (file) openEditor(file, input);
        });

        wrap.appendChild(img);
        wrap.appendChild(editBtn);
        wrap.style.display = 'flex';
    }

    function handleFileChange(input) {
        var file = input.files && input.files[0];
        if (!file || file.type.indexOf('image/') !== 0) return;
        openEditor(file, input);
    }

    document.querySelectorAll('input[type="file"][data-image-editor]').forEach(function (input) {
        input.addEventListener('change', function () {
            handleFileChange(input);
        });
    });

    if (rotateLeftBtn) rotateLeftBtn.addEventListener('click', function () {
        if (cropper) cropper.rotate(-90);
    });

    if (rotateRightBtn) rotateRightBtn.addEventListener('click', function () {
        if (cropper) cropper.rotate(90);
    });

    if (resetBtn) resetBtn.addEventListener('click', function () {
        if (cropper) cropper.reset();
    });

    if (skipBtn) skipBtn.addEventListener('click', closeEditor);

    if (applyBtn) applyBtn.addEventListener('click', function () {
        if (!cropper || !currentInput || !currentInput.files[0]) {
            closeEditor();
            return;
        }

        var canvas = cropper.getCroppedCanvas({ imageSmoothingQuality: 'high' });
        if (!canvas) {
            closeEditor();
            return;
        }

        var mime = currentInput.files[0].type;
        if (mime !== 'image/jpeg' && mime !== 'image/png' && mime !== 'image/webp') {
            mime = 'image/png';
        }

        var inputRef = currentInput;
        canvas.toBlob(function (blob) {
            if (blob) {
                var ext = mime.split('/')[1];
                var newFile = new File([blob], 'edited-image.' + ext, { type: mime });
                var dt = new DataTransfer();
                dt.items.add(newFile);
                inputRef.files = dt.files;
                updatePreview(inputRef, canvas.toDataURL(mime));
            }
            closeEditor();
        }, mime, 0.92);
    });

    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeEditor();
    });
})();
