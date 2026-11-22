/**
 * Behaviour shared by every form:
 * - After a form is submitted, its submit buttons are disabled, so a double-click cannot save twice.
 * - A file input with data-max-megabytes rejects a larger file right away with a message in Indonesian,
 *   before it is uploaded (the server would otherwise refuse the whole request).
 */
(() => {
    const BYTES_PER_MEGABYTE = 1024 * 1024;

    document.addEventListener('submit', (event) => {
        event.target.querySelectorAll('[type="submit"]').forEach((button) => { button.disabled = true; });
    });

    // Re-enable the buttons when the browser shows the page again from its back/forward cache.
    window.addEventListener('pageshow', () => {
        document.querySelectorAll('form [type="submit"]').forEach((button) => { button.disabled = false; });
    });

    document.querySelectorAll('input[type="file"][data-max-megabytes]').forEach((input) => {
        input.addEventListener('change', () => {
            const maxMegabytes = Number(input.dataset.maxMegabytes);
            const tooLarge = input.files.length > 0 && input.files[0].size > maxMegabytes * BYTES_PER_MEGABYTE;

            input.setCustomValidity(tooLarge ? `Ukuran file maksimal ${maxMegabytes} MB.` : '');
            input.classList.toggle('is-invalid', tooLarge);
            if (tooLarge) {
                input.reportValidity();
                input.value = '';
            }
        });
    });
})();
