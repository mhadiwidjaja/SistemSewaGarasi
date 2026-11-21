/**
 * Shared confirmation pop-up (resources/views/components/confirm-modal.blade.php).
 *
 * A submit button with data-confirm="<question>" opens the pop-up instead of submitting.
 * Its form is submitted only when the user clicks the action button.
 * Optional: data-confirm-label (action text) and data-confirm-variant (Bootstrap colour, e.g. "danger").
 */
(() => {
    const modalElement = document.getElementById('confirmModal');
    const modal = new bootstrap.Modal(modalElement);
    const message = document.getElementById('confirmModalMessage');
    const actionButton = document.getElementById('confirmModalAction');
    let formToSubmit = null;

    document.querySelectorAll('[data-confirm]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            formToSubmit = button.form;
            message.textContent = button.dataset.confirm;
            actionButton.textContent = button.dataset.confirmLabel ?? 'Ya';
            actionButton.className = `btn btn-${button.dataset.confirmVariant ?? 'primary'}`;
            actionButton.disabled = false;
            modal.show();
        });
    });

    actionButton.addEventListener('click', () => {
        actionButton.disabled = true;
        formToSubmit.submit();
    });
})();
