/**
 * Batch garage form (S05): previews the codes that will be created and the monthly price,
 * and shows the electricity price field only for electric car garages.
 * All numbers and rules come from the server through the form's data attributes.
 */
(() => {
    const form = document.getElementById('garageForm');
    const nextNumbers = JSON.parse(form.dataset.nextNumbers);
    const remainingCodes = JSON.parse(form.dataset.remainingCodes);
    const codePrefixes = JSON.parse(form.dataset.codePrefixes);
    const codeDigits = Number(form.dataset.codeDigits);
    const monthlyMultiplier = Number(form.dataset.monthlyMultiplier);

    const quantityInput = form.elements.quantity;
    const dailyPriceInput = form.elements.daily_price;
    const chargingField = document.getElementById('chargingPriceField');
    const chargingInput = form.elements.charging_price_per_kwh;
    const rupiah = new Intl.NumberFormat('id-ID');

    const selectedType = () => form.querySelector('input[name="type"]:checked')?.value;
    const code = (type, number) => codePrefixes[type] + String(number).padStart(codeDigits, '0');

    function updateCodePreview(type) {
        const quantity = Number(quantityInput.value);
        const preview = document.getElementById('codePreview');

        if (!type || quantity < 1 || quantity > remainingCodes[type]) {
            preview.textContent = '–';
            return;
        }

        const first = nextNumbers[type];
        const last = first + quantity - 1;
        preview.textContent = quantity === 1 ? code(type, first) : `${code(type, first)} s.d. ${code(type, last)}`;
    }

    function updateMonthlyPrice() {
        const dailyPrice = Number(dailyPriceInput.value);
        document.getElementById('monthlyPricePreview').textContent =
            dailyPrice > 0 ? `Rp ${rupiah.format(dailyPrice * monthlyMultiplier)}` : '–';
    }

    function updateTypeFields(type) {
        const isElectric = type === form.dataset.electricType;
        chargingField.hidden = !isElectric;
        chargingInput.disabled = !isElectric;

        quantityInput.max = remainingCodes[type];
        document.getElementById('quantity_help').textContent = `Sisa kode tersedia: ${remainingCodes[type]}`;
    }

    function update() {
        const type = selectedType();
        updateTypeFields(type);
        updateCodePreview(type);
        updateMonthlyPrice();
    }

    form.addEventListener('input', update);
    update();
})();
