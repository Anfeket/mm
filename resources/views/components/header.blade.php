<header id="header">

    <div id="top">

        <h1 id="site-name"><a href="{{ url('/') }}">{{ config('app.name') }}</a></h1>

        <x-theme />

        @auth
            <div id="user-menu">
                <button class="user-menu-trigger" aria-haspopup="true" aria-expanded="false">
                    @if (Auth::user()->avatar_path)
                        <img src="{{ asset('uploads/' . Auth::user()->avatar_path) }}" alt="Avatar" width="32" height="32" class="avatar">
                    @endif
                    <span>{{ Auth::user()->username }}</span>
                    <span class="user-menu-caret">▼</span>
                </button>
                <div class="user-menu-dropdown" role="menu">
                    <a href="{{ route('account.show') }}" role="menuitem">Account</a>
                    <div class="user-menu-divider"></div>
                    <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="user-menu-logout" role="menuitem">Logout</button>
                    </form>
                </div>
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

        <div style="flex: 1;"></div>

        <x-search />
    </nav>

    <script>
        document.querySelector('.user-menu-trigger')?.addEventListener('click', function() {
            this.closest('#user-menu').classList.toggle('open');
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('#user-menu')) {
                document.querySelector('#user-menu')?.classList.remove('open');
            }
        });
    </script>

</header>
