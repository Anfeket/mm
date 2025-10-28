<?php
require_once __DIR__ . '/../models/Auth.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/Discord.php';

$user = Auth::current_user();
if (!$user || !Auth::is_admin($user['id'])) {
	http_response_code(403);
	die("Forbidden");
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
}
