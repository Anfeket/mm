<x-layout>
    <x-slot:title>Upload</x-slot:title>

    @pushOnce('scripts')
        @vite('resources/js/upload.js')
    @endPushOnce

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

            <div id="hash-warning" class="alert alert-error hidden" style="margin-bottom: 1rem;">
                <span id="hash-warning-text">This exact file has already been uploaded! </span><a href="#" id="hash-warning-link" target="_blank">View existing post</a>
            </div>

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
                <input type="text" name="tags" class="tag form-input" value="{{ old('tags') }}" placeholder="tag1 tag2 tag3" data-autocomplete>
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
</x-layout>
