<?php

namespace App\Http\Controllers;

use App\Models\Invite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load('invites');
        return view('profile.show', compact('user'));
    }

    public function createInvite(Request $request)
    {
        $user = $request->user();
        $invite = new Invite();
        $invite->code = Str::random(32);
        $invite->created_by = $user->id;
        $invite->save();

        return redirect()->route('profile.show')->with('success', 'Invite created: ' . $invite->code);
    }

    public function deleteInvite(Request $request, Invite $invite)
    {
        $user = $request->user();

        if ($invite->created_by !== $user->id) {
            abort(403);
        }

        if ($invite->used_at) {
            return redirect()->route('profile.show')->with('error', 'Cannot delete an invite that has already been used.');
        }

        $invite->delete();

        return redirect()->route('profile.show')->with('success', 'Invite deleted.');
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'username' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('users')->ignore($user->id)],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
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
            $user->avatar_path = $this->processAvatar($request->file('avatar'), $user);
        }

        $user->save();

        return redirect()->route('profile.show')->with('status', 'Profile updated successfully.');
    }
}
