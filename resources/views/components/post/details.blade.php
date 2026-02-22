@props(['post', 'userVote'])
@use(Illuminate\Support\Uri)
@use(Illuminate\Support\Number)

<section id="post-details" class="post-details-section">
    <h3>Information</h3>
    <dl id="post-details-list">
        <dt>ID:</dt>
        <dd>{{ $post->id }}</dd>

        <dt>Uploader:</dt>
        <dd><a href="{{ route('users.show', $post->author) }}">{{ $post->author->username }}</a></dd>

        <dt>Posted:</dt>
        <dd>{{ $post->created_at->format('Y-m-d H:i:s') }}</dd>

        <dt>Size:</dt>
        <dd>{{ Number::fileSize($post->file_size, 2) }}</dd>

        <dt>Source:</dt>
        @if ($post->source_url)
            <dd><a href="{{ $post->source_url }}" target="_blank">{{ Uri::of($post->source_url)->host() }}</a></dd>
        @else
            <dd>N/A</dd>
        @endif

        <dt>Score:</dt>
        <dd class="post-score">
            <span class="score-value">
                <span class="score-number">{{ $post->like_count }}</span>
                <span class="score-breakdown">{{ $post->upvotes }} ↑ / {{ $post->downvotes }} ↓</span>
            </span>
            @auth
                <form action="{{ route('posts.vote', $post) }}" method="POST" class="vote-form">
                    @csrf
                    <button type="submit" name="value" value="1" @class(['vote-btn', 'vote-up', 'vote-active' => $userVote === 1 ])>▲</button>
                    <button type="submit" name="value" value="-1" @class(['vote-btn', 'vote-down', 'vote-active' => $userVote === -1 ])>▼</button>
                </form>
            @endauth
        </dd>

        <dt>Favorites:</dt>
        <dd>{{ $post->favorites_count }}</dd>

    </dl>

</section>
