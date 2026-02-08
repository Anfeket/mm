<x-layout>
    <x-slot:title>Login</x-slot:title>

    <div class="container-sm">
        <h2>Login</h2>

        @if ($errors->any())
            <div class="alert alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="auth-form">
            @csrf

            <label class="form-label">
                Username
                <input type="text" name="username" value="{{ old('username') }}" class="form-input" required autofocus>
            </label>

            <label class="form-label">
                Password
                <input type="password" name="password" class="form-input" required>
            </label>

            <label class="form-label">
                <input type="checkbox" name="remember"> Remember Me
            </label>

            <button type="submit" class="button button-primary">Login</button>
        </form>
    </div>
</x-layout>
