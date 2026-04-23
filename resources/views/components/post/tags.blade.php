@props(['tags', 'post'])
@push('scripts')
    @vite('resources/js/tag-edit.tsx')
@endpush
@use(App\TagCategory)

<section id="tag-list" class="post-details-section">
    @can('editTags', $post)
        <form action="{{ route('posts.tags.attach', $post) }}" method="POST" id="tag-attach-form" class="">
            @csrf
            <input type="text" name="tag" id="tag-name-input" placeholder="Add a tag..." class="form-input" autocomplete="off" data-autocomplete>
        </form>
        <div id="tag-actions">
            <button type="button" class="tag-edit-btn" id="tag-edit-btn" title="Edit tags">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                </svg>
            </button>
        </div>
    @endcan

    @foreach (TagCategory::cases() as $category)
        @if ($tags->where('category', $category)->isNotEmpty())
            <div class="tag-category-wrapper">
                <h3 class="tag-category">{{ $category->label() }}</h3>
                <ul class="tag-list">
                    @foreach ($tags->where('category', $category) as $tag)
                        <li class="tag-item" data-tag-id="{{ $tag->id }}">
                            <form action="{{ route('posts.tags.detach', [$post, $tag]) }}" method="POST" class="tag-remove-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="tag-remove-btn" aria-label="Remove tag">×</button>
                            </form>
                            <a href="{{ $tag->url() }}" class="tag tag-{{ $category->value }}">{{ $tag->name }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endforeach

    @if ($tags->isEmpty())
        <p>No tags found.</p>
    @endif

</section>

