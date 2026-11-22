@extends('layouts.app')

@use('App\Enums\GarageType')

@section('title', 'Buat Garasi')

@section('content')
    @php
        $selectedType = (GarageType::tryFrom((string) old('type')) ?? GarageType::Bicycle)->value;
        $isElectric = $selectedType === GarageType::ElectricCar->value;
    @endphp

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <h1 class="h3 mb-4">Buat Garasi Baru</h1>

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.garages.store') }}" id="garageForm" novalidate
                          data-next-numbers='@json($nextNumbers)'
                          data-remaining-codes='@json($remainingCodes)'
                          data-code-prefixes='@json($codePrefixes)'
                          data-code-digits="{{ $codeDigits }}"
                          data-monthly-multiplier="{{ $monthlyMultiplier }}"
                          data-electric-type="{{ GarageType::ElectricCar->value }}">
                        @csrf

                        <x-radio-group name="type" label="Jenis Garasi" :options="GarageType::cases()"
                                       :selected="$selectedType"/>

                        <x-input name="quantity" label="Jumlah Garasi" type="number" min="1"
                                 :max="$remainingCodes[$selectedType]"
                                 help="Sisa kode tersedia: {{ $remainingCodes[$selectedType] }}" required/>

                        <div class="mb-3">
                            <span class="form-label d-block">Kode Garasi</span>
                            <span class="fw-semibold" id="codePreview">–</span>
                        </div>

                        <x-input name="daily_price" label="Harga Sewa Harian (Rp)" type="number" min="1" required/>

                        <div class="mb-3">
                            <span class="form-label d-block">Harga Sewa Bulanan</span>
                            <span class="fw-semibold" id="monthlyPricePreview">–</span>
                            <div class="form-text">{{ $monthlyMultiplier }} × harga sewa harian.</div>
                        </div>

                        <div id="chargingPriceField" @if (! $isElectric) hidden @endif>
                            <x-input name="charging_price_per_kwh" label="Harga Pengisian Daya per kWh (Rp)" type="number"
                                     min="1" :disabled="! $isElectric" required/>
                        </div>

                        <div class="d-grid gap-2 d-sm-flex justify-content-sm-end mt-4">
                            <a href="{{ route('admin.garages.index') }}" class="btn btn-outline-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary px-4">Buat Garasi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/garage-form.js') }}"></script>
@endpush
