<x-layout>
    <x-slot:title>Component Preview</x-slot:title>

    <div style="display: flex; flex-direction: column; gap: var(--space-xl); padding: var(--space-xl);">

        <section>
            <h2>Buttons</h2>
            <div style="display: flex; gap: var(--space-m); flex-wrap: wrap;">
                <a href="#" class="btn">Default</a>
                <a href="#" class="btn disabled">Disabled</a>
            </div>
        </section>

        <section>
            <h2>Alerts</h2>
            <div style="display: flex; flex-direction: column; gap: var(--space-m);">
                <div class="alert alert-success">Success message</div>
                <div class="alert alert-error">Error message</div>
            </div>
        </section>

        <section>
            <h2>Tags</h2>
            <div style="display: flex; gap: var(--space-m); flex-wrap: wrap;">
                @foreach (App\TagCategory::cases() as $category)
                    <a href="#" class="tag tag-{{ $category->value }}">{{ $category->value }}</a>
                @endforeach
            </div>
        </section>

        <section>
            <h2>Autocomplete</h2>
            <x-search />
        </section>

        <section>
            <h2>Pagination</h2>
            {{ App\Models\Post::paginate(5)->links() }}
        </section>

        <section>
            <h2>Tooltips</h2>
            <div style="display: flex; gap: var(--space-m);">
                <span class="has-tooltip btn">
                    Hover me
                    <span class="tooltip">Tooltip text</span>
                </span>
                <span class="has-tooltip btn">
                    Keyboard hint
                    <span class="tooltip"><kbd>Ctrl</kbd> + <kbd>K</kbd></span>
                </span>
            </div>
        </section>

        <section>
            <h2>Post card</h2>
            <div class="post-grid" style="max-width: 400px;">
                @if ($post = App\Models\Post::first())
                    <x-post-card :post="$post" />
                @endif
            </div>
        </section>

    </div>
</x-layout>
