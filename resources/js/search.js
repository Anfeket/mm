(function () {
    'use strict';

    const DEBOUNCE_MS = 100;
    const MIN_LENGTH = 1;
    const MAX_RESULTS = 10;
    const AUTOCOMPLETE_URL = '/tags/autocomplete';

    // Maps both short (a) and long (artist) prefix strings to their canonical short form
    const PREFIX_MAP = {
        a: 'a', artist: 'a',
        c: 'c', copyright: 'c',
        o: 'o', origin: 'o',
        t: 't', template: 't',
        g: 'g', general: 'g',
        m: 'm', meta: 'm',
        l: 'l', language: 'l',
    };

    // Maps full category name → short prefix
    const CATEGORY_TO_PREFIX = {
        artist: 'a', copyright: 'c', origin: 'o', template: 't',
        general: 'g', meta: 'm', language: 'l',
    };

    const CATEGORY_CLASSES = {
        artist: 'tag-artist',
        copyright: 'tag-copyright',
        origin: 'tag-origin',
        template: 'tag-template',
        general: 'tag-general',
        meta: 'tag-meta',
        language: 'tag-language',
    };

    function lastToken(str) {
        const parts = str.trimStart().split(/\s+/);
        return parts[parts.length - 1] ?? '';
    }

    function replaceLastToken(str, replacement) {
        const trimmed = str.trimStart();
        if (!trimmed) return replacement + ' ';
        const parts = trimmed.split(/\s+/);
        parts[parts.length - 1] = replacement;
        return parts.join(' ') + ' ';
    }

    function createDropdown() {
        const el = document.createElement('ul');
        el.classList.add('autocomplete-dropdown', 'hidden');
        el.setAttribute('role', 'listbox');
        return el;
    }

    document.querySelectorAll('input[data-autocomplete]').forEach(initAutocomplete);

    function initAutocomplete(input) {
        let suggestions = [];
        let selectedIndex = -1;
        let debounceTimer = null;
        let currentRequest = null;

        const prefix = input.dataset.autocompletePrefix ?? null;

        const dropdown = createDropdown();
        document.body.appendChild(dropdown);

        const dropdownId = `autocomplete-${input.name || input.id || Math.random().toString(36).slice(2)}`;
        dropdown.id = dropdownId;
        input.setAttribute('aria-controls', dropdownId);
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-expanded', 'false');

        function fetchSuggestions(query) {
            if (!query || query.trim().length < MIN_LENGTH) {
                suggestions = [];
                selectedIndex = -1;
                renderDropdown();
                return;
            }

            query = prefix ? `${prefix}:${query}` : query;

            if (currentRequest) {
                currentRequest.abort();
            }

            const url = new URL(AUTOCOMPLETE_URL, window.location.origin);
            url.searchParams.set('q', query);

            currentRequest = new AbortController();
            fetch(url, { signal: currentRequest.signal })
                .then(response => response.json())
                .then(data => {
                    suggestions = data.slice(0, MAX_RESULTS);
                    selectedIndex = -1;
                    renderDropdown();
                })
                .catch(err => {
                    if (err.name !== 'AbortError') {
                        console.error('Autocomplete error:', err);
                    }
                })
                .finally(() => {
                    currentRequest = null;
                });
        }

        function renderDropdown() {
            dropdown.innerHTML = '';
            if (suggestions.length === 0) {
                hide();
                return;
            }

            suggestions.forEach((tag, index) => {
                const li = document.createElement('li');
                li.id = `autocomplete-item-${index}`;
                li.className = 'autocomplete-item';
                li.setAttribute('role', 'option');
                li.dataset.index = index;

                const count = document.createElement('span');
                count.textContent = tag.post_count;
                count.classList.add('autocomplete-count');
                li.appendChild(count);

                const name = document.createElement('span');
                name.textContent = tag.name;
                const category = tag.category ?? null;
                if (category && CATEGORY_CLASSES[category]) {
                    name.classList.add(CATEGORY_CLASSES[category]);
                }
                li.appendChild(name);

                if (tag.alias_of) {
                    const alias = document.createElement('span');
                    alias.textContent = `→ ${tag.alias_of}`;
                    alias.classList.add('autocomplete-alias');
                    li.appendChild(alias);
                }

                li.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    confirm(index);
                });

                dropdown.appendChild(li);
            });

            show();
            setSelected(selectedIndex);
        }

        function extractPrefix(token) {
            const match = token.match(/^([a-z]+):/i);
            if (!match) return null;
            const resolved = PREFIX_MAP[match[1].toLowerCase()];
            return resolved ?? null;
        }

        function setSelected(index) {
            dropdown.querySelectorAll('.autocomplete-item').forEach((item, i) => {
                item.classList.toggle('autocomplete-item-selected', i === index);
                if (i === index) {
                    item.setAttribute('aria-selected', 'true');
                    input.setAttribute('aria-activedescendant', item.id);
                } else {
                    item.removeAttribute('aria-selected');
                }
            });
        }

        function confirm(index) {
            const tag = suggestions[index];
            if (!tag) return;

            const token = lastToken(input.value);
            let name = tag.name;

            if (tag.alias_of) {
                name = tag.alias_of;
            }

            if (!prefix) {
                const userPrefix = extractPrefix(token);
                if (userPrefix) {
                    // User already typed a category prefix — keep it
                    name = `${userPrefix}:${name}`;
                } else {
                    // No prefix typed, no default on the input.
                    // Prepend the tag's own short prefix unless it's general (g:).
                    const tagCategory = tag.category ?? 'general';
                    if (tagCategory !== 'general') {
                        const shortPrefix = CATEGORY_TO_PREFIX[tagCategory];
                        if (shortPrefix) {
                            name = `${shortPrefix}:${name}`;
                        }
                    }
                }
            }

            input.value = replaceLastToken(input.value, name);
            hide();
            input.focus();
        }

        function show() {
            const rect = input.getBoundingClientRect();
            dropdown.style.top = `${rect.bottom + window.scrollY}px`;
            dropdown.style.left = `${rect.left + window.scrollX}px`;
            dropdown.style.width = `${rect.width}px`;
            dropdown.classList.remove('hidden');
            input.setAttribute('aria-expanded', 'true');
        }

        function hide() {
            dropdown.classList.add('hidden');
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            selectedIndex = -1;
        }

        function onScroll() {
            if (!dropdown.classList.contains('hidden')) {
                show();
            }
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });

        input.addEventListener('input', () => {
            const query = lastToken(input.value);
            clearTimeout(debounceTimer);
            if (query.length < MIN_LENGTH) {
                hide();
                return;
            }
            debounceTimer = setTimeout(() => fetchSuggestions(query), DEBOUNCE_MS);
        });

        input.addEventListener('keydown', (e) => {
            if (dropdown.classList.contains('hidden')) return;

            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    selectedIndex = (selectedIndex + 1) % suggestions.length;
                    setSelected(selectedIndex);
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    selectedIndex = (selectedIndex - 1 + suggestions.length) % suggestions.length;
                    setSelected(selectedIndex);
                    break;
                case 'Enter':
                    if (selectedIndex >= 0) {
                        e.preventDefault();
                        confirm(selectedIndex);
                    }
                    break;
                case 'Tab':
                    if (suggestions.length > 0) {
                        e.preventDefault();
                        confirm(selectedIndex >= 0 ? selectedIndex : 0);
                    }
                    break;
                case 'Escape':
                    e.preventDefault();
                    hide();
                    break;
            }
        });

        input.addEventListener('blur', () => {
            setTimeout(hide, 100);
        });
    }
})();
