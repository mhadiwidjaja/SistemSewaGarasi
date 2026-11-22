@extends('layouts.app')

@use('App\Support\Format')

@section('title', 'Perpanjang Sewa')

@section('content')
    <h1 class="h3 mb-1">Perpanjang Sewa</h1>
    <p class="text-body-secondary mb-4">Garasi yang disewa hari ini ({{ Format::date(today()) }}).</p>

    <div class="card shadow-sm">
        @if ($rentals->isEmpty())
            <div class="card-body text-body-secondary">Tidak ada garasi yang sedang disewa hari ini.</div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th scope="col">Kode Garasi</th>
                        <th scope="col">Jenis Garasi</th>
                        <th scope="col">Penyewa</th>
                        <th scope="col">Kendaraan</th>
                        <th scope="col">Jenis Sewa</th>
                        <th scope="col">Mulai</th>
                        <th scope="col">Selesai</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($rentals as $rental)
                        <tr>
                            <td class="fw-semibold">{{ $rental->garage->code }}</td>
                            <td>{{ $rental->garage->type->label() }}</td>
                            <td>{{ $rental->renter->name }}</td>
                            <td><x-vehicle :rental="$rental"/></td>
                            <td>{{ $rental->rental_type->label() }}</td>
                            <td>{{ Format::date($rental->start_date) }}</td>
                            <td>{{ Format::date($rental->end_date) }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.extensions.edit', $rental) }}" class="btn btn-primary btn-sm">Perpanjang</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
