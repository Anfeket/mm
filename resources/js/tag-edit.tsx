(function () {
    'use strict';

    const tagEditButton = document.getElementById('tag-edit-btn')
    const tagList = document.getElementById('tag-list')

    if (tagEditButton) {
        tagEditButton.addEventListener('click', () => {
            if (tagList) {
                if (tagList.classList.contains('tag-editing')) {
                    tagList.classList.remove('tag-editing')
                } else {
                    tagList.classList.add('tag-editing')
                }
            }
        })
    }
})();
