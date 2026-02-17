<x-layout>
    <x-slot:title>Login</x-slot:title>

    <div class="auth-container">
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

            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    </div>
</x-layout>
