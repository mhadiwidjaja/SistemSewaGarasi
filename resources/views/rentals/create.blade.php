@extends('layouts.app')

@use('App\Enums\RentalType')
@use('App\Enums\VehicleType')

@section('title', 'Sewa Garasi Baru')

@section('content')
    @php
        $oldVehicle = VehicleType::tryFrom((string) old('vehicle_type'));
        $isDaily = old('rental_type', RentalType::Daily->value) === RentalType::Daily->value;
        $showPlate = $oldVehicle?->requiresPlateNumber() ?? true;
        $showBattery = $oldVehicle?->isElectric() ?? false;
        $labels = fn (array $cases) => collect($cases)->mapWithKeys(fn ($case) => [$case->value => $case->label()]);
    @endphp

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="h3 mb-4">Sewa Garasi Baru</h1>

            <form method="POST" action="{{ route('rentals.store') }}" enctype="multipart/form-data" id="rentalForm" novalidate
                  data-quote-url="{{ route('rentals.quote') }}"
                  data-vehicle-rules='@json($vehicleRules)'
                  data-daily-type="{{ RentalType::Daily->value }}"
                  data-old-garage-id="{{ old('garage_id') }}">
                @csrf

                <section class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">1. Data Kendaraan</h2>
                        <x-radio-group name="vehicle_type" label="Jenis Kendaraan" :options="$labels($vehicleTypes)"/>
                        <div id="plateNumberField" @if (! $showPlate) hidden @endif>
                            <x-input name="plate_number" label="Nomor Polisi" placeholder="B 1234 XYZ"
                                     :disabled="! $showPlate" required/>
                        </div>
                        <x-input name="vehicle_brand_type" label="Merek & Tipe" placeholder="Honda Beat" required/>
                        <div id="batteryFields" class="row" @if (! $showBattery) hidden @endif>
                            <div class="col-sm-6">
                                <x-input name="battery_capacity_kwh" label="Kapasitas Baterai (kWh)" type="number"
                                         step="0.01" min="0" :disabled="! $showBattery" required/>
                            </div>
                            <div class="col-sm-6">
                                <x-input name="battery_left_kwh" label="Sisa Baterai (kWh)" type="number"
                                         step="0.01" min="0" :disabled="! $showBattery" required/>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">2. Data Sewa</h2>
                        <x-radio-group name="rental_type" label="Jenis Sewa" :options="$labels($rentalTypes)"
                                       :selected="RentalType::Daily->value"/>
                        <div class="row">
                            <div class="col-sm-6" id="daysField" @if (! $isDaily) hidden @endif>
                                <x-input name="days" label="Jumlah Hari" type="number" min="1" value="1"
                                         help="Maksimal {{ $maxDailyDays }} hari." :disabled="! $isDaily" required/>
                            </div>
                            <div class="col-sm-6">
                                <x-input name="start_date" label="Tanggal Mulai" type="date" :min="$today"
                                         :value="$today" required/>
                            </div>
                        </div>
                        <div>
                            <span class="form-label d-block">Tanggal Selesai</span>
                            <span class="fw-semibold" id="endDate">–</span>
                            <div class="form-text">Dihitung otomatis.</div>
                        </div>
                    </div>
                </section>

                <section class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">3. Pilih Garasi</h2>
                        <x-radio-group name="garage_type" label="Jenis Garasi" :options="$labels($garageTypes)"/>
                        <span class="form-label d-block">Garasi Tersedia</span>
                        <p class="text-body-secondary mb-0" id="garageMessage">
                            Pilih jenis kendaraan dan jenis garasi untuk melihat garasi yang tersedia.
                        </p>
                        <div class="list-group" id="garageOptions"></div>
                        @error('garage_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </section>

                <section class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">4. Pembayaran</h2>
                        <p class="text-body-secondary" id="paymentHint">Pilih garasi untuk melihat total biaya.</p>
                        <dl class="row mb-3" id="feeSummary" hidden>
                            <dt class="col-7 fw-normal">Biaya Sewa</dt>
                            <dd class="col-5 text-end mb-1" id="rentalFee"></dd>
                            <dt class="col-7 fw-normal" data-charging-row>Biaya Pengisian Daya</dt>
                            <dd class="col-5 text-end mb-1" id="chargingFee" data-charging-row></dd>
                            <dt class="col-7 fs-5 border-top pt-2">Total Biaya</dt>
                            <dd class="col-5 text-end fs-5 fw-bold border-top pt-2 mb-0" id="totalFee"></dd>
                        </dl>
                        <x-input name="payment_proof" label="Bukti Pembayaran" type="file" accept="image/png,image/jpeg"
                                 help="Gambar JPG atau PNG, maksimal 2 MB." required/>

                        <button type="submit" class="btn btn-primary btn-lg w-100 mt-2">Simpan Sewa</button>
                    </div>
                </section>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/live-quote.js') }}"></script>
    <script src="{{ asset('js/rental-form.js') }}"></script>
@endpush
