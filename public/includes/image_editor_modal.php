<div class="modal-overlay" id="imageEditorModal">
    <div class="modal-box" style="max-width: 540px;">
        <h2>Edit Photo</h2>
        <p class="sub">Crop and rotate your image, then apply.</p>

        <div class="image-editor-canvas-wrap">
            <img id="imageEditorImg" src="" alt="Editing preview">
        </div>

        <div class="image-editor-toolbar">
            <button type="button" class="btn btn-sm" id="ieRotateLeft">&#8634; Rotate Left</button>
            <button type="button" class="btn btn-sm" id="ieRotateRight">&#8635; Rotate Right</button>
            <button type="button" class="btn btn-sm" id="ieReset">Reset</button>
        </div>

        <div class="modal-actions">
            <button type="button" class="btn" id="ieSkip">Use Original</button>
            <button type="button" class="btn btn-primary" id="ieApply">Apply</button>
        </div>
    </div>
</div>
