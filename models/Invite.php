<?php
class Invite
{
	public static function create($userId)
	{
		global $pdo;

		$code = bin2hex(random_bytes(16)); // 16-char random hex

		$stmt = $pdo->prepare("
            INSERT INTO invites (code, created_by)
            VALUES (:code, :created_by)
        ");
		$stmt->execute([
			':code' => $code,
			':created_by' => $userId
		]);

		return $code;
	}

	public static function delete($inviteId, $userId)
	{
		global $pdo;

		// Only delete if the current user owns it and it's unused
		$stmt = $pdo->prepare("
        DELETE FROM invites 
        WHERE id = :id AND created_by = :uid AND used_by IS NULL
    ");
		$stmt->execute([
			':id' => $inviteId,
			':uid' => $userId
		]);
	}

	public static function getByUser($userId)
	{
		global $pdo;

		$stmt = $pdo->prepare("
            SELECT i.*, u.username AS used_by_username
            FROM invites i
            LEFT JOIN users u ON i.used_by = u.id
            WHERE i.created_by = :uid
            ORDER BY i.created_at DESC
        ");
		$stmt->execute([':uid' => $userId]);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public static function useCode($code, $newUserId)
	{
		global $pdo;

		// Fetch invite
		$stmt = $pdo->prepare("SELECT * FROM invites WHERE code = :code AND used_by IS NULL");
		$stmt->execute([':code' => $code]);
		$invite = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$invite) {
			return false; // invalid or already used
		}

		// Mark as used
		$stmt = $pdo->prepare("
            UPDATE invites
            SET used_by = :uid, used_at = NOW()
            WHERE id = :id
        ");
		$stmt->execute([
			':uid' => $newUserId,
			':id' => $invite['id']
		]);

		return true;
	}
}
