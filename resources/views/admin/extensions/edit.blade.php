@extends('layouts.app')

@use('App\Enums\RentalType')
@use('App\Support\Format')

@section('title', 'Perpanjang Sewa '.$rental->garage->code)

@section('content')
    @php
        $isDaily = old('rental_type', RentalType::Daily->value) === RentalType::Daily->value;
    @endphp

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="h3 mb-4">Perpanjang Sewa Garasi {{ $rental->garage->code }}</h1>

            <section class="card shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Detail Sewa</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-4 fw-normal text-body-secondary">Garasi</dt>
                        <dd class="col-sm-8">{{ $rental->garage->code }} · {{ $rental->garage->type->label() }}</dd>
                        <dt class="col-sm-4 fw-normal text-body-secondary">Penyewa</dt>
                        <dd class="col-sm-8">{{ $rental->renter->name }}</dd>
                        <dt class="col-sm-4 fw-normal text-body-secondary">Kendaraan</dt>
                        <dd class="col-sm-8">
                            {{ $rental->vehicle_brand_type }} · {{ $rental->vehicle_type->label() }}{{ $rental->plate_number ? ' · '.$rental->plate_number : '' }}
                        </dd>
                        <dt class="col-sm-4 fw-normal text-body-secondary">Jenis Sewa</dt>
                        <dd class="col-sm-8">{{ $rental->rental_type->label() }}</dd>
                        <dt class="col-sm-4 fw-normal text-body-secondary">Periode</dt>
                        <dd class="col-sm-8">{{ Format::date($rental->start_date) }} – {{ Format::date($rental->end_date) }}</dd>
                        <dt class="col-sm-4 fw-normal text-body-secondary">Harga Harian</dt>
                        <dd class="col-sm-8 mb-0">{{ Format::rupiah($rental->garage->daily_price) }}</dd>
                    </dl>
                </div>
            </section>

            <form method="POST" action="{{ route('admin.extensions.update', $rental) }}" enctype="multipart/form-data"
                  id="extensionForm" novalidate
                  data-quote-url="{{ route('admin.extensions.quote', $rental) }}"
                  data-daily-type="{{ RentalType::Daily->value }}">
                @csrf
                @method('PUT')

                <section class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Perpanjangan</h2>

                        <div class="mb-3">
                            <span class="form-label d-block">Mulai Perpanjangan</span>
                            <span class="fw-semibold">{{ Format::date($rental->extensionStartDate()) }}</span>
                        </div>

                        <x-radio-group name="rental_type" label="Jenis Perpanjangan" :selected="RentalType::Daily->value"
                                       :options="collect($rentalTypes)->mapWithKeys(fn ($type) => [$type->value => $type->label()])"/>

                        <div id="daysField" class="col-sm-6" @if (! $isDaily) hidden @endif>
                            <x-input name="days" label="Jumlah Hari" type="number" min="1" value="1"
                                     help="Maksimal {{ $maxDailyDays }} hari." :disabled="! $isDaily" required/>
                        </div>

                        <div class="mb-3">
                            <span class="form-label d-block">Tanggal Selesai Baru</span>
                            <span class="fw-semibold" id="endDate">–</span>
                            @error('end_date')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between align-items-baseline border-top pt-3 mb-3">
                            <span>
                                <span class="fs-5">Sisa Biaya</span>
                                <span class="small text-body-secondary d-block" id="feeBreakdown"></span>
                            </span>
                            <span class="fs-5 fw-bold" id="fee">–</span>
                        </div>

                        <x-input name="payment_proof" label="Bukti Pembayaran" type="file" accept="image/png,image/jpeg"
                                 help="Gambar JPG atau PNG, maksimal 2 MB." required/>

                        <div class="d-flex gap-2 justify-content-end mt-4">
                            <a href="{{ route('admin.extensions.index') }}" class="btn btn-outline-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary px-4">Simpan Perpanjangan</button>
                        </div>
                    </div>
                </section>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/live-quote.js') }}"></script>
    <script src="{{ asset('js/extension-form.js') }}"></script>
@endpush
