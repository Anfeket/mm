@props(['tags'])
@use(App\TagCategory)

<section id="tag-list" class="post-details-section">

    @foreach (TagCategory::cases() as $category)
        @if ($tags->where('category', $category)->isNotEmpty())
            <h3 class="tag-category">{{ $category->label() }}</h3>
            <ul class="tag-list">
                @foreach ($tags->where('category', $category) as $tag)
                    <li><a href="{{ route('tags.show', $tag) }}" class="tag tag-{{ $category->value }}">{{ $tag->name }}</a></li>
                @endforeach
            </ul>
        @endif
    @endforeach

    @if ($tags->isEmpty())
        <p>No tags found.</p>
    @endif

</section>
