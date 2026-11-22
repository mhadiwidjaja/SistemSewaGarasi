@extends('layouts.app')

@use('App\Support\Format')

@section('title', 'Verifikasi Pendaftar')

@section('content')
    <h1 class="h3 mb-4">Verifikasi Pendaftar</h1>

    <div class="card shadow-sm">
        @if ($pendingRenters->isEmpty())
            <div class="card-body text-body-secondary">Tidak ada pendaftar yang menunggu verifikasi.</div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th scope="col">NIK</th>
                        <th scope="col">Nama Lengkap</th>
                        <th scope="col">Nomor Telepon</th>
                        <th scope="col">Email</th>
                        <th scope="col">Tanggal Daftar</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($pendingRenters as $renter)
                        <tr>
                            <td class="font-monospace">{{ $renter->nik }}</td>
                            <td>{{ $renter->name }}</td>
                            <td>{{ $renter->phone }}</td>
                            <td>{{ $renter->email }}</td>
                            <td>{{ Format::date($renter->created_at) }}</td>
                            <td class="text-end text-nowrap">
                                <form method="POST" action="{{ route('admin.registrations.approve', $renter) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm"
                                            data-confirm="Terima pendaftaran {{ $renter->name }}?"
                                            data-confirm-label="Ya, Terima" data-confirm-variant="success">Terima</button>
                                </form>
                                <form method="POST" action="{{ route('admin.registrations.reject', $renter) }}" class="d-inline ms-2">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger btn-sm"
                                            data-confirm="Tolak pendaftaran {{ $renter->name }}?"
                                            data-confirm-label="Ya, Tolak" data-confirm-variant="danger">Tolak</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
