<button id="theme-toggle" type="button" class="theme-toggle" aria-pressed="false">
    Theme
</button>
<script>
    const themeToggle = document.getElementById('theme-toggle');
    const root = document.documentElement;

    themeToggle.addEventListener('click', () => {
        const current = root.dataset.theme;
        const next = current === 'grayscale' ? '' : 'grayscale';

        if (next) {
            root.dataset.theme = next;
            themeToggle.setAttribute('aria-pressed', 'true');
        } else {
            delete root.dataset.theme;
            themeToggle.setAttribute('aria-pressed', 'false');
        }
    });
</script>
