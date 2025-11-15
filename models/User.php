<?php
require_once __DIR__ . '/../models/Invite.php';
require_once __DIR__ . '/../models/Auth.php';

class User
{
	public static function findByUsername($username)
	{
		global $pdo;
		$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
		$stmt->execute([$username]);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	public static function create($username, $email, $password_hash)
	{
		global $pdo;
		$stmt = $pdo->prepare("INSERT INTO users (username,email,password_hash) VALUES (?,?,?)");
		$stmt->execute([$username, $email, $password_hash]);
		return $pdo->lastInsertId();
	}

	public static function findById($id)
	{
		global $pdo;
		$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
		$stmt->execute([$id]);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	public static function update($id, $username, $email, $password = null, $avatar = null)
	{
		global $pdo;

		$fields = [];
		$params = [];

		if ($username !== null) {
			$fields[] = "username = ?";
			$params[] = $username;
		}
		if ($email !== null) {
			$fields[] = "email = ?";
			$params[] = $email;
		}
		if ($password !== null) {
			$fields[] = "password_hash = ?";
			$params[] = password_hash($password, PASSWORD_DEFAULT);
		}
		if ($avatar !== null) {
			$fields[] = "avatar = ?";
			$params[] = $avatar;
		}

		if (empty($fields)) return false;

		$params[] = $id;
		$sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
		$stmt = $pdo->prepare($sql);
		return $stmt->execute($params);
	}

	public static function createInvite($userId)
	{
		Auth::require_login(); // make sure caller is logged in

		$inviteCode = Invite::create($userId);

		return $inviteCode; // return the code so you can show/copy it
	}

	public static function has_permission($userId, $permission)
	{
		global $pdo;
		$stmt = $pdo->prepare("
			SELECT 1
			FROM user_roles ur
			JOIN role_permissions rp ON ur.role_id = rp.role_id
			JOIN permissions p ON rp.permission_id = p.id
			WHERE ur.user_id = :userId AND p.name = :permissionName
			LIMIT 1
		");
		$stmt->execute([
			':userId' => $userId,
			':permissionName' => $permission
		]);
		return $stmt->fetchColumn() !== false;
	}
}
