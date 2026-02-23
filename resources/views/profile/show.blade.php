<x-layout>
    <x-slot:title>Profile</x-slot:title>

    <div class="profile-container">
        @if (session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="profile-section">
            <h2>Edit Profile</h2>

            <form action="{{ route('profile.update') }}" method="POST" class="profile-form" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <label class="form-label">
                    Username
                    <input type="text" name="username" value="{{ old('username', $user->username) }}" class="form-input" required>
                </label>

                <label class="form-label">
                    Email
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-input" required>
                </label>

                <label class="form-label">
                    New Password
                    <input type="password" name="password" class="form-input">
                </label>

                <label class="form-label">
                    Avatar
                    <input type="file" name="avatar" accept="image/*" class="form-input">
                </label>

                @if ($user->avatar_path)
                    <div class="current-avatar">
                        <p>Current Avatar:</p>
                        <img src="{{ asset('uploads/' . $user->avatar_path) }}" alt="Avatar" width="100" height="100">
                    </div>
                @endif

                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
        </section>

        <section class="profile-section">
            <h2>Invites</h2>

            <form action="{{ route('profile.invites.create') }}" method="POST">
                @csrf

                <button type="submit" class="btn">Create new invite</button>
            </form>

            @if ($user->invites->isNotEmpty())
                <ul class="invite-list">
                    @foreach ($user->invites as $invite)
                        <li class="invite-item">
                            <span class="invite-code">{{ $invite->code }}</span>

                            @if ($invite->used_at)
                                <span class="invite-used">Used by {{ $invite->user->username }}</span>
                                <span class="invite-used-date">{{ $invite->used_at->diffForHumans() }}</span>
                            @else
                                <span class="invite-unused">Unused</span>
                                <button type="button" class="btn btn-secondary" onclick="copyInviteLink('{{ $invite->code }}', this)">Copy Link</button>
                                <form action="{{ route('profile.invites.delete', $invite) }}" method="POST" class="invite-delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this invite?')">Delete</button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p>No invites created yet.</p>
            @endif

            <script>
                function copyInviteLink(code, button) {
                    const link = `${window.location.origin}/register?invite=${code}`;
                    navigator.clipboard.writeText(link).then(() => {
                        button.textContent = 'Copied!';
                        setTimeout(() => {
                            button.textContent = 'Copy Link';
                        }, 2000);
                    });
                }
            </script>
        </section>
    </div>
</x-layout>
