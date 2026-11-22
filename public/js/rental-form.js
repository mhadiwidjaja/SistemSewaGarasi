/**
 * New rental form (S03).
 *
 * - Shows only the fields and garage types that apply to the chosen vehicle and rental type.
 * - Asks the server for a quote (end date, daily limit, available garages and their fees) whenever
 *   an input changes, so all dates and prices are calculated in one place: the server.
 *   The end date already shows before the vehicle and garage type are chosen.
 * - Marks a filled-in field that the server rejects, with the message under it.
 * The vehicle rules come from the server through the form's data attributes.
 * Requires live-quote.js.
 */
(() => {
    const form = document.getElementById('rentalForm');
    const vehicleRules = JSON.parse(form.dataset.vehicleRules);
    const garageOptions = document.getElementById('garageOptions');
    const garageMessage = document.getElementById('garageMessage');
    const garageListLabel = document.getElementById('garageListLabel');
    const { toggleFields, showMaxDays, clearFieldErrors, showFieldError } = window.LiveQuote;

    let selectedGarageId = form.dataset.oldGarageId;

    const checkedValue = (name) => form.querySelector(`input[name="${name}"]:checked`)?.value;
    const sendQuoteRequest = window.LiveQuote.requester(form, form.dataset.quoteUrl, {
        onQuote(quote, isValid) {
            clearFieldErrors(form);
            showMaxDays(quote.max_days);
            if (isValid) {
                showQuote(quote);
            } else if (showFieldError(form, quote.field, quote.message)) {
                showQuoteMessage('Perbaiki isian yang ditandai untuk melihat garasi yang tersedia.');
            } else {
                showQuoteMessage(quote.message);
            }
        },
        onFailure() {
            showQuoteMessage('Gagal memuat garasi tersedia. Periksa koneksi lalu coba lagi.');
        },
    });

    /** Show the fields and garage types that fit the chosen vehicle; garage types stay hidden until one is chosen. */
    function updateVehicleFields() {
        const rules = vehicleRules[checkedValue('vehicle_type')];
        document.getElementById('garageTypeField').hidden = !rules;
        if (!rules) {
            return;
        }

        toggleFields(document.getElementById('plateNumberField'), rules.requiresPlateNumber);
        toggleFields(document.getElementById('batteryFields'), rules.isElectric);

        form.querySelectorAll('input[name="garage_type"]').forEach((radio) => {
            const allowed = rules.garageTypes.includes(radio.value);
            radio.closest('.form-check').hidden = !allowed;
            radio.disabled = !allowed;
            if (!allowed) {
                radio.checked = false;
            }
        });

        if (rules.garageTypes.length === 1) {
            form.querySelector(`input[name="garage_type"][value="${rules.garageTypes[0]}"]`).checked = true;
        }
    }

    function updateRentalTypeFields() {
        toggleFields(document.getElementById('daysField'), checkedValue('rental_type') === form.dataset.dailyType);
    }

    /** Show a message instead of the quote, e.g. a validation error, and clear the end date. */
    function showQuoteMessage(message) {
        document.getElementById('endDate').textContent = '–';
        showGarageMessage(message);
    }

    /** Show a message in place of the garage list. */
    function showGarageMessage(message) {
        garageListLabel.textContent = 'Garasi Tersedia';
        garageOptions.replaceChildren();
        garageMessage.textContent = message;
        garageMessage.hidden = false;
        showFees(null);
    }

    /** Show the end date and the available garages, keeping the chosen garage selected. */
    function showQuote(quote) {
        document.getElementById('endDate').textContent = quote.end_date;

        if (quote.garages === null) {
            showGarageMessage('Pilih jenis kendaraan dan jenis garasi untuk melihat garasi yang tersedia.');
            return;
        }
        if (quote.garages.length === 0) {
            showGarageMessage('Tidak ada garasi tersedia untuk periode ini.');
            return;
        }

        garageMessage.hidden = true;
        garageListLabel.textContent =
            `Garasi Tersedia (${quote.garages.length}) untuk ${quote.start_date} – ${quote.end_date}`;
        garageOptions.replaceChildren(...quote.garages.map(garageOption));
        showFees(quote.garages.find((garage) => String(garage.id) === String(selectedGarageId)) ?? null);
    }

    /** Build one selectable garage row: code, daily price and total for this rental. */
    function garageOption(garage) {
        const label = document.createElement('label');
        label.className = 'list-group-item list-group-item-action d-flex align-items-center gap-3';

        const radio = document.createElement('input');
        radio.type = 'radio';
        radio.name = 'garage_id';
        radio.value = garage.id;
        radio.className = 'form-check-input mt-0';
        radio.checked = String(garage.id) === String(selectedGarageId);
        radio.addEventListener('change', () => {
            selectedGarageId = garage.id;
            showFees(garage);
        });

        const code = document.createElement('span');
        code.className = 'fw-semibold';
        code.textContent = garage.code;

        const price = document.createElement('span');
        price.className = 'text-body-secondary small';
        price.textContent = garage.daily_price;

        const total = document.createElement('span');
        total.className = 'ms-auto fw-semibold';
        total.textContent = garage.total_fee;

        label.append(radio, code, price, total);
        return label;
    }

    /** Show the fee breakdown of the chosen garage, or the hint when none is chosen. */
    function showFees(garage) {
        document.getElementById('paymentHint').hidden = garage !== null;
        document.getElementById('feeSummary').hidden = garage === null;
        if (garage === null) {
            return;
        }

        document.getElementById('rentalFee').textContent = garage.rental_fee;
        document.getElementById('chargingFee').textContent = garage.charging_fee ?? '';
        document.querySelectorAll('[data-charging-row]').forEach((row) => { row.hidden = garage.charging_fee === null; });
        document.getElementById('totalFee').textContent = garage.total_fee;
    }

    form.addEventListener('change', (event) => {
        if (event.target.name === 'vehicle_type') {
            updateVehicleFields();
        }
        if (event.target.name === 'rental_type') {
            updateRentalTypeFields();
        }
        if (event.target.name !== 'garage_id' && event.target.type !== 'file') {
            sendQuoteRequest();
        }
    });
    form.addEventListener('input', (event) => {
        if (event.target.type === 'number' || event.target.type === 'date') {
            sendQuoteRequest();
        }
    });

    updateVehicleFields();
    updateRentalTypeFields();
    sendQuoteRequest();
})();
