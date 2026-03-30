<?php

namespace App\Http\Controllers;

use App\Models\Invite;
use App\Models\User;

use App\Jobs\ProcessAvatar;

use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load('invites');
        return view('account.show', compact('user'));
    }

    public function createInvite(Request $request)
    {
        $user = $request->user();
        $invite = new Invite();
        $invite->code = Str::random(32);
        $invite->created_by = $user->id;
        $invite->save();

        return redirect()->route('account.show')->with('success', 'Invite created: ' . $invite->code);
    }

    public function deleteInvite(Request $request, Invite $invite)
    {
        $user = $request->user();

        if ($invite->created_by !== $user->id) {
            abort(403);
        }

        if ($invite->used_at) {
            return redirect()->route('account.show')->with('error', 'Cannot delete an invite that has already been used.');
        }

        $invite->delete();

        return redirect()->route('account.show')->with('success', 'Invite deleted.');
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'username' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('users')->ignore($user->id)],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:10240'],
            'crop_x' => ['nullable', 'integer', 'min:0'],
            'crop_y' => ['nullable', 'integer', 'min:0'],
            'crop_size' => ['nullable', 'integer', 'min:10'],
        ]);

        if (isset($data['username'])) {
            $user->username = $data['username'];
        }

        if (isset($data['email'])) {
            $user->email = $data['email'];
        }

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if ($request->hasFile('avatar')) {
            $tempPath = $request->file('avatar')->store('temp/avatars');
            $crop = $request->filled('crop_size') ? [
                'x'     => (int) $request->input('crop_x', 0),
                'y'     => (int) $request->input('crop_y', 0),
                'size'  => (int) $request->input('crop_size'),
            ] : null;

            ProcessAvatar::dispatch($user, $tempPath, $crop);
        }

        $user->save();

        return redirect()->route('account.show')->with('status', 'Account updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->input('new_password')),
        ]);

        return redirect()->route('account.show')->with('status', 'Password updated successfully.');
    }
}
