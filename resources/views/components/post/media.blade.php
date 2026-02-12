@props(['post'])

<section class="post-media-container">
    @if ($post->isImage())
        <img
            src="{{ asset('uploads/' . $post->file_path) }}"
            alt="Post #{{ $post->id }}"
            @if ($post->width) width="{{ $post->width }}" @endif
            @if ($post->height) height="{{ $post->height }}" @endif
            class="post-media"
        \>
    @elseif ($post->isVideo())
        <video
            controls
            @if ($post->width) width="{{ $post->width }}" @endif
            @if ($post->height) height="{{ $post->height }}" @endif
            class="post-media"
        >
            <source src="{{ asset('uploads/' . $post->file_path) }}" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    @endif
</section>
