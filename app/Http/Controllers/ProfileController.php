<?php

namespace App\Http\Controllers;

use App\Models\Invite;
use App\Models\User;

use Illuminate\Http\UploadedFile;
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
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:10240'],
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

    private function processAvatar(UploadedFile $file, User $user): string
    {
        $source = imagecreatefromstring(file_get_contents($file->getRealPath()));

        if (!$source) {
            throw new \RuntimeException("Could not read avatar image");
        }

        $origW      = imagesx($source);
        $origH      = imagesy($source);
        $thumbSize  = config('media.avatar.size', 128);
        $scale      = max($thumbSize / $origW, $thumbSize / $origH);
        $thumbW     = (int)($origW * $scale);
        $thumbH     = (int)($origH * $scale);

        $offsetX = (int)(($thumbW - $thumbSize) / 2);
        $offsetY = (int)(($thumbH - $thumbSize) / 2);

        $scaled = imagecreatetruecolor($thumbW, $thumbH);
        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);
        imagecopyresampled($scaled, $source, 0, 0, 0, 0, $thumbW, $thumbH, $origW, $origH);

        $thumb = imagecreatetruecolor($thumbSize, $thumbSize);
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        imagecopy($thumb, $scaled, 0, 0, $offsetX, $offsetY, $thumbW, $thumbH);

        $dir = storage_path('app/uploads/avatars/');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = "avatars/{$user->id}.webp";
        imagewebp($thumb, storage_path('app/uploads/' . $path), config('media.avatar.quality', 80));

        return $path;
    }
}
