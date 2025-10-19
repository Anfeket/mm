<main id="post-index">
	<h2>Recent Posts</h2>
	<div class="post-grid">
		<?php foreach ($posts as $post): ?>
			<div class="post-thumb<?= $post['post_type'] === 'video' ? ' post-video' : '' ?>">
				<a href="/post/<?= $post['id'] ?>">
					<img src="<?= htmlspecialchars($post['thumb_path']) ?>"
						alt="Post #<?= $post['id'] ?>">
				</a>
				<div class="vote-box">
					<a href="/post/<?= $post['id'] ?>/vote/up" class="vote vote-up">▲</a>
					<span class="score"><?= (int)$post['score'] ?></span>
					<a href="/post/<?= $post['id'] ?>/vote/down" class="vote vote-down">▼</a>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<?php if ($totalPages > 1): ?>
		<nav id="index-pagination">
			<?php if ($page > 1): ?>
				<a href="?page=<?= $page - 1 ?>" class="prev">&lt; Prev</a>
			<?php endif; ?>

			<span>Page <?= $page ?> / <?= $totalPages ?></span>

			<?php if ($page < $totalPages): ?>
				<a href="?page=<?= $page + 1 ?>" class="next">Next &gt;</a>
			<?php endif; ?>
		</nav>
	<?php endif; ?>
</main>
<script>
	document.addEventListener('keydown', e => {
		const prev = document.querySelector('a.prev');
		const next = document.querySelector('a.next');

		if (['ArrowLeft', 'h'].includes(e.key) && prev) window.location = prev.href;
		if (['ArrowRight', 'l'].includes(e.key) && next) window.location = next.href;
	});
</script>
