<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @isset($title)
        <title>{{ $title }} - {{ config('app.name') }}</title>
    @else
        <title>{{ config('app.name') }}</title>
    @endisset

    @vite('resources/css/app.css')
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
