<x-layout>
    <x-slot:title>Recent Posts</x-slot:title>

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
