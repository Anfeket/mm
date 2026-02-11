@props(['post'])

<div class="post-media">
    @if ($post->isImage())
        <img
            src="{{ asset('uploads/' . $post->file_path) }}"
            alt="Post #{{ $post->id }}"
            @if ($post->width) width="{{ $post->width }}" @endif
            @if ($post->height) height="{{ $post->height }}" @endif
        \>
    @elseif ($post->video)
        <video
            controls
            @if ($post->width) width="{{ $post->width }}" @endif
            @if ($post->height) height="{{ $post->height }}" @endif
        >
            <source src="{{ asset('uploads/' . $post->file_path) }}" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    @endif
</div>
