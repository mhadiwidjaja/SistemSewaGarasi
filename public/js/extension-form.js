/**
 * Rental extension form (S07): shows the days field only for a daily extension and asks the server
 * for the new end date and the fee still to be paid whenever an input changes. A rejected number
 * of days is marked on the field itself.
 * Requires live-quote.js.
 */
(() => {
    const form = document.getElementById('extensionForm');
    const { toggleFields, showMaxDays, clearFieldErrors, showFieldError } = window.LiveQuote;
    const endDate = document.getElementById('endDate');
    const fee = document.getElementById('fee');
    const feeBreakdown = document.getElementById('feeBreakdown');

    const sendQuoteRequest = window.LiveQuote.requester(form, form.dataset.quoteUrl, {
        onQuote(quote, isValid) {
            clearFieldErrors(form);
            showMaxDays(quote.max_days);
            if (isValid) {
                showResult(quote.end_date, quote.fee, quote.fee_breakdown);
            } else {
                showFieldError(form, quote.field, quote.message);
                showResult('–', '–', quote.message);
            }
        },
        onFailure() {
            showResult('–', '–', 'Gagal menghitung biaya. Periksa koneksi lalu coba lagi.');
        },
    });

    /** Show the new end date, the remaining fee and how it is computed (or why it can't be). */
    function showResult(newEndDate, remainingFee, explanation) {
        endDate.textContent = newEndDate;
        fee.textContent = remainingFee;
        feeBreakdown.textContent = explanation;
    }

    function updateRentalTypeFields() {
        const isDaily = form.querySelector('input[name="rental_type"]:checked')?.value === form.dataset.dailyType;
        toggleFields(document.getElementById('daysField'), isDaily);
    }

    form.addEventListener('change', (event) => {
        if (event.target.name === 'rental_type') {
            updateRentalTypeFields();
        }
        if (event.target.type !== 'file') {
            sendQuoteRequest();
        }
    });
    form.addEventListener('input', (event) => {
        if (event.target.type === 'number') {
            sendQuoteRequest();
        }
    });

    updateRentalTypeFields();
    sendQuoteRequest();
})();
