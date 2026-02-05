<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'mm' }}</title>

    @vite('resources/css/app.css')
</head>
<body>
    <x-header />

    <main id="main">
        {{ $slot }}
    </main>

    <x-footer />
</body>
</html>
