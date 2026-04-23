<x-layout>
    <x-slot:title>{{ $tag->name }}</x-slot:title>

    <article class="tag-profile">
        <div class="tag-card">
            <header class="tag-show-header">
                <h2 class="tag tag-{{ $tag->category->value }} tag-title">
                    {{ $tag->name }}
                    <span class="tag-category-label">({{ $tag->category->label() }})</span>
                </h2>
            </header>

            @if($tag->description)
                <section class="tag-show-description">
                    <p>{{ $tag->description }}</p>
                </section>
            @endif

            <div class="tag-stats">
                <div class="tag-stat">
                    <span class="tag-stat-value">{{ $tag->post_count }}</span>
                    <span class="tag-stat-label">Posts</span>
                </div>
            </div>
        </div>

        <section class="tag-show-posts">
            <h3>Latest Posts tagged with "{{ $tag->name }}"</h3>

            @if($posts->isNotEmpty())
                <div class="post-grid">
                    @foreach($posts as $post)
                        <x-post-card :post="$post" />
                    @endforeach
                </div>
            @else
                <p>No posts tagged with "{{ $tag->name }}" yet.</p>
            @endif
        </section>
    </article>
</x-layout>
