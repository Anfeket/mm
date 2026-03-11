@props(['post'])

<figure class="post-media-container">
    @if ($post->isImage())
        <img
            src="{{ asset('uploads/' . $post->file_path) }}"
            alt="Post #{{ $post->id }}"
            @if ($post->width) width="{{ $post->width }}" @endif
            @if ($post->height) height="{{ $post->height }}" @endif
            loading="lazy"
            class="post-media"
        >
    @elseif ($post->isVideo())
        <video
            controls
            playsinline
            @if ($post->thumb_path) poster="{{ asset('uploads/' . $post->thumb_path) }}" @endif
            @if ($post->width) width="{{ $post->width }}" @endif
            @if ($post->height) height="{{ $post->height }}" @endif
            class="post-media"
        >
            <source src="{{ asset('uploads/' . $post->file_path) }}" type="{{ $post->mime_type }}">
            Your browser does not support the video tag.
        </video>
    @endif
</figure>
