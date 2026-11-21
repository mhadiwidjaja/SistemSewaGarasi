@extends('layouts.app')

@use('App\Enums\GarageType')
@use('App\Support\Format')

@section('title', 'Daftar Garasi')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0">Daftar Garasi</h1>
        <a href="{{ route('admin.garages.create') }}" class="btn btn-primary">+ Buat Garasi</a>
    </div>

    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a href="{{ route('admin.garages.index') }}" @class(['nav-link', 'active' => $selectedType === null])
               @if ($selectedType === null) aria-current="page" @endif>Semua ({{ $countsByType->sum() }})</a>
        </li>
        @foreach (GarageType::cases() as $type)
            <li class="nav-item">
                <a href="{{ route('admin.garages.index', ['type' => $type->value]) }}"
                   @class(['nav-link', 'active' => $selectedType === $type])
                   @if ($selectedType === $type) aria-current="page" @endif>
                    {{ $type->label() }} ({{ $countsByType[$type->value] ?? 0 }})
                </a>
            </li>
        @endforeach
    </ul>

    <div class="card shadow-sm border-top-0 rounded-top-0">
        @if ($garages->isEmpty())
            <div class="card-body text-body-secondary">Belum ada garasi. Klik "Buat Garasi" untuk menambahkan.</div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th scope="col">Kode</th>
                        <th scope="col">Jenis Garasi</th>
                        <th scope="col" class="text-end">Harga Harian</th>
                        <th scope="col" class="text-end">Harga Bulanan</th>
                        <th scope="col" class="text-end">Harga Pengisian per kWh</th>
                        <th scope="col">Status Hari Ini</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($garages as $garage)
                        <tr>
                            <td class="fw-semibold">{{ $garage->code }}</td>
                            <td>{{ $garage->type->label() }}</td>
                            <td class="text-end text-nowrap">{{ Format::rupiah($garage->daily_price) }}</td>
                            <td class="text-end text-nowrap">{{ Format::rupiah($garage->monthlyPrice()) }}</td>
                            <td class="text-end text-nowrap">
                                {{ $garage->charging_price_per_kwh === null ? '–' : Format::rupiah($garage->charging_price_per_kwh) }}
                            </td>
                            <td>
                                @if ($garage->currentRental)
                                    <span class="badge text-bg-warning">Disewa</span>
                                    <span class="small text-body-secondary">s.d. {{ Format::date($garage->currentRental->end_date) }}</span>
                                @else
                                    <span class="badge text-bg-success">Tersedia</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-3">
        {{ $garages->links() }}
    </div>
@endsection
