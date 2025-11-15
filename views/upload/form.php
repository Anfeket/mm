<main id="upload-form">
	<h2>Upload a post</h2>
	<form action="/upload" method="post" enctype="multipart/form-data" autocomplete="off">
		<label>
			File:
			<input type="file" name="file" id="fileInput" required accept="image/*,video/*">
		</label>
		<div id="preview"></div>
		<label>
			Artist:
			<input type="text" name="artist">
		</label>
		<label>
			Copyrights:
			<input type="text" name="copyright">
		</label>
		<label>
			Tags (separate with spaces):
			<input type="text" name="general">
		</label>
		<label id="description">
			Description:
			<textarea name="description"></textarea>
		</label>
		<button class="button" type="submit">Upload</button>
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

	function setupAutocomplete(inputName) {
		const input = document.querySelector(`input[name="${inputName}"]`);
		const suggestionBox = document.createElement("div");
		suggestionBox.className = "autocomplete-list";
		input.parentNode.style.position = "relative";
		input.parentNode.appendChild(suggestionBox);

		let debounce;

		input.addEventListener("focus", () => {
			input.dataset.active = "1";
		});
		input.addEventListener("blur", () => {
			setTimeout(() => {
				suggestionBox.innerHTML = "";
				input.dataset.active = "0";
			}, 150);
		});

		input.addEventListener("input", () => {
			let selectedIndex = -1;

			input.addEventListener("keydown", (e) => {
				const items = suggestionBox.querySelectorAll("div");
				if (!items.length) return;

				switch (e.key) {
					case "ArrowDown":
						e.preventDefault();
						selectedIndex = (selectedIndex + 1) % items.length;
						updateHighlight(items, selectedIndex);
						break;
					case "ArrowUp":
						e.preventDefault();
						selectedIndex = (selectedIndex - 1 + items.length) % items.length;
						updateHighlight(items, selectedIndex);
						break;
					case "Tab":
					case "Enter":
						if (selectedIndex >= 0) {
							e.preventDefault();
							selectTag(items[selectedIndex].dataset.tag);
						}
						break;
					default:
						selectedIndex = -1;
				}
			});

			function updateHighlight(items, index) {
				items.forEach((el, i) => el.classList.toggle("active", i === index));
			}

			function selectTag(tagName) {
				const parts = input.value.trim().split(/\s+/);
				parts[parts.length - 1] = tagName;
				input.value = parts.join(" ") + " ";
				suggestionBox.innerHTML = "";
			}
			if (input.dataset.active !== "1") return; // only trigger for focused input
			clearTimeout(debounce);

			const value = input.value.trim();
			if (!value) {
				suggestionBox.innerHTML = "";
				return;
			}

			// get only the last word for multi-tag input
			const parts = value.split(/\s+/);
			const query = parts[parts.length - 1];

			if (!query) return;

			debounce = setTimeout(async () => {
				const url = new URL("/tags", window.location.origin);
				url.searchParams.set("search", query);
				if (inputName) url.searchParams.set("category", inputName);
				const res = await fetch(url);

				const tags = await res.json();
				suggestionBox.innerHTML = "";

				for (const tag of tags) {
					const item = document.createElement("div");
					item.dataset.tag = tag.name;
					item.innerHTML = `
		<span class="tag tag-${tag.category}">${tag.name}</span>
		<span class="tag tag-${tag.category}">(${tag.category})</span>
		<span class="tag">${tag.post_count}</span>
	`;
					item.addEventListener("click", () => {
						selectTag(tag.name);
					});
					suggestionBox.appendChild(item);
				}
			}, 200);
		});
	}

	["general", "artist", "copyright"].forEach(setupAutocomplete);
</script>
