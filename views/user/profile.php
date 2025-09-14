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

	<hr style="margin: 2em 0;">

	<section id="invites">
		<h2>Invites</h2>

		<form action="/profile/invite" method="post" style="margin-bottom: 1em;">
			<button type="submit">Generate New Invite</button>
		</form>

		<?php if (!empty($invites)): ?>
			<ul>
				<?php foreach ($invites as $invite): ?>
					<li>
						<code><?= htmlspecialchars($invite['code']) ?></code>
						<?php if ($invite['used_by']): ?>
							– used by <?= htmlspecialchars($invite['used_by_username']) ?> at <?= htmlspecialchars($invite['used_at']) ?>
						<?php else: ?>
							– unused
							<form action="/profile/invite/delete/<?= $invite['code'] ?>" method="post" style="display:inline;">
								<button type="submit" class="invite-delete" onclick="return confirm('Delete this invite?')">✖</button>
							</form>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else: ?>
			<p>No invites generated yet.</p>
		<?php endif; ?>
	</section>
</main>
