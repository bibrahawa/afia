<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ \App\Support\Marque::titre('Connexion') }}</title>
    
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">

    <link rel="icon" href="{{ asset('assets/img/kaiadmin/favicon.ico') }}" type="image/x-icon">

    <style>
        :root {
            --primary: #087f6b;
            --primary-dark: #056655;
            --primary-soft: #e9f7f3;
            --accent: #e5a23c;
            --bg: #f5f7f6;
            --surface: #ffffff;
            --text: #17231f;
            --text-soft: #66756f;
            --border: #e1e8e5;
            --danger: #c4472d;
            --danger-soft: #fff1ed;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            -webkit-font-smoothing: antialiased;
        }

        .login-shell {
            width: 100%;
            max-width: 400px;
        }

        .login-brand {
            display: flex;
            align-items: center;
            gap: 11px;
            margin-bottom: 28px;
        }

        .login-brand-icon {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: var(--primary-soft);
            color: var(--primary);
            font-size: 19px;
            font-weight: 700;
        }

        .login-brand-name {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--primary);
        }

        .login-brand-sub {
            display: block;
            font-size: .78rem;
            color: var(--text-soft);
            font-weight: 500;
        }

        .login-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px 28px;
            box-shadow: 0 8px 30px rgba(20, 40, 34, .08);
        }

        .login-title {
            margin: 0 0 6px;
            font-size: 1.35rem;
            font-weight: 750;
            letter-spacing: -.02em;
        }

        .login-hint {
            margin: 0 0 24px;
            color: var(--text-soft);
            font-size: .88rem;
            line-height: 1.5;
        }

        .login-alert {
            display: flex;
            gap: 10px;
            padding: 12px 14px;
            margin-bottom: 18px;
            border-radius: 12px;
            background: var(--danger-soft);
            color: var(--danger);
            font-size: .84rem;
            line-height: 1.5;
        }

        .login-label {
            display: block;
            margin-bottom: 7px;
            font-size: .82rem;
            font-weight: 700;
        }

        .login-field {
            position: relative;
            margin-bottom: 18px;
        }

        .login-input {
            width: 100%;
            min-height: 50px;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--surface);
            color: var(--text);
            font-size: .96rem;
            font-family: inherit;
            outline: none;
            transition: border-color 160ms ease, box-shadow 160ms ease;
        }

        .login-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(8, 127, 107, .12);
        }

        .login-input.has-toggle { padding-right: 46px; }

        .login-toggle-password {
            position: absolute;
            right: 6px;
            top: 32px;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 0;
            background: transparent;
            color: var(--text-soft);
            cursor: pointer;
            border-radius: 8px;
        }

        .login-toggle-password:hover { background: var(--primary-soft); color: var(--primary); }

        .login-forgot {
            display: block;
            text-align: right;
            margin: -8px 0 20px;
            font-size: .82rem;
            font-weight: 600;
            color: var(--primary);
            text-decoration: none;
        }

        .login-forgot:hover { color: var(--primary-dark); text-decoration: underline; }

        .login-btn {
            width: 100%;
            min-height: 50px;
            border: 0;
            border-radius: 12px;
            background: var(--primary);
            color: #fff;
            font-size: .95rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(8, 127, 107, .16);
            transition: background 160ms ease, transform 160ms ease;
        }

        .login-btn:hover { background: var(--primary-dark); }
        .login-btn:active { transform: scale(.98); }

        .login-footer {
            margin-top: 22px;
            text-align: center;
            color: var(--text-soft);
            font-size: .78rem;
        }

        @media (max-width: 360px) {
            .login-card { padding: 26px 20px; }
        }
    </style>
</head>
<body>

    <div class="login-shell">

        <div class="login-brand">
            <div class="login-brand-icon">+</div>
            <div>
                <div class="login-brand-name">{{ \App\Support\Marque::nom() }}</div>
                <span class="login-brand-sub">Espace personnel</span>
            </div>
        </div>

        <div class="login-card">

            <h1 class="login-title">Connexion</h1>
            <p class="login-hint">Accédez à votre espace de gestion de la clinique.</p>

            @if ($errors->any())
                <div class="login-alert" role="alert">
                    <span>⚠️</span>
                    <span>
                        @foreach ($errors->all() as $error)
                            {{ $error }}@if(!$loop->last)<br>@endif
                        @endforeach
                    </span>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="login-field">
                    <label class="login-label" for="phone">Téléphone</label>
                    <input
                        type="tel"
                        name="phone"
                        id="phone"
                        class="login-input"
                        inputmode="numeric"
                        placeholder="622 00 00 00"
                        autocomplete="tel"
                        maxlength="9"
                        pattern="[0-9]{9}"
                        value="{{ old('phone') }}"
                        required
                        autofocus
                    >
                </div>

                <div class="login-field">
                    <label class="login-label" for="password">Mot de passe</label>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="login-input has-toggle"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="login-toggle-password" id="togglePassword" aria-label="Afficher le mot de passe">
                        <svg id="iconEyeOpen" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg id="iconEyeClosed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none">
                            <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.5 18.5 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </button>
                </div>

                <a href="#" class="login-forgot">Mot de passe oublié ?</a>

                <button type="submit" class="login-btn">Se connecter</button>
            </form>

        </div>

        <p class="login-footer">Plateforme de gestion clinique — Guinée</p>

    </div>

    <script>
        (function () {
            const toggle = document.getElementById('togglePassword');
            const password = document.getElementById('password');
            const eyeOpen = document.getElementById('iconEyeOpen');
            const eyeClosed = document.getElementById('iconEyeClosed');

            toggle.addEventListener('click', function () {
                const visible = password.type === 'text';
                password.type = visible ? 'password' : 'text';
                eyeOpen.style.display = visible ? '' : 'none';
                eyeClosed.style.display = visible ? 'none' : '';
                toggle.setAttribute('aria-label', visible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
            });
        })();
    </script>

</body>
</html>