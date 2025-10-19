<footer id="footer">
	<p>
		&copy; <?= date('Y') ?> mm -
		<?php if (BUILD_HASH !== null): ?>
			<a href="<?= REPO_URL ?>commit/<?= urlencode(BUILD_HASH) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars(BUILD_HASH) ?></a>
		<?php else: ?>
			<a href="<?= REPO_URL ?>" target="_blank" rel="noopener noreferrer">dev</a>
		<?php endif; ?>
		<?php if (BUILD_DATE !== null): ?> @ <?= htmlspecialchars(BUILD_DATE) ?><?php endif; ?>
	</p>
</footer>
</body>

</html>
