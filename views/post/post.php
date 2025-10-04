	<div id="main">
		<aside id="sidebar">
			<section id="tag-list">
				<h3>Artist</h3>
				<ul id="artist-tag-list">
					<?php foreach ($tags['artist'] as $tag): ?>
						<li><a href="/tag/<?= urlencode($tag) ?>" class="tag tag-artist"><?= htmlspecialchars($tag) ?></a></li>
					<?php endforeach; ?>
				</ul>

				<h3>Copyright</h3>
				<ul id="copyright-tag-list">
					<?php foreach ($tags['copyright'] as $tag): ?>
						<li><a href="/tag/<?= urlencode($tag) ?>" class="tag tag-copyright"><?= htmlspecialchars($tag) ?></a></li>
					<?php endforeach; ?>
				</ul>

				<h3>General</h3>
				<ul id="general-tag-list">
					<?php foreach ($tags['general'] as $tag): ?>
						<li><a href="/tag/<?= urlencode($tag) ?>" class="tag tag-general"><?= htmlspecialchars($tag) ?></a></li>
					<?php endforeach; ?>
				</ul>

				<h3>Meta</h3>
				<ul id="meta-tag-list">
					<?php foreach ($tags['meta'] as $tag): ?>
						<li><a href="/tag/<?= urlencode($tag) ?>" class="tag tag-meta"><?= htmlspecialchars($tag) ?></a></li>
					<?php endforeach; ?>
				</ul>
			</section>
			<section id="post-information">
				<h3>Information</h3>
				<dl>
					<dt>ID:</dt>
					<dd><?= $post['id'] ?></dd>
					<dt>Uploader:</dt>
					<dd><?= htmlspecialchars($uploader['username']) ?></dd>
					<dt>Size:</dt>
					<dd><?= $post['file_size_human'] ?></dd>
					<dt>Posted:</dt>
					<dd><?= $post['created_at'] ?></dd>
					<dt>Source:</dt>
					<dd><?= $post['source'] ? htmlspecialchars($post['source']) : 'N/A' ?></dd>
					<dt>Score:</dt>
					<dd><?= $post['score'] ?></dd>
					<dt>Favorites</dt>
					<dd><?= $post['favorites_count'] ?></dd>
				</dl>
			</section>
		</aside>
		<main id="mainContent">
			<article>
				<div id="post-image">
					<?php if ($post['post_type'] === 'image'): ?>
						<img src="<?= htmlspecialchars($post['file_path']) ?>" alt="Post #<?= $post['id'] ?>">
					<?php elseif ($post['post_type'] === 'video'): ?>
						<video src="<?= htmlspecialchars($post['file_path']) ?>" alt="Post #<?= $post['id'] ?>" controls>
					<?php endif ?>
				</div>
				<div id="post-description">
					<h3>Description</h3>
					<?= $post['description'] ?>
				</div>
				<div id="post-comments">
					<h3>Comments</h3>
				</div>
			</article>
		</main>
	</div>
