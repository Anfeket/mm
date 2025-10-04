<main id="upload-form">
	<h2>Upload a post</h2>
	<form action="/upload" method="post" enctype="multipart/form-data">
		<label>
			File:
			<input type="file" name="file" id="fileInput" required>
		</label>
		<div id="preview"></div>
		<label>
			Artist:
			<input type="text" name="artist">
		</label>
		<label>
			Copyrights:
			<input type="text" name="copyrights">
		</label>
		<label>
			Tags (separate with spaces):
			<input type="text" name="tags">
		</label>
		<label>
			Description:
			<textarea name="description"></textarea>
		</label>
		<button type="submit">Upload</button>
	</form>
</main>
<script>
	document.getElementById('fileInput').addEventListener('change', function(event) {
		const file = event.target.files[0];
		const preview = document.getElementById('preview');
		preview.innerHTML = "";

		if (!file) return;

		if (file.type.startsWith('image/')) {
			const img = document.createElement('img');
			img.style.maxWidth = "300px";
			img.style.marginTop = "10px";
			img.src = URL.createObjectURL(file);
			preview.appendChild(img);
		} else if (file.type.startsWith('video/')) {
			const video = document.createElement('video');
			video.controls = true;
			video.style.maxWidth = "300px";
			video.style.marginTop = "10px";
			video.src = URL.createObjectURL(file);
			preview.appendChild(video);
		}
	});
</script>
