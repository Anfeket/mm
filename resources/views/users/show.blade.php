<x-layout>
    <x-slot:title>{{ $user->username }}'s Profile</x-slot:title>

    <article class="user-profile">
        <div class="user-card">
            <header class="user-header">
                @if ($user->avatar_path)
                    <div class="user-avatar">
                        <img src="{{ asset('uploads/' . $user->avatar_path) }}" alt="{{ $user->username }}'s avatar" width="100" height="100" class="avatar">
                    </div>
                @endif
                <div class="user-info">
                    <h2 class="user-username">{{ $user->username }}</h2>
                    <span class="user-joined">Member since {{ $user->created_at->format('F j, Y') }}</span>
                </div>
            </header>
            <section class="user-stats">
                <div class="user-stat">
                    <span class="user-stat-value">{{ $postStats->post_count }}</span>
                    <span class="user-stat-label">Posts</span>
                </div>
                <div class="user-stat">
                    <span class="user-stat-value">{{ $postStats->total_score > 0 ? '+' : '' }}{{ $postStats->total_score }}</span>
                    <span class="user-stat-label">Total Score</span>
                </div>
                <div class="user-stat">
                    <span class="user-stat-value">{{ $postStats->total_views }}</span>
                    <span class="user-stat-label">Total Views</span>
                </div>
                <div class="user-stat">
                    <span class="user-stat-value">{{ $postStats->total_favorites_received }}</span>
                    <span class="user-stat-label">Total Favorites</span>
                </div>
                <div class="user-stat">
                    <span class="user-stat-value">{{ $favoritesGivenCount }}</span>
                    <span class="user-stat-label">Favorited posts</span>
                </div>
                <div class="user-stat">
                    <span class="user-stat-value">{{ $commentCount }}</span>
                    <span class="user-stat-label">Comments</span>
                </div>
            </section>
        </div>
        <section class="user-posts">
            <h3>Recent Posts</h3>
            <div class="post-grid">
                @forelse ($posts as $post)
                    <x-post-card :post="$post" />
                @empty
                    <p>No posts yet.</p>
                @endforelse
            </div>
            @if ($posts->isNotEmpty())
                <a href="#" class="btn">View All Posts</a>
            @endif
        </section>
    </article>
</x-layout>
