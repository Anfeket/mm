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

            <div class="form-field">
                <label class="form-label" for="username">Username</label>
                <input type="text" name="username" value="{{ old('username') }}" class="form-input" required autofocus>
            </div>

            <div class="form-field">
                <label class="form-label" for="password">Password</label>
                <input type="password" name="password" class="form-input" required>
            </div>

            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    </div>
</x-layout>
