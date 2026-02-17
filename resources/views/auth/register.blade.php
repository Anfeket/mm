<x-layout>
    <x-slot:title>Register</x-slot:title>

    <div class="auth-container">
        <h2>Register</h2>

        @if ($errors->any())
            <div class="alert alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST" class="auth-form">
            @csrf

            <input type="hidden" name="invite" value="{{ old('invite', $invite) }}">

            <label class="form-label">
                Username
                <input type="text" name="username" value="{{ old('username') }}" class="form-input" required autofocus>
            </label>

            <label class="form-label">
                Email
                <input type="email" name="email" value="{{ old('email') }}" class="form-input" required>
            </label>

            <label class="form-label">
                Password
                <input type="password" name="password" class="form-input" required>
            </label>

            <button type="submit" class="btn btn-primary">Register</button>
        </form>
    </div>
</x-layout>
