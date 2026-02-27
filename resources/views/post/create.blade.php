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

            <div id="dropzone" class="dropzone">
                <input type="file" name="file" id="file-input" accept="image/*,video/*" required>
                <div id="dropzone-prompt" class="dropzone-prompt">
                    <p>Drop file here or <span class="browse-link">browse</span></p>
                </div>
                <div id="preview" class="dropzone-preview hidden"></div>
            </div>

            <div class="form-field">
                <label class="form-label" for="artist">Artist:</label>
                <input type="text" name="artist" class="form-input" value="{{ old('artist') }}" placeholder="artist_name">
            </div>

            <div class="form-field">
                <label class="form-label" for="copyright">Copyrights:</label>
                <input type="text" name="copyright" class="form-input" value="{{ old('copyright') }}" placeholder="series_name">
            </div>

            <div class="form-field">
                <label class="form-label" for="tags">Tags:</label>
                <input type="text" name="tags" class="form-input" value="{{ old('tags') }}" placeholder="tag1 tag2 tag3">
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
        </form>
    </div>

    <script>
        const dropzone = document.getElementById('dropzone');
        const input = document.getElementById('file-input');
        const prompt = document.getElementById('dropzone-prompt');
        const preview = document.getElementById('preview');

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
            if (input.files[0]) showPreview(input.files[0]);
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
