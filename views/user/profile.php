<main id="profile">
	<h2>Edit Profile</h2>

	<?php if (isset($_SESSION['error'])): ?>
		<div class="error-message">
			<?= htmlspecialchars($_SESSION['error']) ?>
			<?php unset($_SESSION['error']); ?>
		</div>
	<?php endif; ?>

	<form action="/profile" method="post" enctype="multipart/form-data">
		<label>Username:
			<input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
		</label>

		<label>Email:
			<input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
		</label>

		<label>Password (leave blank to keep current):
			<input type="password" name="password">
		</label>

		<label>Avatar (max 2MB, will be resized to 128×128 and converted to WebP):
			<input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp">
			<?php if ($user['avatar']): ?>
				<img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar" width="128" style="display: block; margin-top: 10px;">
			<?php endif; ?>
		</label>

		<button type="submit">Save Changes</button>
	</form>
</main>
