@extends('layouts.app')

@section('title', 'Daftar - TechnoPOS')

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
                Buat akun untuk mulai bekerja
            </p>

        </div>


        {{-- ROLE --}}
        <div class="role-selector">

            {{-- KASIR --}}
            <a
                href="{{ route('register', ['role' => 'kasir']) }}"
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
                href="{{ route('register', ['role' => 'pic']) }}"
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
                href="{{ route('register', ['role' => 'dapur']) }}"
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


        {{-- REGISTER CARD --}}
        <div class="login-card">

            <form action="{{ route('register.process') }}" method="POST">
                @csrf

                {{-- PESAN ERROR --}}
                @if ($errors->any())
                    <div class="alert alert-error">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- NAMA --}}
                <div class="form-group">
                    <label for="name">Nama</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="Masukkan nama lengkap"
                        value="{{ old('name') }}"
                        required
                    >
                </div>

                {{-- USERNAME --}}
                <div class="form-group">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Buat username"
                        value="{{ old('username') }}"
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
                        placeholder="Buat password"
                        required
                    >
                </div>

                {{-- KONFIRMASI PASSWORD --}}
                <div class="form-group">
                    <label for="password_confirmation">Konfirmasi Password</label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        placeholder="Ulangi password"
                        required
                    >
                </div>

                {{-- ROLE --}}
                <input type="hidden" name="role" value="{{ $role }}">

                {{-- OUTLET --}}
                @if(in_array($role, ['kasir', 'dapur']))

                    <div class="form-group">
                        <label for="outlet">Outlet tempat bertugas</label>

                        <select id="outlet" name="outlet" required>
                            <option value="">Pilih outlet</option>
                            <option value="Kal's Maison">Kal's Maison</option>
                            <option value="Sky Watch">Sky Watch</option>
                            <option value="Needs mart">Needs mart</option>
                            <option value="Billiard">Billiard</option>
                            <option value="GPM PTDI">GPM PTDI</option>
                            <option value="Technocoffe">Technocoffe</option>
                        </select>
                    </div>

                @endif

                {{-- BUTTON --}}
                <button type="submit" class="login-button">
                    Daftar sebagai {{ ucfirst($role) }}
                </button>

            </form>

        </div>


        {{-- LOGIN LINK --}}
        <div class="account-switch">

            <span>Sudah memiliki akun?</span>

            <a href="{{ route('login', ['role' => $role]) }}">
                Login
            </a>

        </div>

    </div>

</div>

@endsection
