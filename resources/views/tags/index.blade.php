@use(App\TagCategory)

<x-layout>
    <x-slot:title>Tags</x-slot:title>

    <div class="page-content">
        <header class="tag-index-header">
        <h2>Tags</h2>

        <form method="GET" action="{{ route('tags') }}" class="search-form">
            <input
                type="search"
                name="tag_q"
                value="{{ $query }}"
                placeholder="Search tags..."
                class="search-input"
                autocomplete="off"
                aria-label="Search tags"
            >
        </form>
    </header>

    @unless($query)
        <section class="tag-index-section">
            <h3>Top Tags</h3>

            @if($topTags->isNotEmpty())
                <ul class="tag-list tag-index-list tag-index-list-inline">
                    @foreach($topTags as $tag)
                        <li class="tag-item tag-index-item">
                            <a href="{{ $tag->url() }}" class="tag tag-{{ $tag->category->value }}">{{ $tag->name }}</a>
                            <span class="tag-count">{{ $tag->post_count }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="tag-index-empty">No tags with posts yet.</p>
            @endif
        </section>
    @endunless

    <section class="tag-index-section">
        @if($query)
            <h3>Results for "{{ $query }}"</h3>
        @else
            <h3>By Category</h3>
        @endif

        <div class="tag-index-categories">
            @foreach(TagCategory::cases() as $category)
                @php $categoryTags = $byCategory[$category->value] ?? [] @endphp
                @if(count($categoryTags) > 0)
                    <div class="tag-category-wrapper">
                        <h4 class="tag-category">{{ $category->label() }}</h4>

                        <ul class="tag-list tag-index-list">
                            @foreach($categoryTags as $tag)
                                <li class="tag-item tag-index-item">
                                    <a href="{{ $tag->url() }}" class="tag tag-{{ $category->value }}">{{ $tag->name }}</a>
                                    <span class="tag-count">{{ $tag->post_count }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach
        </div>
    </section>
    </div>

</x-layout>
