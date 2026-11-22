/**
 * Shared helpers for forms that show a live quote calculated by the server
 * (the renter's rental form and the admin's extension form).
 */
window.LiveQuote = (() => {
    const DELAY_MS = 250;
    const HTTP_UNAUTHORIZED = 401;
    const SESSION_EXPIRED_MESSAGE = 'Sesi Anda telah berakhir. Silakan muat ulang halaman dan masuk kembali.';

    /** Show or hide a block of fields; hidden fields are disabled so they are not submitted. */
    function toggleFields(container, visible) {
        container.hidden = !visible;
        container.querySelectorAll('input').forEach((input) => { input.disabled = !visible; });
    }

    /** The form's text fields as URL parameters, without the CSRF token, method override or files. */
    function textFields(form) {
        const parameters = new URLSearchParams();
        new FormData(form).forEach((value, name) => {
            if (typeof value === 'string' && !name.startsWith('_')) {
                parameters.append(name, value);
            }
        });
        return parameters;
    }

    /**
     * Create a function that, after a short pause in typing, sends the form's text fields to `url`
     * and passes the JSON answer to onQuote(quote, isValid). A request still running is cancelled.
     */
    function requester(form, url, { onQuote, onFailure }) {
        let timer = null;
        let controller = null;

        async function send() {
            controller?.abort();
            controller = new AbortController();
            try {
                const response = await fetch(`${url}?${textFields(form)}`, {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });
                if (response.status === HTTP_UNAUTHORIZED) {
                    onQuote({ message: SESSION_EXPIRED_MESSAGE }, false);
                    return;
                }
                onQuote(await response.json(), response.ok);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    onFailure();
                }
            }
        }

        return () => {
            clearTimeout(timer);
            timer = setTimeout(send, DELAY_MS);
        };
    }

    /** Show the daily limit from the server on the "days" input and its help text. */
    function showMaxDays(maxDays) {
        if (maxDays) {
            document.getElementById('days').max = maxDays;
            document.getElementById('days_help').textContent = `Maksimal ${maxDays} hari.`;
        }
    }

    /** Remove the error marks a previous quote put on fields. */
    function clearFieldErrors(form) {
        form.querySelectorAll('[data-quote-error]').forEach((feedback) => feedback.remove());
        form.querySelectorAll('[data-quote-invalid]').forEach((input) => {
            input.classList.remove('is-invalid');
            input.removeAttribute('data-quote-invalid');
        });
    }

    /**
     * Mark a field the user already filled in as invalid and show the message under it.
     * Returns false (nothing marked) for an empty or unknown field, so the caller shows the message elsewhere.
     */
    function showFieldError(form, field, message) {
        const input = form.elements[field];
        if (!(input instanceof HTMLInputElement) || input.value === '') {
            return false;
        }

        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.dataset.quoteError = '';
        feedback.textContent = message;

        input.classList.add('is-invalid');
        input.dataset.quoteInvalid = '';
        input.after(feedback);
        return true;
    }

    return { toggleFields, requester, showMaxDays, clearFieldErrors, showFieldError };
})();
