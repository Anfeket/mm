<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="/css/styles.css">
	<title><?= $title ?? 'mm' ?></title>
	<?php if (isset($post)): ?>
		<meta property="og:site_name" content="mm">
		<meta property="og:type" content="website">
		<meta property="og:title" content="Post #<?= $post['id'] ?>">
		<meta property="og:url" content="https://mm.svidnik.org/post/<?= $post['id'] ?>">
		<?php if ($post['post_type'] === 'image'): ?>
			<meta property="og:image" content="https://mm.svidnik.org<?= htmlspecialchars($post['thumb_path']) ?>">
		<?php elseif ($post['post_type'] === 'video'): ?>
			<meta property="og:video" content="https://mm.svidnik.org<?= htmlspecialchars($post['file_path']) ?>">
			<meta property="og:video:secure_url" content="https://mm.svidnik.org<?= htmlspecialchars($post['file_path']) ?>">
			<meta property="og:image" content="https://mm.svidnik.org<?= htmlspecialchars($post['thumb_path']) ?>">
		<?php endif ?>
		<meta property="og:description" content="<?= $post['description'] ?>">

		<meta property="twitter:url" content="https://mm.svidnik.org/post/<?= $post['id'] ?>">
		<meta property="twitter:title" content="Post #<?= $post['id'] ?>">
		<meta property="twitter:description" content="<?= $post['description'] ?>">
		<?php if ($post['post_type'] === 'image'): ?>
			<meta property="twitter:card" content="summary_large_image">
			<meta property="twitter:image" content="https://mm.svidnik.org<?= htmlspecialchars($post['thumb_path']) ?>">
		<?php elseif ($post['post_type'] === 'video'): ?>
			<meta property="twitter:card" content="player">
			<meta property="twitter:player:stream" content="https://mm.svidnik.org<?= htmlspecialchars($post['thumb_path']) ?>">
			<meta property="twitter:image" content="0">
		<?php endif ?>
	<?php endif ?>
</head>

<body>
