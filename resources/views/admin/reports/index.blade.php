@extends('layouts.app')

@use('App\Enums\ReportType')
@use('App\Support\Format')

@section('title', 'Laporan Sewa')

@section('content')
    <h1 class="h3 mb-4">Laporan Sewa Garasi</h1>

    <ul class="nav nav-tabs">
        @foreach (ReportType::cases() as $type)
            <li class="nav-item">
                <a href="{{ route('admin.reports.index', ['type' => $type->value, 'year' => $year, 'month' => $month->format('Y-m')]) }}"
                   @class(['nav-link', 'active' => $reportType === $type])
                   @if ($reportType === $type) aria-current="page" @endif>{{ $type->label() }}</a>
            </li>
        @endforeach
    </ul>

    <div class="card shadow-sm border-top-0 rounded-top-0">
        @if (in_array($reportType, [ReportType::PerMonth, ReportType::PerDay], true))
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('admin.reports.index') }}" class="row g-2 align-items-end">
                    <input type="hidden" name="type" value="{{ $reportType->value }}">
                    <div class="col-auto">
                        @if ($reportType === ReportType::PerMonth)
                            <label for="year" class="form-label">Tahun</label>
                            <input type="number" id="year" name="year" value="{{ $year }}" min="2000" max="2100"
                                   @class(['form-control', 'is-invalid' => $errors->has('year')])>
                        @else
                            <label for="month" class="form-label">Bulan</label>
                            <input type="month" id="month" name="month" value="{{ $month->format('Y-m') }}" placeholder="YYYY-MM"
                                   @class(['form-control', 'is-invalid' => $errors->has('month')])>
                        @endif
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Tampilkan</button>
                    </div>
                    @if ($errors->any())
                        <div class="col-12 text-danger small">{{ $errors->first() }}</div>
                    @endif
                </form>
            </div>
        @endif

        @if ($rows->isEmpty())
            <div class="card-body text-body-secondary">Belum ada data sewa.</div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th scope="col">{{ $reportType->groupHeading() }}</th>
                        <th scope="col" class="text-end">Jumlah Sewa</th>
                        <th scope="col" class="text-end">Pendapatan</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($rows as $row)
                        <tr @class(['text-body-secondary' => $row['rental_count'] === 0])>
                            <td>
                                {{ $row['label'] }}
                                @if ($row['detail'])
                                    <div class="small text-body-secondary">{{ $row['detail'] }}</div>
                                @endif
                            </td>
                            <td class="text-end">{{ $row['rental_count'] }}</td>
                            <td class="text-end text-nowrap">{{ Format::rupiah($row['revenue']) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr class="fw-bold">
                        <td>Total</td>
                        <td class="text-end">{{ $rows->sum('rental_count') }}</td>
                        <td class="text-end text-nowrap">{{ Format::rupiah($rows->sum('revenue')) }}</td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

    <p class="small text-body-secondary mt-3">
        Pendapatan = biaya sewa + biaya pengisian daya + biaya perpanjangan, dihitung pada tanggal mulai sewa.
    </p>
@endsection
