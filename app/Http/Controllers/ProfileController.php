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
            $crop = $request->filled('crop_size') ? [
                'x'     => (int) $request->input('crop_x', 0),
                'y'     => (int) $request->input('crop_y', 0),
                'size'  => (int) $request->input('crop_size'),
            ] : null;

            $user->avatar_path = $this->processAvatar($request->file('avatar'), $user, $crop);
        }

        $user->save();

        return redirect()->route('profile.show')->with('status', 'Profile updated successfully.');
    }

    private function processAvatar(UploadedFile $file, User $user, ?array $crop = null): string
    {
        $dir = storage_path('app/uploads/avatars/');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = "avatars/{$user->id}.webp";
        $dest = storage_path('app/uploads/' . $path);

        if ($this->isAnimated($file)) {
            $this->processAvatarAnimated($file, $dest, $crop);
        } else {
            $this->processAvatarStatic($file, $dest, $crop);
        }

        return $path;
    }

    private function processAvatarStatic(UploadedFile $file, string $dest, ?array $crop = null): void
    {
        $source = imagecreatefromstring(file_get_contents($file->getRealPath()));

        if (!$source) {
            throw new \RuntimeException("Could not read avatar image");
        }

        $origW     = imagesx($source);
        $origH     = imagesy($source);
        $thumbSize = config('media.avatar.size', 128);

        $thumb = imagecreatetruecolor($thumbSize, $thumbSize);
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);

        if ($crop) {
            $cropX    = max(0, min($crop['x'], $origW - 1));
            $cropY    = max(0, min($crop['y'], $origH - 1));
            $cropSize = max(1, min($crop['size'], $origW - $cropX, $origH - $cropY));

            imagecopyresampled(
                $thumb,
                $source,
                0,
                0,
                $cropX,
                $cropY,
                $thumbSize,
                $thumbSize,
                $cropSize,
                $cropSize
            );
        } else {
            $scale   = max($thumbSize / $origW, $thumbSize / $origH);
            $thumbW  = (int)($origW * $scale);
            $thumbH  = (int)($origH * $scale);
            $offsetX = (int)(($thumbW - $thumbSize) / 2);
            $offsetY = (int)(($thumbH - $thumbSize) / 2);

            $scaled = imagecreatetruecolor($thumbW, $thumbH);
            imagealphablending($scaled, false);
            imagesavealpha($scaled, true);
            imagecopyresampled($scaled, $source, 0, 0, 0, 0, $thumbW, $thumbH, $origW, $origH);
            imagecopy($thumb, $scaled, 0, 0, $offsetX, $offsetY, $thumbW, $thumbH);
        }

        imagewebp($thumb, $dest, config('media.avatar.quality', 80));
    }

    private function processAvatarAnimated(UploadedFile $file, string $dest, ?array $crop = null): void
    {
        $thumbSize = config('media.avatar.size', 128);
        $quality   = config('media.avatar.quality', 80);

        $imagick = new \Imagick();
        $imagick->readImage($file->getRealPath());
        $imagick = $imagick->coalesceImages();

        $origW = $imagick->current()->getImageWidth();
        $origH = $imagick->current()->getImageHeight();

        // Calculate crop/scale params once, same for all frames
        if ($crop) {
            $cropX    = max(0, min($crop['x'], $origW - 1));
            $cropY    = max(0, min($crop['y'], $origH - 1));
            $cropSize = max(1, min($crop['size'], $origW - $cropX, $origH - $cropY));
        } else {
            $scale    = max($thumbSize / $origW, $thumbSize / $origH);
            $cropSize = (int)(min($origW, $origH));
            $cropX    = (int)(($origW - $cropSize) / 2);
            $cropY    = (int)(($origH - $cropSize) / 2);
        }

        foreach ($imagick as $frame) {
            $frame->cropImage($cropSize, $cropSize, $cropX, $cropY);
            $frame->thumbnailImage($thumbSize, $thumbSize);
            $frame->setImagePage($thumbSize, $thumbSize, 0, 0);
            $frame->setImageCompressionQuality($quality);
        }

        $imagick = $imagick->deconstructImages();
        $imagick->setFormat('webp');
        $imagick->setOption('webp:loop', '0');

        file_put_contents($dest, $imagick->getImagesBlob());
        $imagick->clear();
    }

    private function isAnimated(UploadedFile $file): bool
    {
        if (!in_array($file->getMimeType(), ['image/gif', 'image/webp'])) {
            return false;
        }

        $imagick = new \Imagick();
        $imagick->pingImage($file->getRealPath());
        $frames = $imagick->getNumberImages();
        $imagick->clear();

        return $frames > 1;
    }
}
