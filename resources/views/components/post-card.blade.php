<div class="post-card">
    <a href="{{ route('posts.show', $post->id) }}">
    @if ($post->thumb_path)
        <img src="{{ asset($post->thumb_path) }}" alt="Post #{{ $post->id }}" class="post-thumb">
    @endif
    </a>
</div>
