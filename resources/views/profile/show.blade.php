@push('scripts')
    @vite('resources/js/profile.js')
@endpush

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

                <div class="form-field">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" name="username" value="{{ old('username', $user->username) }}" autocomplete="username" class="form-input">
                </div>

                <div class="form-field">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" autocomplete="email" class="form-input">
                </div>

                <div class="form-field">
                    <label class="form-label" for="avatar">Avatar</label>
                    <div id="dropzone" class="dropzone">
                        <input type="file" name="avatar" accept="image/*" class="form-input" id="avatar-input">
                        <input type="hidden" name="crop_x" id="crop_x">
                        <input type="hidden" name="crop_y" id="crop_y">
                        <input type="hidden" name="crop_size" id="crop_size">
                        <div id="dropzone-prompt" class="dropzone-prompt">
                            <p>Drop file here or <span class="browse-link">browse</span></p>
                        </div>
                        <div id="crop-container" class="hidden">
                            <div id="crop-image-container">
                                <img id="crop-img" src="" alt="Crop Image">
                                <div id="crop-overlay">
                                    <div id="crop-selection">
                                        <div class="crop-handle crop-handle-nw" data-handle="nw"></div>
                                        <div class="crop-handle crop-handle-ne" data-handle="ne"></div>
                                        <div class="crop-handle crop-handle-sw" data-handle="sw"></div>
                                        <div class="crop-handle crop-handle-se" data-handle="se"></div>
                                    </div>
                                </div>
                            </div>
                            <div id="crop-preview" class="hidden">
                                <img id="crop-preview-img" src="" alt="Preview">
                            </div>
                            <div id="crop-actions">
                                <button type="button" class="btn btn-secondary" id="crop-confirm">Crop & Upload</button>
                                <button type="button" class="btn hidden" id="crop-reset">Reset</button>
                                <button type="button" class="btn" id="crop-cancel">Cancel</button>
                            </div>
                        </div>
                    </div>

                    @if ($user->avatar_path)
                        <div class="current-avatar">
                            <p>Current Avatar:</p>
                            <img src="{{ asset('uploads/' . $user->avatar_path) }}" alt="Avatar" width="100" height="100">
                        </div>
                    @endif
                </div>

                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
        </section>

        <section class="profile-section">
            <h2>Change Password</h2>

            <form action="{{ route('profile.password') }}" method="POST" class="profile-form">
                @csrf
                @method('PUT')

                <div class="form-field">
                    <label class="form-label" for="current_password">Current Password</label>
                    <input type="password" name="current_password" autocomplete="current-password" class="form-input">
                </div>

                <div class="form-field">
                    <label class="form-label" for="new_password">New Password</label>
                    <input type="password" name="new_password" autocomplete="new-password" minlength="8" class="form-input">
                </div>

                <div class="form-field">
                    <label class="form-label" for="new_password_confirmation">Confirm New Password</label>
                    <input type="password" name="new_password_confirmation" autocomplete="new-password" minlength="8" class="form-input">
                </div>

                <button type="submit" class="btn btn-primary">Change Password</button>
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
