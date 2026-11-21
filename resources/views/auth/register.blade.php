@extends('layouts.app')

@section('title', 'Daftar')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-1">Daftar sebagai Penyewa</h1>
                    <p class="text-body-secondary mb-4">Akun dapat digunakan setelah diverifikasi admin.</p>

                    <form method="POST" action="{{ route('register') }}" novalidate>
                        @csrf
                        <x-input name="nik" label="NIK" inputmode="numeric" maxlength="16"
                                 help="16 digit angka sesuai KTP." required autofocus/>
                        <x-input name="name" label="Nama Lengkap" autocomplete="name" required/>
                        <x-input name="phone" label="Nomor Telepon" type="tel" autocomplete="tel"
                                 placeholder="08123456789" required/>
                        <x-input name="email" label="Email" type="email" autocomplete="email"
                                 help="Digunakan untuk masuk." required/>
                        <x-input name="password" label="Kata Sandi" type="password" autocomplete="new-password"
                                 help="Minimal 8 karakter." required/>
                        <x-input name="password_confirmation" label="Konfirmasi Kata Sandi" type="password"
                                 autocomplete="new-password" required/>

                        <button type="submit" class="btn btn-primary w-100 mt-2">Daftar</button>
                    </form>

                    <p class="text-center mt-4 mb-0">
                        Sudah punya akun? <a href="{{ route('login') }}">Masuk</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
