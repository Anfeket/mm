<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Auth.php';
require_once __DIR__ . '/../models/Invite.php';

class ProfileController
{
	public static function show()
	{
		Auth::require_login();
		$user = Auth::current_user();

		$title = "Profile - mm";
		$invites = Invite::getByUser($user['id']);
		include __DIR__ . '/../views/layout/head.php';
		include __DIR__ . '/../views/layout/header.php';
		include __DIR__ . '/../views/user/profile.php';
		include __DIR__ . '/../views/layout/footer.php';
	}

	public static function update()
	{
		Auth::require_login();
		$user = Auth::current_user();

		$username = $_POST['username'] ?? null;
		$email    = $_POST['email'] ?? null;
		$password = $_POST['password'] ?: null;

		// Handle avatar upload if set
		$avatar = $user['avatar']; // Keep current avatar by default

		if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
			// Validate upload
			$maxFileSize = 2 * 1024 * 1024; // 2MB
			$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

			if ($_FILES['avatar']['size'] > $maxFileSize) {
				$_SESSION['error'] = "Avatar must be less than 2MB";
				header("Location: /profile");
				exit;
			}

			$finfo = new finfo(FILEINFO_MIME_TYPE);
			$mime = $finfo->file($_FILES['avatar']['tmp_name']);

			if (!in_array($mime, $allowedTypes)) {
				$_SESSION['error'] = "Invalid image format. Please use JPEG, PNG, GIF, or WebP.";
				header("Location: /profile");
				exit;
			}

			// Process and resize image
			$avatar = self::processAvatar($_FILES['avatar']['tmp_name'], $user['id']);
		}

		User::update($user['id'], $username, $email, $password, $avatar);

		header("Location: /profile");
		exit;
	}

	private static function processAvatar($tmpPath, $userId)
	{
		// Create avatars directory if it doesn't exist
		$avatarDir = UPLOAD_BASE_DIR . "avatars/";
		if (!is_dir($avatarDir)) {
			mkdir($avatarDir, 0775, true);
		}

		// Determine image type and create image resource
		$imageInfo = getimagesize($tmpPath);
		$mime = $imageInfo['mime'];

		switch ($mime) {
			case 'image/jpeg':
				$source = imagecreatefromjpeg($tmpPath);
				break;
			case 'image/png':
				$source = imagecreatefrompng($tmpPath);
				break;
			case 'image/gif':
				$source = imagecreatefromgif($tmpPath);
				break;
			case 'image/webp':
				$source = imagecreatefromwebp($tmpPath);
				break;
			default:
				return null; // Shouldn't happen due to earlier validation
		}

		if (!$source) {
			return null;
		}

		// Get original dimensions
		$origWidth = imagesx($source);
		$origHeight = imagesy($source);

		// Calculate new dimensions (maintain aspect ratio)
		$targetSize = 128;
		$ratio = $origWidth / $origHeight;

		if ($ratio > 1) {
			// Landscape
			$newWidth = $targetSize;
			$newHeight = $targetSize / $ratio;
		} else {
			// Portrait or square
			$newWidth = $targetSize * $ratio;
			$newHeight = $targetSize;
		}

		// Create a new true color image with transparent background
		$thumb = imagecreatetruecolor($targetSize, $targetSize);

		// Fill with transparent background (for PNG/GIF)
		$transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
		imagefill($thumb, 0, 0, $transparent);
		imagesavealpha($thumb, true);

		// Resize the image
		$xOffset = ($targetSize - $newWidth) / 2;
		$yOffset = ($targetSize - $newHeight) / 2;

		imagecopyresampled(
			$thumb,
			$source,
			$xOffset,
			$yOffset,
			0,
			0,
			$newWidth,
			$newHeight,
			$origWidth,
			$origHeight
		);

		// Generate filename and save as WebP
		$filename = "avatar_{$userId}.webp";
		$filepath = $avatarDir . $filename;

		// Save as WebP with 80% quality (good balance between size and quality)
		imagewebp($thumb, $filepath, 80);

		// Clean up
		imagedestroy($source);
		imagedestroy($thumb);

		return 'uploads/avatars/' . $filename;
	}

	public static function createInvite()
	{
		Auth::require_login();

		Invite::create($_SESSION['user_id']);

		header("Location: /profile");
		exit;
	}

	public static function deleteInvite($inviteId)
	{
		Auth::require_login();

		Invite::delete($inviteId, $_SESSION['user_id']);

		header("Location: /profile");
		exit;
	}
}
