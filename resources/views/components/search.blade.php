@props([
    'action' => route('posts.index'),
    'placeholder' => 'search posts...',
    'autofocus' => false,
])

<form action="{{ $action }}" method="GET" class="search-form">
    <input
        type="search"
        name="q"
        value="{{ request('q') }}"
        placeholder="{{ $placeholder }}"
        class="search-input"
        autocomplete="off"
        aria-label="Search"
        @if ($autofocus) autofocus @endif
    >
</form>
