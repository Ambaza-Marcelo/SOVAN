@extends('backend.auth.auth_master')

@section('auth_title')
    @lang('messages.auth_login_title')
@endsection

@section('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    :root {
        --sovan-teal: #163f3d;
        --sovan-teal-deep: #0f2c2a;
        --sovan-teal-mid: #1f5652;
        --sovan-gold: #e2b03a;
        --sovan-gold-soft: #f0c85a;
        --sovan-blue: #1e2a78;
        --sovan-green: #1faa5a;
        --sovan-ink: #15202b;
        --sovan-muted: #6b7585;
        --sovan-line: #d9dee8;
        --sovan-cream: #f4f1ea;
    }

    html, body {
        margin: 0;
        min-height: 100%;
        font-family: 'Manrope', sans-serif;
        color: var(--sovan-ink);
        background: var(--sovan-cream);
    }

    #preloader {
        background: var(--sovan-cream) !important;
    }

    .sovan-split {
        min-height: 100vh;
        display: grid;
        grid-template-columns: 1.15fr 1fr;
    }

    /* ===== LEFT PANEL ===== */
    .sovan-panel {
        position: relative;
        overflow: hidden;
        padding: 42px 48px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        color: #fff;
        background:
            radial-gradient(circle at 18% 20%, rgba(226, 176, 58, 0.18), transparent 34%),
            radial-gradient(circle at 85% 80%, rgba(31, 170, 90, 0.14), transparent 42%),
            linear-gradient(155deg, #0f2c2a 0%, #163f3d 42%, #1a4a5c 100%);
    }

    .sovan-panel::before {
        content: "";
        position: absolute;
        inset: 0;
        background:
            repeating-linear-gradient(
                -22deg,
                transparent 0,
                transparent 26px,
                rgba(255,255,255,0.015) 26px,
                rgba(255,255,255,0.015) 27px
            );
        pointer-events: none;
    }

    .sovan-panel::after {
        content: "";
        position: absolute;
        right: -40px;
        bottom: -60px;
        width: 320px;
        height: 320px;
        border: 28px solid rgba(255,255,255,0.04);
        border-radius: 36px;
        transform: rotate(18deg);
        pointer-events: none;
    }

    .sovan-panel-inner {
        position: relative;
        z-index: 1;
        max-width: 560px;
    }

    .sovan-panel-logo-wrap {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 92px;
        height: 92px;
        border-radius: 50%;
        background: #fff;
        box-shadow: 0 10px 28px rgba(0,0,0,0.22);
        margin-bottom: 28px;
        overflow: hidden;
        padding: 10px;
    }

    .sovan-panel-logo-wrap img {
        width: 100%;
        height: auto;
        object-fit: contain;
    }

    .sovan-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 18px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--sovan-gold-soft);
    }

    .sovan-status .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--sovan-gold);
        box-shadow: 0 0 0 0 rgba(226, 176, 58, 0.55);
        animation: sovanPulse 1.8s infinite;
    }

    @keyframes sovanPulse {
        0% { box-shadow: 0 0 0 0 rgba(226, 176, 58, 0.55); }
        70% { box-shadow: 0 0 0 10px rgba(226, 176, 58, 0); }
        100% { box-shadow: 0 0 0 0 rgba(226, 176, 58, 0); }
    }

    .sovan-panel h1 {
        margin: 0 0 14px;
        font-size: 42px;
        font-weight: 800;
        letter-spacing: -0.03em;
        line-height: 1.1;
        color: #fff;
    }

    .sovan-panel-lead {
        margin: 0 0 28px;
        max-width: 480px;
        font-size: 15px;
        line-height: 1.65;
        color: rgba(255,255,255,0.78);
    }

    .sovan-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .sovan-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .sovan-chip-solid {
        background: rgba(15, 20, 24, 0.45);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.08);
    }

    .sovan-chip-outline {
        background: transparent;
        color: #fff;
        border: 1px solid rgba(255,255,255,0.35);
    }

    .sovan-chip i {
        color: var(--sovan-gold);
    }

    .sovan-panel-foot {
        position: relative;
        z-index: 1;
        margin-top: 40px;
        font-size: 12px;
        color: rgba(255,255,255,0.55);
        letter-spacing: 0.02em;
    }

    /* ===== RIGHT PANEL ===== */
    .sovan-form-side {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 36px 28px;
        background:
            radial-gradient(circle at 80% 15%, rgba(226, 176, 58, 0.08), transparent 30%),
            linear-gradient(180deg, #f7f4ee 0%, #eef2f0 100%);
    }

    .sovan-card {
        width: 100%;
        max-width: 420px;
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 18px 50px rgba(15, 44, 42, 0.12);
        overflow: hidden;
        animation: sovanCardIn 0.65s ease both;
    }

    @keyframes sovanCardIn {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .sovan-card-accent {
        height: 5px;
        background: linear-gradient(90deg, var(--sovan-gold) 0%, var(--sovan-green) 55%, var(--sovan-teal) 100%);
    }

    .sovan-card-body {
        padding: 28px 30px 24px;
    }

    .sovan-card-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 22px;
    }

    .sovan-card-brand img {
        width: 52px;
        height: auto;
        object-fit: contain;
    }

    .sovan-card-brand-text strong {
        display: block;
        font-size: 16px;
        font-weight: 800;
        color: var(--sovan-blue);
        letter-spacing: 0.02em;
    }

    .sovan-card-brand-text span {
        display: block;
        margin-top: 2px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: var(--sovan-green);
    }

    .sovan-card h2 {
        margin: 0 0 6px;
        font-size: 28px;
        font-weight: 800;
        letter-spacing: 0.04em;
        color: var(--sovan-blue);
        text-transform: uppercase;
    }

    .sovan-card-help {
        margin: 0 0 22px;
        font-size: 13px;
        color: var(--sovan-muted);
        line-height: 1.5;
    }

    .sovan-alert {
        margin-bottom: 14px;
    }

    .sovan-field {
        position: relative;
        margin-bottom: 14px;
    }

    .sovan-field input {
        width: 100%;
        height: 48px;
        border: 1px solid var(--sovan-line);
        border-radius: 10px;
        background: #fff;
        padding: 0 42px 0 14px;
        font-size: 14px;
        color: var(--sovan-ink);
        outline: none;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        box-shadow: none;
    }

    .sovan-field input::placeholder {
        color: #98a0b0;
    }

    .sovan-field input:focus {
        border-color: var(--sovan-teal-mid);
        box-shadow: 0 0 0 3px rgba(31, 86, 82, 0.12);
    }

    .sovan-field i {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--sovan-teal-mid);
        font-size: 15px;
        pointer-events: none;
    }

    .sovan-field .invalid-feedback {
        display: block;
        margin-top: 6px;
        font-size: 12px;
    }

    .sovan-remember {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 6px 0 18px;
        color: var(--sovan-muted);
        font-size: 13px;
        font-weight: 500;
    }

    .sovan-remember input {
        width: 15px;
        height: 15px;
        accent-color: var(--sovan-teal);
        cursor: pointer;
    }

    .sovan-remember label {
        margin: 0;
        cursor: pointer;
    }

    .sovan-submit {
        width: 100%;
        height: 48px;
        border: 0;
        border-radius: 10px;
        background: linear-gradient(135deg, var(--sovan-teal-deep), var(--sovan-teal-mid));
        color: #fff;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-shadow: 0 12px 24px rgba(15, 44, 42, 0.22);
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
    }

    .sovan-submit:hover,
    .sovan-submit:focus {
        transform: translateY(-1px);
        filter: brightness(1.05);
        box-shadow: 0 16px 28px rgba(15, 44, 42, 0.28);
        color: #fff;
        outline: none;
    }

    .sovan-submit i {
        transition: transform 0.2s ease;
    }

    .sovan-submit:hover i {
        transform: translateX(3px);
    }

    .sovan-card-foot {
        margin-top: 18px;
        padding-top: 14px;
        border-top: 1px solid #eef0f4;
        text-align: center;
        font-size: 11px;
        color: var(--sovan-muted);
    }

    .sovan-card-foot strong {
        color: var(--sovan-teal-deep);
        font-weight: 800;
    }

    @media (max-width: 991px) {
        .sovan-split {
            grid-template-columns: 1fr;
        }

        .sovan-panel {
            min-height: auto;
            padding: 28px 24px 32px;
        }

        .sovan-panel h1 {
            font-size: 32px;
        }

        .sovan-panel-foot {
            margin-top: 24px;
        }

        .sovan-form-side {
            padding: 24px 18px 36px;
        }
    }

    @media (max-width: 480px) {
        .sovan-card-body {
            padding: 22px 18px 18px;
        }

        .sovan-card h2 {
            font-size: 24px;
        }

        .sovan-chips {
            flex-direction: column;
        }
    }
</style>
@endsection

@section('auth-content')
<div class="sovan-split">
    <aside class="sovan-panel">
        <div class="sovan-panel-inner">
            <div class="sovan-panel-logo-wrap">
                <img src="{{ asset('img/logo.png') }}" alt="SOVAN">
            </div>

            <div class="sovan-status">
                <span class="dot"></span>
                @lang('messages.flour_mill_status_active')
            </div>

            <h1>@lang('messages.app_brand')</h1>
            <p class="sovan-panel-lead">@lang('messages.flour_mill_hero_subtitle')</p>

            <div class="sovan-chips">
                <span class="sovan-chip sovan-chip-solid">
                    <i class="fa fa-industry"></i>
                    @lang('messages.flour_mill_chip_product')
                </span>
                <span class="sovan-chip sovan-chip-outline">
                    <i class="fa fa-cogs"></i>
                    @lang('messages.flour_mill_chip_process')
                </span>
            </div>
        </div>

        <div class="sovan-panel-foot">
            @lang('messages.flour_mill_login_secure_access')
        </div>
    </aside>

    <section class="sovan-form-side">
        <div class="sovan-card">
            <div class="sovan-card-accent"></div>
            <div class="sovan-card-body">
                <div class="sovan-card-brand">
                    <img src="{{ asset('img/logo.png') }}" alt="SOVAN">
                    <div class="sovan-card-brand-text">
                        <strong>@lang('messages.app_brand')</strong>
                        <span>@lang('messages.app_brand_tagline')</span>
                    </div>
                </div>

                <h2>@lang('messages.login')</h2>
                <p class="sovan-card-help">@lang('messages.flour_mill_login_platform_help')</p>

                <form method="POST" action="{{ route('admin.login.submit') }}" autocomplete="on">
                    @csrf

                    <div class="sovan-alert">
                        @include('backend.layouts.partials.messages')
                    </div>

                    <div class="sovan-field">
                        <input
                            type="text"
                            id="exampleInputEmail1"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="@lang('messages.mail') @lang('messages.or') @lang('messages.username')"
                            required
                            autofocus
                        >
                        <i class="ti-email"></i>
                        @error('email')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="sovan-field">
                        <input
                            type="password"
                            id="exampleInputPassword1"
                            name="password"
                            placeholder="@lang('messages.password')"
                            required
                        >
                        <i class="ti-lock"></i>
                        @error('password')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="sovan-remember">
                        <input type="checkbox" id="customControlAutosizing" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label for="customControlAutosizing">@lang('messages.rememberme')</label>
                    </div>

                    <button class="sovan-submit" id="form_submit" type="submit">
                        @lang('messages.login')
                        <i class="ti-arrow-right"></i>
                    </button>
                </form>

                <div class="sovan-card-foot">
                    © {{ date('Y') }} @lang('messages.app_brand')
                    · @lang('messages.flour_mill_login_designed_by')
                    <strong>@lang('messages.app_system_vendor')</strong>
                </div>
            </div>
        </div>
    </section>
</div>

<script type="text/javascript">
    function preventBack() {
        window.history.forward();
    }
    setTimeout("preventBack()", 0);
    window.onunload = function () { null };
</script>
@endsection
