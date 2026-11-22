{{--
    The proof of payment upload, shared by the rental and extension forms. The browser can't keep a
    chosen file after a failed submit, so the help text then asks the user to choose it again.
--}}
@use('App\Http\Requests\PaymentProofRules')

@php
    $limit = 'Gambar JPG atau PNG, maksimal '.PaymentProofRules::MAX_MEGABYTES.' MB.';
    $help = $errors->any() ? 'Pilih ulang file bukti pembayaran. '.$limit : $limit;
@endphp

<x-input name="payment_proof" label="Bukti Pembayaran" type="file" accept="image/png,image/jpeg"
         :data-max-megabytes="PaymentProofRules::MAX_MEGABYTES" :help="$help" required/>
