<main id="upload-form">
    <h2>Upload a post</h2>
    <form action="/upload" method="post" enctype="multipart/form-data">
        <label>
            File:
            <input type="file" name="file" required>
        </label>
        <label>
            Description:
            <textarea name="description"></textarea>
        </label>
        <button type="submit">Upload</button>
    </form>
</main>
