<header id="header">
    <div id="top">
        <h1><a href="{{ url('/') }}">mm</a></h1>
        @auth
            <div id="user-menu">
                <a href="{{ route('profile') }}">
                @if (Auth::user()->avatar)
                    <img src="{{ Auth::user()->avatar }}" alt="Avatar" width="32" height="32" class="avatar">
                @endif
                {{ Auth::user()->name }}
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
        <nav>
            <a href="{{ route('posts.index') }}">Posts</a>
            <a href="{{ route('tags') }}">Tags</a>
            <a href="{{ route('artists') }}">Artists</a>
            <a href="{{ route('pools') }}">Pools</a>
            <a href="{{ route('wiki') }}">Wiki</a>
            <a href="{{ route('forum') }}">Forum</a>
            @auth
                <a href="{{ route('upload') }}">Upload</a>
            @endauth
        </nav>
    </div>
</header>
