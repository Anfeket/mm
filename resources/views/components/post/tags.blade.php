@props(['tags'])
@use(App\TagCategory)
<section id="tag-list" class="post-details">
    @if ($tags->where('category', TagCategory::Artist)->isNotEmpty())
        <h3 class="tag-category">Artists</h3>
        <ul class="tag-list">
            @foreach ($tags->where('category', TagCategory::Artist) as $tag)
                <li><a href="{{ route('tags.show', $tag) }}" class="tag tag-artist">{{ $tag->name }}</a></li>
            @endforeach
        </ul>
    @endif
    @if ($tags->where('category', TagCategory::Copyright)->isNotEmpty())
        <h3 class="tag-category">Copyrights</h3>
        <ul class="tag-list">
            @foreach ($tags->where('category', TagCategory::Copyright) as $tag)
                <li><a href="{{ route('tags.show', $tag) }}" class="tag tag-copyright">{{ $tag->name }}</a></li>
            @endforeach
        </ul>
    @endif
    @if ($tags->where('category', TagCategory::General)->isNotEmpty())
        <h3 class="tag-category">General</h3>
        <ul class="tag-list">
            @foreach ($tags->where('category', TagCategory::General) as $tag)
                <li><a href="{{ route('tags.show', $tag) }}" class="tag tag-general">{{ $tag->name }}</a></li>
            @endforeach
        </ul>
    @endif
    @if ($tags->where('category', TagCategory::Meta)->isNotEmpty())
        <h3 class="tag-category">Meta</h3>
        <ul class="tag-list">
            @foreach ($tags->where('category', TagCategory::Meta) as $tag)
                <li><a href="{{ route('tags.show', $tag) }}" class="tag tag-meta">{{ $tag->name }}</a></li>
            @endforeach
        </ul>
    @endif
</section>
