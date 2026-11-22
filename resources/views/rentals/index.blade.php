@extends('layouts.app')

@use('App\Enums\RentalStatus')
@use('App\Support\Format')

@section('title', 'Riwayat Sewa')

@section('content')
    @php
        $statusColors = [
            RentalStatus::Upcoming->value => 'primary',
            RentalStatus::Active->value => 'success',
            RentalStatus::Finished->value => 'secondary',
        ];
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0">Riwayat Sewa</h1>
        @if ($rentals->isNotEmpty())
            <a href="{{ route('rentals.create') }}" class="btn btn-primary">+ Sewa Garasi Lagi</a>
        @endif
    </div>

    <div class="card shadow-sm">
        @if ($rentals->isEmpty())
            <div class="card-body">
                <p class="text-body-secondary">Anda belum memiliki riwayat sewa.</p>
                <a href="{{ route('rentals.create') }}" class="btn btn-primary">Sewa Garasi</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th scope="col">Kode Garasi</th>
                        <th scope="col">Jenis Garasi</th>
                        <th scope="col">Kendaraan</th>
                        <th scope="col">Jenis Sewa</th>
                        <th scope="col">Mulai</th>
                        <th scope="col">Selesai</th>
                        <th scope="col" class="text-end">Total Biaya</th>
                        <th scope="col">Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($rentals as $rental)
                        @php($status = $rental->status())
                        <tr @class(['table-success' => $rental->id === session('newRentalId')])>
                            <td class="fw-semibold">{{ $rental->garage->code }}</td>
                            <td>{{ $rental->garage->type->label() }}</td>
                            <td><x-vehicle :rental="$rental"/></td>
                            <td>{{ $rental->rental_type->label() }}</td>
                            <td>{{ Format::date($rental->start_date) }}</td>
                            <td>
                                {{ Format::date($rental->end_date) }}
                                @if ($rental->extensions->isNotEmpty())
                                    <div class="small text-body-secondary">Diperpanjang</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">{{ Format::rupiah($rental->totalFee()) }}</td>
                            <td><span class="badge text-bg-{{ $statusColors[$status->value] }}">{{ $status->label() }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
