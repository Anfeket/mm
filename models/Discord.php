<?php
class Discord
{
	public static function postWebhook(string $url, array $data): bool
	{
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_PORT => true,
			CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
			CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
			CURLOPT_TIMEOUT => 10,
		]);
		curl_exec($ch);
		$code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		curl_close($ch);
		return $code >= 200 && $code < 300;
	}

	public static function postUpload(array $post): void
	{
		$url = getenv('DISCORD_WEBHOOK_URL');
		if (!$url) return;

		$fields = [];
		$tags = Tag::forPost($post['id']);
		foreach ($tags as $category => $tags) {
			if (empty($tags)) continue;

			$fields[] = [
				'name' => strtoupper($category),
				'value' => implode(' ', $tags),
				'inline' => true,
			];
		}

		$embed = [
			'title' => "New post #{$post['id']}",
			'url' => BASE_URL . "/post/{$post['id']}",
			'timestamp' => gmdate('Y-m-d\TH:i:s\Z', strtotime($post['created_at'])),
			'thumbnail' => BASE_URL . $post['thumb_path']
		];
		if ($post['post_type'] === 'video') {
			$embed['video'] = [
				'url' => BASE_URL . '/' . $post['file_path']
			];
			if (isset($post['width'], $post['height'])) {
				$embed['video']['width'] = (int)$post['width'];
				$embed['video']['height'] = (int)$post['height'];
			}
		} elseif ($post['post_type'] === 'image') {
			$embed['image'] = [
				'url' => BASE_URL . '/' . $post['file_path']
			];
			if (isset($post['width'], $post['height'])) {
				$embed['image']['width'] = (int)$post['width'];
				$embed['image']['height'] = (int)$post['height'];
			}
		}
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
