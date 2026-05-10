<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Daftar – VOXORA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>tailwind.config={theme:{extend:{fontFamily:{serif:['Fraunces','serif'],sans:['Plus Jakarta Sans','sans-serif']}}}}</script>
    <style>
        :root{--bg-main:#D9E4FF;--bg-header:#8BABF1;--bg-sidebar:#B3C7F7;}
        html,body{height:100%;margin:0;font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg-main);color:#000;}
        :focus-visible{outline:3px solid #000;outline-offset:3px;border-radius:4px;}
        :focus:not(:focus-visible){outline:none;}
        .form-input{width:100%;padding:.75rem 1rem;border:2px solid rgba(0,0,0,.18);border-radius:.75rem;background:rgba(255,255,255,.75);font-family:'Plus Jakarta Sans',sans-serif;font-size:.9rem;color:#000;transition:border-color .18s,box-shadow .18s;}
        .form-input:focus{outline:none;border-color:#000;background:#fff;box-shadow:0 0 0 3px rgba(0,0,0,.1);}
        .form-input.error{border-color:#b91c1c;background:#fff5f5;}
        .login-card{background:rgba(255,255,255,.55);backdrop-filter:blur(12px);border:1.5px solid rgba(0,0,0,.1);border-radius:1.5rem;box-shadow:0 8px 40px rgba(0,0,0,.08);}
        .btn-primary{width:100%;padding:.85rem;background:#000;color:#fff;font-family:'Plus Jakarta Sans',sans-serif;font-size:.9rem;font-weight:700;border:none;border-radius:.875rem;cursor:pointer;transition:opacity .18s;}
        .btn-primary:hover{opacity:.85;}
        .blob{position:fixed;border-radius:50%;filter:blur(80px);opacity:.45;pointer-events:none;z-index:0;}
        .skip-link{position:absolute;top:-999px;left:1rem;z-index:9999;padding:.5rem 1rem;background:#000;color:var(--bg-main);font-weight:700;border-radius:0 0 .5rem .5rem;text-decoration:none;}
        .skip-link:focus{top:0;}
    </style>
</head>
<body class="h-full flex flex-col">
<a href="#register-form" class="skip-link">Lewati ke formulir pendaftaran</a>
<div id="aria-announcer" role="status" aria-live="polite" aria-atomic="true" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);"></div>
<div class="blob" style="width:400px;height:400px;background:#8BABF1;top:-80px;left:-100px;"></div>
<div class="blob" style="width:320px;height:320px;background:#FAAF90;bottom:-60px;right:-80px;"></div>

<header role="banner" class="relative z-10 flex items-center justify-center py-5" style="background:var(--bg-header);">
    <a href="{{ url('/') }}" aria-label="VOXORA – halaman utama" class="flex items-center gap-2">
        <span aria-hidden="true" class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold text-sm bg-black">V</span>
        <span class="font-serif text-2xl font-bold text-black tracking-tight">VOXORA</span>
    </a>
</header>

<main id="main-content" role="main" tabindex="-1" aria-label="Halaman pendaftaran akun VOXORA"
      class="relative z-10 flex-1 flex items-center justify-center px-4 py-8">
    <div class="login-card w-full max-w-md p-8 md:p-10">
        <div class="text-center mb-7">
            <h1 class="font-serif text-3xl font-bold text-black mb-1">Buat Akun</h1>
            <p class="text-sm text-slate-600">Daftar untuk mulai menggunakan VOXORA.</p>
        </div>

        @if($errors->any())
        <div role="alert" aria-live="assertive"
             class="mb-5 flex items-start gap-3 rounded-xl border border-red-700 bg-red-50 px-4 py-3 text-red-800 text-sm">
            <svg aria-hidden="true" class="w-5 h-5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <ul class="space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form id="register-form" method="POST" action="{{ route('register.post') }}" novalidate aria-label="Formulir pendaftaran akun">
            @csrf

            <div class="mb-4">
                <label for="name" class="block text-sm font-semibold text-black mb-1.5">
                    Nama Lengkap <span aria-hidden="true" class="text-red-600">*</span>
                </label>
                <input type="text" id="name" name="name"
                       class="form-input {{ $errors->has('name') ? 'error' : '' }}"
                       value="{{ old('name') }}" required autofocus autocomplete="name"
                       aria-required="true" aria-describedby="{{ $errors->has('name') ? 'name-err' : '' }}"
                       placeholder="Nama lengkap Anda">
                @error('name')<p id="name-err" role="alert" class="mt-1 text-xs text-red-700 font-medium">{{ $message }}</p>@enderror
            </div>

            <div class="mb-4">
                <label for="email" class="block text-sm font-semibold text-black mb-1.5">
                    Alamat Email <span aria-hidden="true" class="text-red-600">*</span>
                </label>
                <input type="email" id="email" name="email"
                       class="form-input {{ $errors->has('email') ? 'error' : '' }}"
                       value="{{ old('email') }}" required autocomplete="email"
                       aria-required="true" placeholder="contoh@email.com">
                @error('email')<p role="alert" class="mt-1 text-xs text-red-700 font-medium">{{ $message }}</p>@enderror
            </div>

            <div class="mb-4">
                <label for="password" class="block text-sm font-semibold text-black mb-1.5">
                    Kata Sandi <span aria-hidden="true" class="text-red-600">*</span>
                </label>
                <input type="password" id="password" name="password"
                       class="form-input {{ $errors->has('password') ? 'error' : '' }}"
                       required autocomplete="new-password"
                       aria-required="true" aria-describedby="pw-hint"
                       placeholder="Minimal 8 karakter">
                <p id="pw-hint" class="mt-1 text-xs text-slate-500">Minimal 8 karakter.</p>
                @error('password')<p role="alert" class="mt-1 text-xs text-red-700 font-medium">{{ $message }}</p>@enderror
            </div>

            <div class="mb-6">
                <label for="password_confirmation" class="block text-sm font-semibold text-black mb-1.5">
                    Konfirmasi Kata Sandi <span aria-hidden="true" class="text-red-600">*</span>
                </label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                       class="form-input"
                       required autocomplete="new-password"
                       aria-required="true" placeholder="Ulangi kata sandi">
            </div>

            <button type="submit" class="btn-primary" aria-label="Buat akun VOXORA baru">
                Buat Akun
            </button>
        </form>

        <p class="text-center text-sm text-slate-600 mt-5">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="font-semibold underline underline-offset-2 text-black hover:opacity-70 focus:outline-none focus:ring-2 focus:ring-black rounded">
                Masuk di sini
            </a>
        </p>
    </div>
</main>

<footer role="contentinfo" class="relative z-10 py-3 text-center text-xs text-slate-600" style="background:var(--bg-sidebar);">
    VOXORA &copy; {{ date('Y') }} &mdash; Platform Aksesibilitas Dokumen untuk Tunanetra
</footer>
</body>
</html>