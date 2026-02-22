<header id="header">

    <div id="top">

        <h1 id="site-name"><a href="{{ url('/') }}">{{ config('app.name') }}</a></h1>

        <x-theme />

        @auth
            <div id="user-menu">
                <a href="{{ route('profile.show') }}">
                @if (Auth::user()->avatar_path)
                    <img src="{{ asset('uploads/' . Auth::user()->avatar_path) }}" alt="Avatar" width="24" height="24" class="avatar">
                @endif
                {{ Auth::user()->username }}
                </a>
                <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit">Logout</button>
                </form>

            </div>
        @else
            <div id="auth-links">
                <a href="{{ route('login') }}">Login</a>
                <a href="{{ route('register') }}">Register</a>
            </div>
        @endauth
    </div>

    <nav id="main-nav">
        <a href="{{ route('posts.index') }}">Posts</a>
        <a href="{{ route('tags') }}">Tags</a>
        <a href="{{ route('artists') }}">Artists</a>
        <a href="{{ route('pools') }}">Pools</a>
        <a href="{{ route('wiki') }}">Wiki</a>
        <a href="{{ route('forum') }}">Forum</a>
        @auth
            <a href="{{ route('posts.create') }}">Upload</a>
        @endauth
    </nav>

</header>
