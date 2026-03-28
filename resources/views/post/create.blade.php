<x-layout>
    <x-slot:title>Upload</x-slot:title>

    <div class="upload-container">

        <h2>Upload a post</h2>

        @if ($errors->any())
            <div class="alert alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('posts.store') }}" method="POST" enctype="multipart/form-data" autocomplete="off" class="upload-form">
            @csrf

            <div class="upload-source">
                <div id="dropzone" class="dropzone">
                    <input type="file" name="file" id="file-input" accept="image/*,video/*" required>
                    <div id="dropzone-prompt" class="dropzone-prompt">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"></path>
                        </svg>
                        <p>Drop file here or <span class="browse-link">browse</span></p>
                        <small>JPG, PNG, GIF, WebP, MP4, WebM &middot; max 100 MB</small>
                    </div>
                    <div id="preview" class="dropzone-preview hidden"></div>
                </div>

                <div class="upload-source-divider" id="upload-source-divider">
                    <span>or enter a URL</span>
                </div>

                <div class="upload-source-url" id="url-source">
                    <input type="text" name="url" class="url-input" id="url-source-input" value="{{ old('url') }}" placeholder="https://example.com/image.jpg">
                </div>
            </div>

            <div class="form-field">
                <label class="form-label" for="artist">Artist:</label>
                <input type="text" name="artist" class="form-input" value="{{ old('artist') }}" placeholder="artist_name" data-autocomplete data-autocomplete-prefix="a">
            </div>

            <div class="form-field">
                <label class="form-label" for="tags">Tags:</label>
                <input type="text" name="tags" class="form-input" value="{{ old('tags') }}" placeholder="tag1 tag2 tag3" data-autocomplete>
            </div>

            <div class="form-field">
                <label class="form-label" for="source_url">Source:</label>
                <input type="text" name="source_url" class="form-input" value="{{ old('source_url') }}" placeholder="https://...">
            </div>

            <div class="form-field">
                <label class="form-label" for="description">Description:</label>
                <textarea name="description" class="form-input" rows="3">{{ old('description') }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary">Upload</button>
            <script>
                document.querySelector('.upload-form').addEventListener('submit', function(e) {
                    const fileInput = document.getElementById('file-input');
                    const urlInput = document.getElementById('url-source-input');
                    if (!fileInput.files.length && !urlInput.value.trim()) {
                        e.preventDefault();
                        alert('Please provide either a file or a URL.');
                    }
                });
            </script>
        </form>
    </div>

    <script>
        const dropzone = document.getElementById('dropzone');
        const input = document.getElementById('file-input');
        const prompt = document.getElementById('dropzone-prompt');
        const preview = document.getElementById('preview');
        const urlSource = document.getElementById('url-source');
        const urlInput = document.getElementById('url-source-input');
        const divider = document.getElementById('upload-source-divider');

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
                showPreview(input.files[0]);
            }
        });

        input.addEventListener('change', () => {
            if (input.files[0]) {
                showPreview(input.files[0]);
                urlSource.classList.add('hidden');
                divider.classList.add('hidden');
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
                input.required = false;
                urlInput.required = true;
            } else {
                dropzone.classList.remove('hidden');
                input.required = true;
                urlInput.required = false;
            }
        });

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
            info.textContent = `${file.name} · ${mb >= 1 ? mb.toFixed(2) + ' MB' : (file.size / 1024).toFixed(2) + ' KB'}`;
            preview.appendChild(info);
        }
    </script>

</x-layout>
