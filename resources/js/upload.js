import SparkMD5 from 'spark-md5';

document.addEventListener('DOMContentLoaded', () => {
    const uploadForm = document.querySelector('.upload-form');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function (e) {
            const fileInput = document.getElementById('file-input');
            const urlInput = document.getElementById('url-source-input');
            if (fileInput && urlInput && !fileInput.files.length && !urlInput.value.trim()) {
                e.preventDefault();
                alert('Please provide either a file or a URL.');
            }
        });
    }

    const dropzone = document.getElementById('dropzone');
    const input = document.getElementById('file-input');
    const prompt = document.getElementById('dropzone-prompt');
    const preview = document.getElementById('preview');
    const urlSource = document.getElementById('url-source');
    const urlInput = document.getElementById('url-source-input');
    const divider = document.getElementById('upload-source-divider');

    if (!dropzone) return; // Only run on upload page

    dropzone.addEventListener('click', () => input.click());

    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('dragover');
    });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            input.files = e.dataTransfer.files;
            handleFileSelection(input.files[0]);
        }
    });

    input.addEventListener('change', () => {
        if (input.files[0]) {
            handleFileSelection(input.files[0]);
        } else {
            prompt.classList.remove('hidden');
            preview.classList.add('hidden');
            urlSource.classList.remove('hidden');
            divider.classList.remove('hidden');
        }
    });

    urlInput.addEventListener('input', () => {
        if (urlInput.value.trim()) {
            dropzone.classList.add('hidden');
            if (input) input.required = false;
            urlInput.required = true;
        } else {
            dropzone.classList.remove('hidden');
            if (input) input.required = true;
            urlInput.required = false;
        }
    });

    function handleFileSelection(file) {
        showPreview(file);
        if (urlSource) urlSource.classList.add('hidden');
        if (divider) divider.classList.add('hidden');
        checkFileHash(file);
    }

    function showPreview(file) {
        preview.innerHTML = '';
        prompt.classList.add('hidden');
        preview.classList.remove('hidden');

        const url = URL.createObjectURL(file);

        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = url;
            preview.appendChild(img);
        } else if (file.type.startsWith('video/')) {
            const video = document.createElement('video');
            video.src = url;
            video.controls = true;
            preview.appendChild(video);
        }

        const info = document.createElement('p');
        info.className = 'dropzone-hint';
        const mb = file.size / 1024 / 1024;
        info.textContent = `${file.name} \u00B7 ${mb >= 1 ? mb.toFixed(2) + ' MB' : (file.size / 1024).toFixed(2) + ' KB'}`;
        preview.appendChild(info);

        let warningDiv = document.getElementById('hash-warning');
        if (warningDiv) {
            warningDiv.classList.add('hidden');
        }
    }

    function checkFileHash(file) {
        const blobSlice = File.prototype.slice || File.prototype.mozSlice || File.prototype.webkitSlice;
        const chunkSize = 2097152; // 2MB
        const chunks = Math.ceil(file.size / chunkSize);
        const spark = new SparkMD5.ArrayBuffer();
        const fileReader = new FileReader();
        let currentChunk = 0;

        fileReader.onload = (e) => {
            spark.append(e.target.result);
            currentChunk++;

            if (currentChunk < chunks) {
                loadNext();
            } else {
                const hash = spark.end();
                fetch(`/upload/check-hash?hash=${hash}`)
                    .then(r => r.json())
                    .then(data => {
                        const warningDiv = document.getElementById('hash-warning');
                        const link = document.getElementById('hash-warning-link');
                        if (!warningDiv) return;

                        if (data.exists) {
                            if (link) {
                                link.href = data.url;
                                link.classList.remove('hidden');
                            }
                            const textSpan = document.getElementById('hash-warning-text');
                            if (textSpan) textSpan.textContent = 'This exact file has already been uploaded! ';
                            warningDiv.classList.remove('hidden');
                        } else {
                            warningDiv.classList.add('hidden');
                        }
                    })
                    .catch(err => {
                        console.error('Hash check failed:', err);
                        const warningDiv = document.getElementById('hash-warning');
                        if (warningDiv) {
                            const textSpan = document.getElementById('hash-warning-text');
                            if (textSpan) textSpan.textContent = 'Failed to check file hash.';
                            const link = document.getElementById('hash-warning-link');
                            if (link) link.classList.add('hidden');
                            warningDiv.classList.remove('hidden');
                        }
                    });
            }
        };

        fileReader.onerror = () => {
            console.warn('Failed to read file for hash check.');
            const warningDiv = document.getElementById('hash-warning');
            if (warningDiv) {
                const textSpan = document.getElementById('hash-warning-text');
                if (textSpan) textSpan.textContent = 'Failed to read file for hash check.';
                const link = document.getElementById('hash-warning-link');
                if (link) link.classList.add('hidden');
                warningDiv.classList.remove('hidden');
            }
        };

        function loadNext() {
            const start = currentChunk * chunkSize;
            const end = Math.min(start + chunkSize, file.size);
            fileReader.readAsArrayBuffer(blobSlice.call(file, start, end));
        }

        loadNext();
    }
});
