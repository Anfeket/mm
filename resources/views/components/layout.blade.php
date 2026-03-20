<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @if (config('app.google_site_verification'))
        <meta name="google-site-verification" content="{{ config('app.google_site_verification') }}">
    @endif
    @isset($title)
        <title>{{ $title }} - {{ config('app.short_name') }}</title>
    @else
        <title>{{ config('app.name') }}</title>
    @endisset

    <meta name="description" content="{{ $description ?? config('app.description') }}">

    @isset($jsonLd)
        <script type="application/ld+json">{!! $jsonLd !!}</script>
    @endisset

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:title" content="{{ isset($title) ? $title . ' - ' . config('app.short_name') : config('app.name') }}">
    <meta property="og:description" content="{{ $description ?? config('app.description') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @isset($ogImage)
        <meta property="og:image" content="{{ asset('uploads/' . $ogImage) }}">
    @endisset
    @isset($ogVideo)
        <meta property="og:video" content="{{ asset('uploads/' . $ogVideo) }}">
        <meta property="og:video:type" content="{{ $ogVideoType ?? 'video/mp4' }}">
    @endisset

    <meta name="twitter:card" content="{{ isset($ogImage) ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $title ?? config('app.name') }}">
    <meta name="twitter:description" content="{{ $description ?? config('app.description') }}">
    @isset($ogImage)
        <meta name="twitter:image" content="{{ asset('uploads/' . $ogImage) }}">
    @endisset

    @vite(['resources/css/app.css', 'resources/js/search.js'])
    @stack('scripts')
</head>
<body>
    <x-header />

    <div id="container" @class(['with-sidebar' => isset($sidebar)])>

        @if (isset($sidebar))
            <aside id="sidebar">
                {{ $sidebar }}
            </aside>
        @endif

        <main id="main">
            {{ $slot }}
        </main>

    </div>

    <x-footer />
</body>
</html>
