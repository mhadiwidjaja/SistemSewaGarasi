<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · Sewa Garasi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body class="bg-body-tertiary">
<nav class="navbar navbar-expand-md bg-body border-bottom mb-4">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="{{ route('home') }}">Sewa Garasi</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Buka menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            @auth
                <ul class="navbar-nav me-auto">
                    @if (auth()->user()->isAdmin())
                        <x-nav-link route="admin.registrations.index">
                            Verifikasi Pendaftar
                            @if ($pendingRegistrationCount > 0)
                                <span class="badge text-bg-danger">{{ $pendingRegistrationCount }}</span>
                            @endif
                        </x-nav-link>
                        <x-nav-link route="admin.garages.index" active="admin.garages.*">Garasi</x-nav-link>
                        <x-nav-link route="admin.extensions.index" active="admin.extensions.*">Perpanjang Sewa</x-nav-link>
                        <x-nav-link route="admin.reports.index">Laporan</x-nav-link>
                    @else
                        <x-nav-link route="rentals.create">Sewa Garasi</x-nav-link>
                        <x-nav-link route="rentals.index">Riwayat Sewa</x-nav-link>
                    @endif
                </ul>
                <div class="d-flex align-items-center gap-3">
                    <span class="navbar-text">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Keluar</button>
                    </form>
                </div>
            @else
                <ul class="navbar-nav ms-auto">
                    <x-nav-link route="login">Masuk</x-nav-link>
                    <x-nav-link route="register">Daftar</x-nav-link>
                </ul>
            @endauth
        </div>
    </div>
</nav>

<main class="container pb-5">
    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">Periksa kembali isian yang ditandai.</div>
    @endif

    @yield('content')
</main>

<x-confirm-modal/>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="{{ asset('js/forms.js') }}"></script>
<script src="{{ asset('js/confirm-modal.js') }}"></script>
@stack('scripts')
</body>
</html>
