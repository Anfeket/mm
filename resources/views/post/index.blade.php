<x-layout>
    <x-slot:title>Recent Posts</x-slot:title>

    <x-slot:jsonLd>{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES) !!}</x-slot:jsonLd>

    <h2>Recent Posts</h2>

    <div class="post-grid">
        @forelse($posts as $post)
            <x-post-card :post="$post" />
        @empty
            <p>No posts yet.</p>
        @endforelse
    </div>

    {{ $posts->links() }}

</x-layout>
