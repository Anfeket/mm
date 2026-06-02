@props(['post', 'discordConfigured' => false])

@canany(['delete', 'toggleVisibility'], $post)
<section class="post-details-section">
    <h3>Actions</h3>

    <div class="post-actions">
        @can('toggleVisibility', $post)
            <form action="{{ route('posts.toggleVisibility', $post) }}" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-warning">
                    {{ $post->is_listed ? 'Hide post' : 'Unhide post' }}
                </button>
            </form>
        @endcan

        @can('delete', $post)
            <form action="{{ route('posts.destroy', $post) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this post?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Delete post</button>
            </form>
        @endcan

        @can('sendWebhook', $post)
            <form action="{{ route('posts.sendWebhook', $post) }}" method="POST" class="has-tooltip">
                @csrf
                <button
                    type="submit"
                    class="btn btn-info {{ !$discordConfigured ? 'disabled' : '' }}"
                    @disabled(!$discordConfigured)
                >
                    Send webhook
                </button>
                @unless($discordConfigured)
                    <div class="tooltip">Discord webhook URL is not configured.</div>
                @endunless
            </form>
        @endcan
    </div>
</section>
@endcanany
