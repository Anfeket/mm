@use(App\TagCategory)
<x-layout>
    <x-slot:title>Post #{{ $post->id }}</x-slot:title>

    <x-slot:sidebar>
        <x-post.tags :tags="$post->tags" />
        <x-post.details :post="$post" />
    </x-slot:sidebar>

    <article>
        <div id="post-actions">
        </div>
        <x-post.media :post="$post" />
        <div id="post-content">
            <section class="post-description">
                <h3>Description</h3>
                <p>{{ $post->description }}</p>
            </section>
            <section class="post-comments">
                <h3>Comments</h3>
                @forelse($post->comments as $comment)
                    <div class="comment">
                        <p><strong>{{ $comment->author->username }}:</strong> {{ $comment->content }}</p>
                    </div>
                @empty
                    <p>No comments yet.</p>
                @endforelse
            </section>
        </div>

        <nav class="post-pagination">
            @if($nextPost)
                <a href="{{ route('posts.show', $nextPost) }}" class="btn btn-secondary">&laquo; Next</a>
            @else
                <span class="btn btn-secondary disabled">&laquo; Next</span>
            @endif

            @if($previousPost)
                <a href="{{ route('posts.show', $previousPost) }}" class="btn btn-secondary">Previous &raquo;</a>
            @else
                <span class="btn btn-secondary disabled">Previous &raquo;</span>
            @endif
        </nav>

    </article>
</x-layout>
