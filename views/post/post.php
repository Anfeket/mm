	<div id="main">
		<aside id="sidebar">
			<section id="tag-list">
				<h3>Artist</h3>
				<ul id="artist-tag-list"></ul>
				<h3>Copyright</h3>
				<ul id="copyright-tag-list"></ul>
				<h3>Genre</h3>
				<ul id="genre-tag-list"></ul>
				<h3>General</h3>
				<ul id="general-tag-list"></ul>
				<h3>Meta</h3>
				<ul id="meta-tag-list"></ul>
			</section>
			<section id="post-information">
				<h3>Information</h3>
				<dl>
					<dt>ID:</dt>
					<dd><?= $post['id'] ?></dd>
					<dt>Uploader:</dt>
					<dd><?= $post['author_id'] ?></dd>
					<dt>Size:</dt>
					<dd><?= $post['file_size_human'] ?></dd>
					<dt>Posted:</dt>
					<dd><?= $post['created_at'] ?></dd>
					<dt>Source:</dt>
					<dd>xxxxxx</dd>
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
				<img src="<?= htmlspecialchars($post['file_path']) ?>" alt="Post #<?= $post['id'] ?>">
				</div>
				<div id="post-description">
					<h3>Description</h3>
				</div>
				<div id="post-comments">
					<h3>Comments</h3>
				</div>
			</article>
		</main>
	</div>
