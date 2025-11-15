<?php
require_once __DIR__ . '/../models/Auth.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/Discord.php';
require_once __DIR__ . '/../controllers/ErrorController.php';
require_once __DIR__ . '/../controllers/PostController.php';

$user = Auth::current_user();
if (!$user || !User::has_permission($user['id'], 'access_admin_panel')) {
	$controller = new ErrorController();
	$controller->forbidden();
	exit;
}

class AdminController
{
	public static function handle()
	{
		$command = $_GET['c'] ?? null;

		switch ($command) {
			case 'send-webhook':
				self::sendWebhook($_POST['post_id'] ?? null);
				break;
			case 'generate-thumbnail':
				self::regenerateThumbnail($_POST['post_id'] ?? null);
				break;
			case 'delete-post':
				self::deletePost($_POST['post_id'] ?? null);
				break;
		}

		include __DIR__ . '/../views/layout/head.php';
		include __DIR__ . '/../views/layout/header.php';
		include __DIR__ . '/../views/admin/admin-panel.php';
		include __DIR__ . '/../views/layout/footer.php';
	}

	private static function sendWebhook($postId)
	{
		if (!$postId) return;

		$post = Post::find($postId);

		if (!$post) return;
		try {
			Discord::postUpload($post);
			echo "Webhook sent successfully.";
		} catch (Throwable $e) {
			echo "Error sending webhook: " . htmlspecialchars($e->getMessage());
		}
	}

	private static function regenerateThumbnail($postId)
	{
		if (!$postId) return;

		$post = Post::find($postId);

		if (!$post) return;
		try {
			$path = __DIR__ . '/../public' . $post['file_path'];
			Post::generateThumbnail($path, $post['mime_type'], $post['file_hash'], true);
			echo "Thumbnail generated successfully.";
		} catch (throwable $e) {
			echo "Error generating thumbnail: " . htmlspecialchars($e->getMessage());
		}
	}

	private static function deletePost($postId)
	{
		if (!$postId) return;

		$post = Post::find($postId);

		if (!$post) return;
		try {
			PostController::delete($postId);
			echo "Post deleted successfully.";
		} catch (throwable $e) {
			echo "Error deleting post: " . htmlspecialchars($e->getMessage());
		}
	}
}
