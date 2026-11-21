/**
 * Rental extension form (S07): shows the days field only for a daily extension and asks the server
 * for the new end date and the fee still to be paid whenever an input changes.
 * Requires live-quote.js.
 */
(() => {
    const form = document.getElementById('extensionForm');
    const { toggleFields, showMaxDays } = window.LiveQuote;
    const endDate = document.getElementById('endDate');
    const fee = document.getElementById('fee');
    const feeBreakdown = document.getElementById('feeBreakdown');

    const sendQuoteRequest = window.LiveQuote.requester(form, form.dataset.quoteUrl, {
        onQuote(quote, isValid) {
            showMaxDays(quote.max_days);
            endDate.textContent = isValid ? quote.end_date : '–';
            fee.textContent = isValid ? quote.fee : '–';
            feeBreakdown.textContent = isValid ? quote.fee_breakdown : quote.message;
        },
        onFailure() {
            feeBreakdown.textContent = 'Gagal menghitung biaya. Periksa koneksi lalu coba lagi.';
        },
    });

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
