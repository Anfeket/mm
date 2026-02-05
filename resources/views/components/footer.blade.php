<footer id="footer">
    <p>
        &copy; {{ date('Y') }} mm -
        @if(config('build.hash'))
            <a href="{{ config('build.repo_url') }}" target="_blank" rel="noopener noreferrer">{{ config('build.hash') }}</a>
        @else
            <a href="{{ config('build.repo_url') }}" target="_blank" rel="noopener noreferrer">dev</a>
        @endif

        @if(config('build.date'))
            @ {{ config('build.date') }}
        @endif
    </p>
</footer>
