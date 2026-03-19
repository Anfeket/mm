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

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    <link rel="canonical" href="{{ url()->current() }}">

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
