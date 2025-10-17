<?php
session_start();

class Auth
{
	public static function current_user()
	{
		global $pdo;
		if (!isset($_SESSION['user_id'])) return null;
		$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
		$stmt->execute([$_SESSION['user_id']]);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	public static function require_login()
	{
		if (!isset($_SESSION['user_id'])) {
			header("Location: /login");
			exit;
		}
	}

	public static function log_ip($user_id)
	{
		global $pdo;
		$ip = $_SERVER['REMOTE_ADDR'] ?? null;
		$stmt = $pdo->prepare("
			UPDATE users
			SET last_login = NOW(), last_login_ip = ?
			WHERE id = ?");
		$stmt->execute([$ip, $user_id]);
	}
}
