@use(App\TagCategory)
<x-layout>
    <x-slot:title>Post #{{ $post->id }}</x-slot:title>

    <x-slot:sidebar>
        <x-post.tags :tags="$post->tags" />
    </x-slot:sidebar>

    <article>
        <div id="post-actions">
        </div>
        <x-post.media :post="$post" />
        <div id="post-content">
            <div class="post-description">
                <h3 class="tag-category">Description</h3>
                <p>{{ $post->description }}</p>
            </div>
            <div class="post-comments">
                <h3 class="tag-category">Comments</h3>
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
