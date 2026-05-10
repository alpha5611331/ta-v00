<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk – VOXORA</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        serif: ['Fraunces', 'Georgia', 'serif'],
                        sans:  ['Plus Jakarta Sans', 'sans-serif'],
                    },
                }
            }
        }
    </script>

    <style>
        :root {
            --bg-main:    #D9E4FF;
            --bg-header:  #8BABF1;
            --bg-sidebar: #B3C7F7;
            --accent:     #FAAF90;
            --text-main:  #000000;
        }

        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-main);
            color: var(--text-main);
        }

        /* Focus ring global */
        :focus-visible {
            outline: 3px solid #000;
            outline-offset: 3px;
            border-radius: 4px;
        }
        :focus:not(:focus-visible) { outline: none; }

        /* Input styling */
        .form-input {
            width: 100%;
            padding: .75rem 1rem;
            border: 2px solid rgba(0,0,0,.2);
            border-radius: .75rem;
            background: rgba(255,255,255,.75);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: .9rem;
            color: #000;
            transition: border-color .18s ease, background .18s ease, box-shadow .18s ease;
        }
        .form-input:focus {
            outline: none;
            border-color: #000;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(0,0,0,.12);
        }
        .form-input.error {
            border-color: #b91c1c;
            background: #fff5f5;
        }

        /* Card */
        .login-card {
            background: rgba(255,255,255,.55);
            backdrop-filter: blur(12px);
            border: 1.5px solid rgba(0,0,0,.10);
            border-radius: 1.5rem;
            box-shadow: 0 8px 40px rgba(0,0,0,.08);
        }

        /* Submit button */
        .btn-primary {
            width: 100%;
            padding: .85rem 1rem;
            background: #000;
            color: #fff;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: .9rem;
            font-weight: 700;
            border: none;
            border-radius: .875rem;
            cursor: pointer;
            transition: opacity .18s ease, transform .12s ease;
        }
        .btn-primary:hover   { opacity: .85; }
        .btn-primary:active  { transform: scale(.98); }
        .btn-primary:focus-visible {
            outline: 3px solid #000;
            outline-offset: 3px;
        }

        /* Decorative blobs */
        .blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: .45;
            pointer-events: none;
            z-index: 0;
        }

        /* Skip link */
        .skip-link {
            position: absolute; top: -999px; left: 1rem; z-index: 9999;
            padding: .5rem 1rem; background: #000; color: var(--bg-main);
            font-weight: 700; border-radius: 0 0 .5rem .5rem; text-decoration: none;
        }
        .skip-link:focus { top: 0; }

        /* Password toggle */
        .pw-toggle {
            position: absolute; right: .875rem; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: #475569; padding: .25rem;
            line-height: 1; border-radius: .375rem;
        }
        .pw-toggle:focus-visible { outline: 3px solid #000; outline-offset: 2px; }
    </style>
</head>

<body class="h-full flex flex-col">

    {{-- Skip link aksesibilitas --}}
    <a href="#login-form" class="skip-link">Lewati ke formulir masuk</a>

    {{-- Live region NVDA --}}
    <div id="aria-announcer" role="status" aria-live="polite" aria-atomic="true"
         style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);">
    </div>

    {{-- Decorative background blobs --}}
    <div class="blob" style="width:420px;height:420px;background:#8BABF1;top:-80px;left:-120px;"></div>
    <div class="blob" style="width:340px;height:340px;background:#FAAF90;bottom:-60px;right:-80px;"></div>
    <div class="blob" style="width:260px;height:260px;background:#FCC9B5;top:40%;left:55%;"></div>

    {{-- ════════════════════════════════════════════
         HEADER MINIMAL
    ════════════════════════════════════════════ --}}
    <header
        role="banner"
        aria-label="Header VOXORA"
        class="relative z-10 flex items-center justify-center py-5"
        style="background: var(--bg-header);">
        <a href="{{ url('/') }}" aria-label="VOXORA – halaman utama"
           class="flex items-center gap-2">
            <span aria-hidden="true"
                  class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold text-sm"
                  style="background:#000;">V</span>
            <span class="font-serif text-2xl font-bold text-black tracking-tight">VOXORA</span>
        </a>
    </header>

    {{-- ════════════════════════════════════════════
         MAIN – LOGIN CARD
    ════════════════════════════════════════════ --}}
    <main
        id="main-content"
        role="main"
        tabindex="-1"
        aria-label="Halaman masuk VOXORA"
        class="relative z-10 flex-1 flex items-center justify-center px-4 py-10">

        <div class="login-card w-full max-w-md p-8 md:p-10">

            {{-- Heading --}}
            <div class="text-center mb-8">
                <h1 class="font-serif text-3xl font-bold text-black mb-2">
                    Selamat Datang
                </h1>
                <p class="text-sm text-slate-600">
                    Masuk untuk mengakses platform remediasi dokumen VOXORA.
                </p>
            </div>

            {{-- ── Error global ── --}}
            @if ($errors->any())
                <div
                    role="alert"
                    aria-live="assertive"
                    class="mb-6 flex items-start gap-3 rounded-xl border border-red-700
                           bg-red-50 px-4 py-3 text-red-800 text-sm">
                    <svg aria-hidden="true" class="w-5 h-5 mt-0.5 flex-shrink-0 text-red-600"
                         fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2
                              0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1
                              0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <ul class="space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ── Session status (setelah logout/redirect) ── --}}
            @if (session('status'))
                <div role="status" aria-live="polite"
                     class="mb-6 rounded-xl border border-green-700 bg-green-50
                            px-4 py-3 text-green-800 text-sm font-medium">
                    {{ session('status') }}
                </div>
            @endif

            {{-- ══════════════════════════
                 FORM LOGIN
            ══════════════════════════ --}}
            <form
                id="login-form"
                method="POST"
                action="{{ route('login.post') }}"
                novalidate
                aria-label="Formulir masuk">
                @csrf

                {{-- ── Email ── --}}
                <div class="mb-5">
                    <label
                        for="email"
                        class="block text-sm font-semibold text-black mb-1.5">
                        Alamat Email
                        <span aria-hidden="true" class="text-red-600 ml-0.5">*</span>
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-input {{ $errors->has('email') ? 'error' : '' }}"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                        autofocus
                        aria-required="true"
                        aria-describedby="{{ $errors->has('email') ? 'email-error' : 'email-hint' }}"
                        placeholder="contoh@email.com">

                    <p id="email-hint" class="mt-1 text-xs text-slate-500">
                        Gunakan email yang terdaftar di VOXORA.
                    </p>

                    @error('email')
                        <p id="email-error" role="alert" aria-live="assertive"
                           class="mt-1.5 text-xs text-red-700 font-medium flex items-center gap-1">
                            <svg aria-hidden="true" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                      d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0
                                      11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0
                                      102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- ── Password ── --}}
                <div class="mb-6">
                    <label
                        for="password"
                        class="block text-sm font-semibold text-black mb-1.5">
                        Kata Sandi
                        <span aria-hidden="true" class="text-red-600 ml-0.5">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input pr-12 {{ $errors->has('password') ? 'error' : '' }}"
                            required
                            autocomplete="current-password"
                            aria-required="true"
                            aria-describedby="{{ $errors->has('password') ? 'password-error' : '' }}"
                            placeholder="Masukkan kata sandi">

                        {{-- Toggle tampilkan/sembunyikan password --}}
                        <button
                            type="button"
                            class="pw-toggle"
                            aria-label="Tampilkan kata sandi"
                            aria-pressed="false"
                            onclick="togglePassword(this)">
                            {{-- Ikon mata --}}
                            <svg id="icon-eye" aria-hidden="true" class="w-5 h-5" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478
                                      0 8.268 2.943 9.542 7-1.274 4.057-5.064
                                      7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            {{-- Ikon mata dicoret (tersembunyi awal) --}}
                            <svg id="icon-eye-off" aria-hidden="true" class="w-5 h-5 hidden"
                                 fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478
                                      0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3
                                      3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88
                                      9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59
                                      3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268
                                      2.943 9.543 7a10.025 10.025 0 01-4.132
                                      5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>

                    @error('password')
                        <p id="password-error" role="alert" aria-live="assertive"
                           class="mt-1.5 text-xs text-red-700 font-medium flex items-center gap-1">
                            <svg aria-hidden="true" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                      d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0
                                      11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0
                                      102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- ── Remember me ── --}}
                <div class="flex items-center gap-2.5 mb-6">
                    <input
                        type="checkbox"
                        id="remember"
                        name="remember"
                        class="w-4 h-4 accent-black rounded cursor-pointer"
                        aria-describedby="remember-hint">
                    <label for="remember" class="text-sm text-black cursor-pointer select-none">
                        Ingat saya di perangkat ini
                    </label>
                    <p id="remember-hint" class="sr-only">
                        Jika dicentang, Anda tidak perlu masuk lagi pada kunjungan berikutnya di perangkat ini.
                    </p>
                </div>

                {{-- ── Submit ── --}}
                <button
                    type="submit"
                    class="btn-primary"
                    aria-label="Masuk ke akun VOXORA Anda">
                    Masuk ke VOXORA
                </button>
            </form>

            {{-- ── Divider ── --}}
            <div class="flex items-center gap-3 my-6" aria-hidden="true">
                <div class="flex-1 border-t border-black/10"></div>
                <span class="text-xs text-slate-400 font-medium">atau</span>
                <div class="flex-1 border-t border-black/10"></div>
            </div>

            {{-- ── Register Link ── --}}
            <div class="text-center">
                <p class="text-sm text-slate-600 mb-2">
                    Belum punya akun?
                </p>
                <a href="{{ route('register') }}" 
                   class="inline-flex items-center justify-center px-4 py-2 border border-2 border-black bg-white text-black font-medium rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black transition-colors"
                   aria-label="Daftar akun VOXORA baru">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v-3m0 3h.01M9 16h.01M15 12H9m6 0v.01M12 15h.01M12 9h.01M12 12h.01M12 18h.01M16 12h-4m-4 0v4m0-4h4" />
                    </svg>
                    Daftar Sekarang
                </a>
            </div>

            {{-- ── Info aksesibilitas ── --}}
            <p class="text-center text-xs text-slate-500 leading-relaxed mt-6">
                Platform ini dirancang ramah screen reader.<br>
                Gunakan <kbd class="px-1 py-0.5 bg-slate-100 rounded text-slate-700 font-mono">Tab</kbd>
                untuk navigasi keyboard.
            </p>
        </div>
    </main>

    {{-- ════════════════════════════════════════════
         FOOTER MINIMAL
    ════════════════════════════════════════════ --}}
    <footer
        role="contentinfo"
        class="relative z-10 py-4 text-center text-xs text-slate-600"
        style="background: var(--bg-sidebar);">
        VOXORA &copy; {{ date('Y') }} &mdash; Platform Aksesibilitas Dokumen untuk Tunanetra
    </footer>

    <script>
    /* ── Toggle password visibility ── */
    function togglePassword(btn) {
        const input  = document.getElementById('password');
        const eyeOn  = document.getElementById('icon-eye');
        const eyeOff = document.getElementById('icon-eye-off');
        const isHidden = input.type === 'password';

        input.type = isHidden ? 'text' : 'password';
        btn.setAttribute('aria-pressed', String(isHidden));
        btn.setAttribute('aria-label', isHidden ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        eyeOn.classList.toggle('hidden', isHidden);
        eyeOff.classList.toggle('hidden', !isHidden);

        // Umumkan ke NVDA
        const announcer = document.getElementById('aria-announcer');
        announcer.textContent = '';
        requestAnimationFrame(() => {
            announcer.textContent = isHidden
                ? 'Kata sandi ditampilkan.'
                : 'Kata sandi disembunyikan.';
        });
    }

    /* ── Fokus otomatis ke field error ── */
    window.addEventListener('DOMContentLoaded', () => {
        const firstError = document.querySelector('.form-input.error');
        if (firstError) {
            firstError.focus();
        }
    });
    </script>

</body>
</html>