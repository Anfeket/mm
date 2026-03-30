(function () {
    'use strict';

    // -------------------------------------------------------------------------
    // Elements
    // -------------------------------------------------------------------------

    const dropzone = document.getElementById('dropzone');
    const avatarInput = document.getElementById('avatar-input');

    const cropContainer = document.getElementById('crop-container');
    const cropImageContainer = document.getElementById('crop-image-container');
    const cropImage = document.getElementById('crop-img');
    const cropSelection = document.getElementById('crop-selection');

    const cropPreview = document.getElementById('crop-preview');
    const cropPreviewImg = document.getElementById('crop-preview-img');

    const cropConfirm = document.getElementById('crop-confirm');
    const cropCancel = document.getElementById('crop-cancel');
    const cropReset = document.getElementById('crop-reset');

    const inputX = document.getElementById('crop_x');
    const inputY = document.getElementById('crop_y');
    const inputSize = document.getElementById('crop_size');

    if (!dropzone || !cropImage) {
        console.warn('avatar-crop: required elements not found');
        return;
    }

    // -------------------------------------------------------------------------
    // Crop state
    // -------------------------------------------------------------------------

    let crop = { x: 0, y: 0, size: 100 };

    // Drag state
    let mode = null; // 'move' | 'resize' | null
    let activeHandle = null; // 'nw' | 'ne' | 'sw' | 'se' | null
    let startPtr = { x: 0, y: 0 };
    let startCrop = { x: 0, y: 0, size: 100 };

    let preview = null;

    // -------------------------------------------------------------------------
    // Crop helpers
    // -------------------------------------------------------------------------

    function clamp() {
        const minSize = 20;
        const maxW = cropImage.offsetWidth;
        const maxH = cropImage.offsetHeight;

        crop.size = Math.max(minSize, Math.min(crop.size, maxW, maxH));
        crop.x = Math.max(0, Math.min(crop.x, maxW - crop.size));
        crop.y = Math.max(0, Math.min(crop.y, maxH - crop.size));
    }

    function render() {
        cropSelection.style.left = crop.x + 'px';
        cropSelection.style.top = crop.y + 'px';
        cropSelection.style.width = crop.size + 'px';
        cropSelection.style.height = crop.size + 'px';
    }

    function initCrop() {
        const size = Math.floor(Math.min(cropImage.offsetWidth, cropImage.offsetHeight) * 0.8);

        crop = {
            x: Math.floor((cropImage.offsetWidth - size) / 2),
            y: Math.floor((cropImage.offsetHeight - size) / 2),
            size: size,
        };

        render();
    }

    // -------------------------------------------------------------------------
    // File loading
    // -------------------------------------------------------------------------

    function loadFile(file) {
        if (!file.type.startsWith('image/')) return;

        if (cropImage.src.startsWith('blob:')) {
            URL.revokeObjectURL(cropImage.src);
        }

        cropImage.src = URL.createObjectURL(file);

        cropImage.onload = () => {
            cropContainer.classList.remove('hidden');
            cropImageContainer.classList.remove('hidden');
            cropPreview.classList.add('hidden');
            cropConfirm.classList.remove('hidden');
            cropCancel.classList.remove('hidden');

            requestAnimationFrame(initCrop);
        };

        if (cropImage.complete && cropImage.naturalWidth) cropImage.onload();
    }

    // -------------------------------------------------------------------------
    // Dropzone events
    // -------------------------------------------------------------------------

    dropzone.addEventListener('click', (e) => {
        if (e.target === dropzone) avatarInput.click();
    });

    avatarInput.addEventListener('change', () => {
        if (avatarInput.files?.[0]) loadFile(avatarInput.files[0]);
    });

    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('dragover');
    });

    dropzone.addEventListener('dragleave', (e) => {
        if (!dropzone.contains(e.relatedTarget)) dropzone.classList.remove('dragover');
    });

    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('dragover');

        const file = e.dataTransfer.files[0];
        if (!file) return;

        try {
            const dt = new DataTransfer();
            dt.items.add(file);
            avatarInput.files = dt.files;
        } catch (_) { }

        loadFile(file);
    });

    // -------------------------------------------------------------------------
    // Crop selection drag
    // -------------------------------------------------------------------------

    cropSelection.addEventListener('pointerdown', (e) => {
        e.preventDefault();
        e.stopPropagation();

        const handle = e.target.dataset.handle;
        mode = handle ? 'resize' : 'move';
        activeHandle = handle ?? null;
        startPtr = { x: e.clientX, y: e.clientY };
        startCrop = { ...crop };

        cropSelection.setPointerCapture(e.pointerId);
    });

    cropSelection.addEventListener('pointermove', (e) => {
        if (!mode) return;
        e.preventDefault();

        const dx = e.clientX - startPtr.x;
        const dy = e.clientY - startPtr.y;

        if (mode === 'move') {
            crop.x = startCrop.x + dx;
            crop.y = startCrop.y + dy;
        } else {
            resizeFromHandle(dx, dy);
        }

        clamp();
        render();
    });

    cropSelection.addEventListener('pointerup', () => { mode = null; activeHandle = null; });
    cropSelection.addEventListener('pointercancel', () => { mode = null; activeHandle = null; });

    function resizeFromHandle(dx, dy) {
        // For a 1:1 crop box, derive a single delta from whichever axis moved more.
        // Each handle anchors the opposite corner, so position adjusts accordingly.
        let delta;

        switch (activeHandle) {
            case 'nw':
                delta = Math.abs(dx) >= Math.abs(dy) ? -dx : -dy;
                crop.size = startCrop.size + delta;
                crop.x = startCrop.x - delta;
                crop.y = startCrop.y - delta;
                break;
            case 'ne':
                delta = Math.abs(dx) >= Math.abs(dy) ? dx : -dy;
                crop.size = startCrop.size + delta;
                crop.y = startCrop.y - delta;
                break;
            case 'sw':
                delta = Math.abs(dx) >= Math.abs(dy) ? -dx : dy;
                crop.size = startCrop.size + delta;
                crop.x = startCrop.x - delta;
                break;
            case 'se':
                delta = Math.abs(dx) >= Math.abs(dy) ? dx : dy;
                crop.size = startCrop.size + delta;
                break;
        }
    }

    // -------------------------------------------------------------------------
    // Confirm / Cancel / Reset
    // -------------------------------------------------------------------------

    cropConfirm.addEventListener('click', confirm);
    cropReset.addEventListener('click', reset);
    cropCancel.addEventListener('click', cancel);

    function confirm() {
        const scaleX = cropImage.naturalWidth / cropImage.offsetWidth;
        const scaleY = cropImage.naturalHeight / cropImage.offsetHeight;

        inputX.value = Math.round(crop.x * scaleX);
        inputY.value = Math.round(crop.y * scaleY);
        inputSize.value = Math.round(crop.size * ((scaleX + scaleY) / 2));

        preview = true;
        showPreview();

        cropImageContainer.classList.add('hidden');
        cropConfirm.classList.add('hidden');
        cropCancel.classList.add('hidden');
        cropReset.classList.remove('hidden');
    }

    function reset() {
        avatarInput.value = '';

        if (cropImage.src.startsWith('blob:')) URL.revokeObjectURL(cropImage.src);
        cropImage.src = '';
        cropImage.style.opacity = '';
        cropPreviewImg.src = '';

        preview = null;

        inputX.value = inputY.value = inputSize.value = '';

        cropImageContainer.classList.remove('hidden');
        cropContainer.classList.add('hidden');
        cropPreview.classList.add('hidden');

        cropConfirm.classList.remove('hidden');
        cropCancel.classList.remove('hidden');
        cropReset.classList.add('hidden');
    }

    function cancel() {
        if (cropImage.src.startsWith('blob:')) URL.revokeObjectURL(cropImage.src);
        cropImage.src = '';
        cropImage.style.opacity = '';

        cropImageContainer.classList.remove('hidden');

        if (preview) {
            cropPreview.classList.remove('hidden');
            cropConfirm.classList.add('hidden');
            cropCancel.classList.add('hidden');
            cropReset.classList.remove('hidden');
        } else {
            cropContainer.classList.add('hidden');
        }

    }

    // -------------------------------------------------------------------------
    // Preview
    // -------------------------------------------------------------------------

    function showPreview() {
        const previewSize = 150;
        const scale = previewSize / crop.size;

        cropPreviewImg.src = cropImage.src;
        cropPreviewImg.style.width = (cropImage.offsetWidth * scale) + 'px';
        cropPreviewImg.style.height = (cropImage.offsetHeight * scale) + 'px';
        cropPreviewImg.style.left = -(crop.x * scale) + 'px';
        cropPreviewImg.style.top = -(crop.y * scale) + 'px';

        cropPreview.classList.remove('hidden');
    }

})();
