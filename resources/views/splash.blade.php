@extends('layouts.app')

@section('title', 'TechnoPOS')

@section('content')

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Poppins:wght@600&display=swap" rel="stylesheet">

<style>
    .splash-page {
        position: relative;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background: #fbf9f8;
        font-family: 'Inter', sans-serif;
        padding: 16px;
    }

    /* Lingkaran dekorasi: flat, tanpa gradasi */
    .splash-circle {
        position: absolute;
        border-radius: 9999px;
        background: #dc143c;
        opacity: 0.06;
    }

    .circle-large {
        width: 560px;
        height: 560px;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        opacity: 0.04;
    }

    .circle-top {
        width: 240px;
        height: 240px;
        top: -88px;
        right: -64px;
    }

    .circle-bottom {
        width: 160px;
        height: 160px;
        bottom: -48px;
        left: -32px;
    }

    .splash-content {
        position: relative;
        z-index: 1;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .splash-content h1 {
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        font-size: clamp(32px, 8vw, 48px);
        line-height: 1.2;
        color: #121212;
        margin: 0;
    }

    .splash-pos {
        margin-top: 8px;
        background: #dc143c;
        color: #ffffff;
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        font-size: clamp(16px, 4vw, 22px);
        line-height: 1.4;
        letter-spacing: 0.08em;
        padding: 4px clamp(16px, 4vw, 24px);
        border-radius: 12px;
    }

    .splash-content p {
        margin: clamp(16px, 4vw, 24px) 0 0;
        font-size: clamp(14px, 3.5vw, 16px);
        line-height: 160%;
        color: #4a4a4a;
        max-width: 90vw;
    }

    .splash-loading {
        margin-top: clamp(24px, 5vw, 32px);
        width: min(220px, 70vw);
        height: 5px;
        background: #eaeaea;
        border-radius: 9999px;
        overflow: hidden;
    }

    .loading-progress {
        height: 100%;
        width: 0;
        background: #dc143c;
        border-radius: 9999px;
        animation: splash-load 2.5s ease-out forwards;
    }

    @keyframes splash-load {
        to { width: 100%; }
    }

    @media (max-width: 767px) {
        .circle-large {
            width: min(360px, 85vw);
            height: min(360px, 85vw);
        }

        .circle-top {
            width: 160px;
            height: 160px;
            top: -40px;
            right: -40px;
        }

        .circle-bottom {
            width: 120px;
            height: 120px;
            bottom: -30px;
            left: -30px;
        }
    }
</style>

<div class="splash-page">

    <div class="splash-circle circle-large"></div>
    <div class="splash-circle circle-top"></div>
    <div class="splash-circle circle-bottom"></div>

    <div class="splash-content">

        <h1>Techno</h1>

        <div class="splash-pos">
            POS
        </div>

        <p>
            Satu kasir untuk semua outlet
        </p>

        {{-- LOADING --}}
        <div class="splash-loading">
            <div class="loading-progress"></div>
        </div>

    </div>

</div>

<script>
    setTimeout(() => {
        window.location.href = "{{ url('/login') }}";
    }, 2500);
</script>

@endsection
