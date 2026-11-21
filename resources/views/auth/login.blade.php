@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-4">Masuk</h1>

                    <form method="POST" action="{{ route('login') }}" novalidate>
                        @csrf
                        <x-input name="email" label="Email" type="email" autocomplete="username" required autofocus/>
                        <x-input name="password" label="Kata Sandi" type="password" autocomplete="current-password" required/>

                        <button type="submit" class="btn btn-primary w-100 mt-2">Masuk</button>
                    </form>

                    <p class="text-center mt-4 mb-0">
                        Belum punya akun? <a href="{{ route('register') }}">Daftar</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
