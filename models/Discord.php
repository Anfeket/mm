<?php
require_once __DIR__ . '/Tag.php';

class Discord
{
	public static function postWebhook(string $url, array $data): bool
	{
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
			CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
			CURLOPT_TIMEOUT => 5,
		]);
		curl_exec($ch);

		$code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		curl_close($ch);
		return $code >= 200 && $code < 300;
	}

	public static function getWebhook(): string|false
	{
		return getenv('DISCORD_WEBHOOK_URL');
	}

	public static function postUpload(array $post): void
	{
		$url = self::getWebhook();

		$fields = [];
		$tags = Tag::forPost($post['id']);
		foreach ($tags as $category => $tags) {
			if (empty($tags)) continue;

			$fields[] = [
				'name' => ucfirst($category),
				'value' => implode(' ', $tags),
				'inline' => true,
			];
		}

		$embed = [
			'title' => "New post #{$post['id']}",
			'url' => BASE_URL . "/post/{$post['id']}",
			'timestamp' => gmdate('Y-m-d\TH:i:s\Z', strtotime($post['created_at'])),
			'image' => [
				'url' => BASE_URL . $post['thumb_path']
			]
		];
		if (!empty($fields)) {
			$embed['fields'] = $fields;
		}

		$data = [
			'username' => 'mm',
			'embeds' => [$embed],
		];

		self::postWebhook($url, $data);
		return;
	}
}
