<x-layout>
    <x-slot:title>Post #{{ $post->id }}</x-slot:title>

    <article>
        <div id="post-actions">
        </div>
        <div id="post-content">
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
            <div class="post-description">
                <h3>Description</h3>
                <p>{{ $post->description }}</p>
            </div>
            <div class="post-comments">
                <h3>Comments</h3>
                @forelse($post->comments as $comment)
                    <div class="comment">
                        <p><strong>{{ $comment->author->username }}:</strong> {{ $comment->content }}</p>
                    </div>
                @empty
                    <p>No comments yet.</p>
                @endforelse
            </div>
        </div>
    </article>
</x-layout>
