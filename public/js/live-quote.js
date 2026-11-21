/**
 * Shared helpers for forms that show a live quote calculated by the server
 * (the renter's rental form and the admin's extension form).
 */
window.LiveQuote = (() => {
    const DELAY_MS = 250;

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

    return { toggleFields, requester, showMaxDays };
})();
