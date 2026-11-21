<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Poppins:wght@600&display=swap" rel="stylesheet">

<style>
    .login-page {
        min-height: 100vh;
        min-height: 100dvh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fbf9f8;
        font-family: 'Inter', sans-serif;
        color: #4a4a4a;
        padding: clamp(20px, 4vw, 36px) clamp(12px, 3vw, 20px);
    }

    .login-wrapper {
        width: 100%;
        max-width: 480px;
        margin: 0 auto;
    }

    /* =========================
       HEADER
    ========================= */

    .login-header {
        text-align: center;
        margin-bottom: clamp(18px, 4vw, 32px);
    }

    .login-header h1 {
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        font-size: clamp(26px, 6vw, 32px);
        line-height: 1.2;
        color: #121212;
        margin: 0;
    }

    .logo-role-color {
        color: #dc143c;
    }

    .login-header p {
        margin: 8px 0 0;
        font-size: clamp(14px, 3.5vw, 16px);
        line-height: 150%;
        color: #4a4a4a;
    }

    /* =========================
       ROLE SELECTOR
    ========================= */

    .role-selector {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: clamp(8px, 2.5vw, 16px);
        margin-bottom: clamp(16px, 3.5vw, 24px);
    }

    .role-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: clamp(10px, 2vw, 16px) clamp(4px, 1.5vw, 8px);
        background: #ffffff;
        border: 1px solid #eaeaea;
        border-radius: 12px;
        text-decoration: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        transition: box-shadow 0.2s, border-color 0.2s, transform 0.15s;
    }

    .role-card:hover {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        transform: translateY(-2px);
    }

    .role-card.active {
        border: 1.5px solid #dc143c;
        padding: clamp(9.5px, calc(2vw - 0.5px), 15.5px) clamp(3.5px, calc(1.5vw - 0.5px), 7.5px);
        box-shadow: 0 4px 16px rgba(220, 20, 60, 0.12);
    }

    .role-icon {
        width: clamp(20px, 4vw, 24px);
        height: clamp(20px, 4vw, 24px);
        margin-bottom: clamp(4px, 1vw, 8px);
        color: #4a4a4a;
    }

    .role-icon svg {
        width: 100%;
        height: 100%;
        display: block;
        fill: none;
        stroke: currentColor;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .role-card.active .role-icon {
        color: #dc143c;
    }

    .role-name {
        font-size: clamp(13px, 3.2vw, 16px);
        font-weight: 600;
        color: #121212;
    }

    .role-description {
        margin-top: 4px;
        font-size: clamp(10px, 2.5vw, 13px);
        line-height: 130%;
        color: #666;
        word-break: break-word;
    }

    /* =========================
       CARD & FORM
    ========================= */

    .login-card {
        background: #ffffff;
        border: 1px solid #eaeaea;
        border-radius: 14px;
        padding: clamp(16px, 4vw, 28px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
    }

    .form-group {
        margin-bottom: clamp(12px, 3vw, 18px);
    }

    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-size: 14px;
        font-weight: 600;
        letter-spacing: 0.01em;
        color: #121212;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        height: 48px;
        padding: 0 14px;
        font-family: 'Inter', sans-serif;
        font-size: 16px;
        color: #121212;
        background: #ffffff;
        border: 1px solid #e5e5e5;
        border-radius: 10px;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
        box-sizing: border-box;
    }

    .form-group input::placeholder {
        color: #8a8a8a;
    }

    .form-group input:focus,
    .form-group select:focus {
        border-color: #dc143c;
        box-shadow: 0 0 0 3px rgba(220, 20, 60, 0.15);
    }

    .login-button {
        width: 100%;
        min-height: 48px;
        margin-top: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 16px;
        font-weight: 600;
        color: #ffffff;
        background: #dc143c;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        transition: background 0.2s, transform 0.1s;
        touch-action: manipulation;
    }

    .login-button:hover {
        background: #c61236;
    }

    .login-button:active {
        transform: scale(0.99);
    }

    /* =========================
       PESAN
    ========================= */

    .alert {
        margin-bottom: 16px;
        padding: 10px 14px;
        font-size: 14px;
        line-height: 150%;
        border-radius: 10px;
        word-break: break-word;
    }

    .alert-success {
        color: #00531a;
        background: #e8f6ec;
        border: 1px solid #28a745;
    }

    .alert-error {
        color: #93000a;
        background: #ffdad6;
        border: 1px solid #ba1a1a;
    }

    .alert-error ul {
        margin: 0;
        padding-left: 18px;
    }

    /* =========================
       LINK GANTI HALAMAN
    ========================= */

    .account-switch {
        margin-top: clamp(16px, 4vw, 24px);
        text-align: center;
        font-size: 14px;
        line-height: 150%;
        color: #4a4a4a;
    }

    .account-switch a {
        margin-left: 4px;
        font-weight: 600;
        color: #dc143c;
        text-decoration: none;
    }

    .account-switch a:hover {
        text-decoration: underline;
    }

    /* =========================
       RESPONSIVE
    ========================= */

    @media (max-width: 480px) {
        .role-selector {
            gap: 6px;
        }

        .role-card {
            padding: 10px 4px;
        }

        .role-name {
            font-size: 13px;
        }

        .role-description {
            font-size: 10.5px;
        }

        .login-card {
            padding: 16px 14px;
        }
    }

    @media (max-width: 340px) {
        .role-description {
            display: none;
        }
        .role-card {
            padding: 8px 2px;
            min-height: 58px;
            justify-content: center;
        }
    }
</style>
