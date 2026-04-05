@use(App\TagCategory)
<x-layout>
    <x-slot:title>Post #{{ $post->id }} uploaded by {{ $post->author->username }}</x-slot:title>

    <x-slot:sidebar>
        <x-post.actions :post="$post" />
        <x-post.tags :tags="$post->tags" :post="$post" />
        <x-post.details :post="$post" :user-vote="$userVote" :user-favorite="$userFavorite" />
    </x-slot:sidebar>

    <x-slot:description>
        {{ Str::limit($post->description, 150) }}
    </x-slot:description>

    <x-slot:jsonLd>{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES) !!}</x-slot:jsonLd>

    @if ($post->thumb_path)
        <x-slot:ogImage>{{ $post->thumb_path }}</x-slot:ogImage>
    @endif

    @if ($post->isVideo())
        <x-slot:ogType>video.other</x-slot:ogType>
        <x-slot:ogVideo>{{ $post->file_path }}</x-slot:ogVideo>
        <x-slot:ogVideoType>{{ $post->mime_type }}</x-slot:ogVideoType>
    @endif

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

                @auth
                    <form action="{{ route('posts.comments.store', $post) }}" method="POST" class="comment-form">
                        @csrf
                        @error('content')
                            <p class="alert alert-danger">{{ $message }}</p>
                        @enderror
                        <textarea name="content" placeholder="Write a comment..." required maxlength="2000" class="form-input">{{ old('content') }}</textarea>
                        <button type="submit" class="btn btn-primary">Post Comment</button>
                    </form>
                @else
                    <p><a href="{{ route('login') }}">Log in</a> to post a comment.</p>
                @endauth

                @forelse($post->comments as $comment)
                    <div class="comment">
                        <div class="comment-body">
                            <div class="comment-meta">
                                <a href="{{ route('users.show', $comment->user) }}" class="comment-author">
                                    @if($comment->user->avatar_path)
                                        <img src="{{ asset('uploads/' . $comment->user->avatar_path) }}"
                                             alt="{{ $comment->user->username }}"
                                             width="24" height="24" class="avatar avatar-inline">
                                    @else
                                        <div class="avatar avatar-placeholder avatar-inline" style="width:24px;height:24px;font-size:0.75rem;">
                                            {{ mb_substr($comment->user->username, 0, 1) }}
                                        </div>
                                    @endif
                                    {{ $comment->user->username }}
                                </a>
                                <time class="comment-time"
                                      datetime="{{ $comment->created_at->toIso8601String() }}"
                                      title="{{ $comment->created_at->format('Y-m-d H:i') }}">
                                    {{ $comment->created_at->diffForHumans() }}
                                </time>
                                @auth
                                    @if(auth()->id() === $comment->user_id)
                                        <div class="comment-actions">
                                            <form action="{{ route('comments.destroy', $comment) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        </div>
                                    @endif
                                @endauth
                            </div>
                            <p class="comment-content">{{ $comment->content }}</p>
                        </div>
                    </div>
                @empty
                    <p>No comments yet.</p>
                @endforelse
            </section>
        </div>

        <nav class="post-pagination">
            @if($nextPost)
                <a href="{{ route('posts.show', $nextPost) }}" class="btn btn-secondary has-tooltip">
                    &laquo; Next
                    <span class="tooltip"><kbd>→</kbd>, <kbd>D</kbd>, <kbd>L</kbd></span>
                </a>
            @else
                <span class="btn btn-secondary disabled">&laquo; Next</span>
            @endif

            @if($previousPost)
                <a href="{{ route('posts.show', $previousPost) }}" class="btn btn-secondary has-tooltip">
                    Previous &raquo;
                    <span class="tooltip"><kbd>←</kbd>, <kbd>A</kbd>, <kbd>H</kbd></span>
                </a>
            @else
                <span class="btn btn-secondary disabled">Previous &raquo;</span>
            @endif
        </nav>
        <script>
            document.addEventListener('keydown', function(event) {
                if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA' || event.target.isContentEditable) {
                    return;
                }
                const leftKeys = ['ArrowLeft', 'a', 'A', 'h', 'H'];
                const rightKeys = ['ArrowRight', 'd', 'D', 'l', 'L'];
                @if($nextPost)
                    if (leftKeys.includes(event.key)) {
                        window.location.href = "{{ route('posts.show', $nextPost) }}";
                    }
                @endif
                @if($previousPost)
                    if (rightKeys.includes(event.key)) {
                        window.location.href = "{{ route('posts.show', $previousPost) }}";
                    }
                @endif
            });
        </script>
    </article>
</x-layout>
