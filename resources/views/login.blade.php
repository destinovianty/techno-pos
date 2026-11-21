@extends('layouts.app')

@section('title', 'Login - TechnoPOS')

@section('content')

@include('partials.auth-style')

<div class="login-page role-{{ $role }}">

    <div class="login-wrapper">

        {{-- HEADER --}}
        <div class="login-header">

            <h1>
                Techno<span class="logo-role-color">POS</span>
            </h1>

            <p>
                Masuk untuk mulai bekerja
            </p>

        </div>


        {{-- ROLE --}}
        <div class="role-selector">

            {{-- KASIR --}}
            <a
                href="{{ route('login', ['role' => 'kasir']) }}"
                class="role-card {{ $role === 'kasir' ? 'active' : '' }}"
            >
                <div class="role-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/>
                        <path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/>
                        <path d="M12 17.5v-11"/>
                    </svg>
                </div>

                <div class="role-name">Kasir</div>
                <div class="role-description">Transaksi & struk</div>
            </a>


            {{-- PIC --}}
            <a
                href="{{ route('login', ['role' => 'pic']) }}"
                class="role-card {{ $role === 'pic' ? 'active' : '' }}"
            >
                <div class="role-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M3 3v18h18"/>
                        <path d="M18 17V9"/>
                        <path d="M13 17V5"/>
                        <path d="M8 17v-3"/>
                    </svg>
                </div>

                <div class="role-name">PIC</div>
                <div class="role-description">Manajemen outlet</div>
            </a>


            {{-- DAPUR --}}
            <a
                href="{{ route('login', ['role' => 'dapur']) }}"
                class="role-card {{ $role === 'dapur' ? 'active' : '' }}"
            >
                <div class="role-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M17 21a1 1 0 0 0 1-1v-5.35c0-.457.316-.844.727-1.041a4 4 0 0 0-2.134-7.589 5 5 0 0 0-9.186 0 4 4 0 0 0-2.134 7.588c.411.198.727.585.727 1.041V20a1 1 0 0 0 1 1Z"/>
                        <path d="M6 17h12"/>
                    </svg>
                </div>

                <div class="role-name">Dapur</div>
                <div class="role-description">Proses pesanan</div>
            </a>

        </div>


        {{-- LOGIN CARD --}}
        <div class="login-card">

            <form action="{{ route('login.process') }}" method="POST">
                @csrf

                <input type="hidden" name="role" value="{{ $role }}">

                {{-- PESAN SUKSES (setelah daftar) --}}
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- PESAN ERROR --}}
                @error('username')
                    <div class="alert alert-error">
                        {{ $message }}
                    </div>
                @enderror

                {{-- USERNAME --}}
                <div class="form-group">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="{{ old('username') }}"
                        placeholder="Masukkan username"
                        required
                    >
                </div>

                {{-- PASSWORD --}}
                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Masukkan password"
                        required
                    >
                </div>

                {{-- BUTTON --}}
                <button type="submit" class="login-button">
                    Masuk sebagai {{ ucfirst($role) }}
                </button>

            </form>

        </div>


        {{-- REGISTER LINK --}}
        <div class="account-switch">

            <span>Belum punya akun?</span>

            <a href="{{ route('register', ['role' => $role]) }}">
                Daftar
            </a>

        </div>

    </div>

</div>

@endsection
