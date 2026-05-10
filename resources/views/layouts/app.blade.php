<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{--
    ┌─────────────────────────────────────────────────────────────┐
    │  VOXORA – Base Layout                                        │
    │  Palet: Pastel Contrasting Palette 1                         │
    │    #FAAF90 · #FCC9B5 · #D9E4FF · #B3C7F7 · #8BABF1          │
    │  Semua kontras teks hitam telah diverifikasi WCAG 2.1 AA+    │
    └─────────────────────────────────────────────────────────────┘
    --}}

    <title>@yield('title', 'VOXORA') – Platform Aksesibilitas Dokumen</title>

    {{-- Tailwind CDN – ganti dengan Vite build di produksi --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Fonts: Fraunces (display) + Plus Jakarta Sans (body) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,600;0,700;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        serif: ['Fraunces', 'Georgia', 'serif'],
                        sans:  ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        /* ── Pastel Contrasting Palette 1 ── */
                        /* Contrast vs #000: 11.60:1 */ coral:   '#FAAF90',
                        /* Contrast vs #000: 14.16:1 */ peach:   '#FCC9B5',
                        /* Contrast vs #000: 16.49:1 */ lavender:'#D9E4FF',
                        /* Contrast vs #000: 12.43:1 */ sky:     '#B3C7F7',
                        /* Contrast vs #000:  9.19:1 */ steel:   '#8BABF1',
                    },
                    keyframes: {
                        fadeSlide: {
                            '0%':   { opacity: '0', transform: 'translateY(8px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                    },
                    animation: {
                        fadeSlide: 'fadeSlide .35s ease forwards',
                    },
                }
            }
        }
    </script>

    <style>
        /* ══════════════════════════════════════════════════
           CSS Custom Properties (tema global)
        ══════════════════════════════════════════════════ */
        :root {
            --bg-main:      #D9E4FF;   /* lavender  – area konten */
            --bg-sidebar:   #B3C7F7;   /* sky        – sidebar */
            --bg-header:    #8BABF1;   /* steel      – header bar */
            --accent-warm:  #FAAF90;   /* coral      – aksen / hover aktif */
            --accent-soft:  #FCC9B5;   /* peach      – aksen sekunder */
            --text-main:    #000000;   /* hitam murni – kontras tertinggi */
            --text-muted:   #1e293b;   /* slate-800  – teks sekunder */
            --focus-ring:   #000000;   /* hitam      – indikator fokus keyboard */
            --border-main:  #1e3a5f;   /* navy       – border elemen */
        }

        /* ══════════════════════════════════════════════════
           Reset & Base
        ══════════════════════════════════════════════════ */
        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-main);
            color: var(--text-main);
        }

        /* ══════════════════════════════════════════════════
           Skip Navigation Link (WCAG 2.4.1)
        ══════════════════════════════════════════════════ */
        .skip-link {
            position: absolute;
            top: -100vh;          /* jauh di luar viewport */
            left: 1rem;
            z-index: 9999;
            padding: .6rem 1.25rem;
            background: var(--text-main);
            color: var(--bg-main);
            font-weight: 700;
            font-size: .875rem;
            border-radius: 0 0 .5rem .5rem;
            text-decoration: none;
            transition: top .15s ease;
        }
        .skip-link:focus {
            top: 0;               /* muncul saat Tab pertama ditekan */
            outline: 3px solid var(--accent-warm);
            outline-offset: 2px;
        }

        /* ══════════════════════════════════════════════════
           Focus Ring Global – WCAG 2.4.7
           Semua elemen interaktif wajib punya indikator jelas
        ══════════════════════════════════════════════════ */
        :focus-visible {
            outline: 3px solid var(--focus-ring);
            outline-offset: 3px;
            border-radius: 4px;
        }
        /* Hapus outline default browser hanya untuk mouse, bukan keyboard */
        :focus:not(:focus-visible) { outline: none; }

        /* ══════════════════════════════════════════════════
           Sidebar Navigation
        ══════════════════════════════════════════════════ */
        .nav-link {
            display:     flex;
            align-items: center;
            gap:         .75rem;
            padding:     .75rem 1.25rem;
            font-size:   .9rem;
            font-weight: 500;
            color:       var(--text-main);
            border-left: 4px solid transparent;
            border-radius: 0 .75rem .75rem 0;
            text-decoration: none;
            transition: background .18s ease, border-color .18s ease;
            position: relative;
        }
        .nav-link:hover {
            background:  rgba(0,0,0,.07);
            border-left-color: var(--border-main);
        }
        /* ── Active state – aria-current="page" ── */
        .nav-link[aria-current="page"],
        .nav-link.active {
            background:      var(--accent-warm);   /* #FAAF90 – 11.60:1 vs black ✓ */
            border-left:     4px solid var(--text-main);
            font-weight:     700;
        }
        .nav-link[aria-current="page"]:focus-visible {
            outline-color: var(--border-main);
        }

        /* ══════════════════════════════════════════════════
           Profile Dropdown
        ══════════════════════════════════════════════════ */
        #profile-dropdown {
            transform-origin: top right;
            transition: opacity .15s ease, transform .15s ease;
        }
        #profile-dropdown.hidden {
            opacity: 0;
            transform: scale(.96);
            pointer-events: none;
        }
        #profile-dropdown:not(.hidden) {
            opacity: 1;
            transform: scale(1);
        }

        /* ══════════════════════════════════════════════════
           Scrollbar custom (Webkit)
        ══════════════════════════════════════════════════ */
        ::-webkit-scrollbar { width: 7px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb {
            background: var(--bg-header);
            border-radius: 4px;
        }

        /* ══════════════════════════════════════════════════
           Utility
        ══════════════════════════════════════════════════ */
        .sr-only {
            position: absolute; width: 1px; height: 1px;
            padding: 0; margin: -1px; overflow: hidden;
            clip: rect(0,0,0,0); white-space: nowrap; border: 0;
        }
    </style>

    {{-- Per-halaman: tambahan <head> --}}
    @stack('head')
</head>

<body class="h-full flex flex-col antialiased">

    {{-- ════════════════════════════════════════════
         SKIP NAVIGATION  (WCAG 2.4.1 – Level A)
    ════════════════════════════════════════════ --}}
    <a href="#main-content" class="skip-link">
        Lewati navigasi, langsung ke konten utama
    </a>

    {{-- ════════════════════════════════════════════
         ARIA LIVE REGION  (global announcer)
         Dibaca NVDA secara otomatis saat diisi JS
    ════════════════════════════════════════════ --}}
    <div
        id="aria-announcer"
        role="status"
        aria-live="polite"
        aria-atomic="true"
        class="sr-only">
    </div>

    {{-- ════════════════════════════════════════════
         HEADER  – role="banner" (Landmark L1)
    ════════════════════════════════════════════ --}}
    <header
        role="banner"
        aria-label="Header aplikasi VOXORA"
        class="flex items-center justify-between px-6 py-3 z-30 flex-shrink-0 shadow-sm"
        style="background: var(--bg-header);">

        {{-- ── Logo / Brand ── --}}
        {{-- Admin → /admin, User → /upload --}}
        <a
            href="{{ auth()->user()?->is_admin ? route('admin.index') : route('upload.index') }}"
            class="group flex items-center gap-2 rounded-md"
            aria-label="VOXORA – kembali ke halaman utama">
            <span
                aria-hidden="true"
                class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold text-sm"
                style="background: var(--text-main);">
                V
            </span>
            <span class="font-serif text-2xl font-bold tracking-tight text-black leading-none">
                VOXORA
            </span>
        </a>

        {{-- ── User area ── --}}
        <div class="relative flex items-center gap-3">

            {{-- Badge role admin --}}
            @if(auth()->user()?->is_admin)
            <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                         text-[10px] font-bold uppercase tracking-wider text-white"
                  style="background:#000;"
                  aria-label="Role: Administrator">
                <svg aria-hidden="true" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                Admin
            </span>
            @endif

            <span
                class="text-sm font-semibold text-black hidden sm:inline"
                aria-hidden="true">
                {{ auth()->user()?->name ?? 'Nama User' }}
            </span>

            {{-- Tombol profil --}}
            <button
                id="profile-btn"
                type="button"
                aria-haspopup="true"
                aria-expanded="false"
                aria-controls="profile-dropdown"
                aria-label="Menu profil untuk {{ auth()->user()?->name ?? 'Nama User' }}"
                class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm
                       transition-opacity hover:opacity-90"
                style="background: var(--text-main);">
                {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
            </button>

            {{-- Dropdown profil --}}
            <div
                id="profile-dropdown"
                role="menu"
                aria-label="Opsi profil pengguna"
                class="hidden absolute right-0 top-12 w-52 rounded-xl shadow-xl border border-black/10 overflow-hidden z-50"
                style="background: var(--bg-main);">

                <div class="px-4 py-3 border-b border-black/10">
                    <p class="text-xs text-slate-500">Masuk sebagai</p>
                    <p class="text-sm font-semibold text-black truncate">
                        {{ auth()->user()?->name ?? 'Nama User' }}
                    </p>
                    <p class="text-[10px] text-slate-400 mt-0.5">
                        {{ auth()->user()?->is_admin ? 'Administrator' : 'Pengguna' }}
                    </p>
                </div>

                <a
                    role="menuitem"
                    href="{{ auth()->user()?->is_admin ? route('admin.profile') : route('profile.show') }}"
                    class="flex items-center gap-2 px-4 py-3 text-sm text-black hover:bg-sky-200 transition-colors">
                    <svg aria-hidden="true" focusable="false" class="w-4 h-4 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Profil Saya
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        role="menuitem"
                        type="submit"
                        class="w-full flex items-center gap-2 px-4 py-3 text-sm text-red-800 hover:bg-red-50 transition-colors">
                        <svg aria-hidden="true" focusable="false" class="w-4 h-4 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- ════════════════════════════════════════════
         BODY WRAPPER  (sidebar + main)
    ════════════════════════════════════════════ --}}
    <div class="flex flex-1 overflow-hidden">

        {{-- ══════════════════════════════════════════════════════════
             SIDEBAR — tampilan berbeda untuk ADMIN vs USER
        ══════════════════════════════════════════════════════════ --}}
        @if(auth()->user()?->is_admin)

        {{-- ─────────────────────────────
             SIDEBAR ADMIN
        ───────────────────────────── --}}
        <nav
            id="sidebar"
            role="navigation"
            aria-label="Navigasi panel administrator VOXORA"
            class="w-56 flex-shrink-0 flex flex-col py-5 gap-0.5 overflow-y-auto"
            style="background: var(--bg-sidebar);">

            <h2 class="sr-only">Menu Administrator VOXORA</h2>

            {{-- ── GRUP: MANAJEMEN ── --}}
            <p class="px-5 pb-1.5 pt-2 text-[10px] font-bold uppercase tracking-widest
                       text-slate-500 select-none" aria-hidden="true">
                Manajemen
            </p>

            {{-- Dashboard Admin --}}
            <a href="{{ route('admin.index') }}"
               class="nav-link {{ request()->routeIs('admin.index') ? 'active' : '' }}"
               aria-label="Dashboard – ringkasan statistik platform"
               @if(request()->routeIs('admin.index')) aria-current="page" @endif
                <svg aria-hidden="true" focusable="false" class="w-5 h-5 flex-shrink-0"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1
                          0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1
                          1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1
                          1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0
                          011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
                </svg>
                Dashboard
            </a>

            {{-- Kelola Pengguna --}}
            <a href="{{ route('admin.users') }}"
               class="nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}"
               aria-label="Kelola Pengguna – daftar dan hapus akun pengguna"
               @if(request()->routeIs('admin.users')) aria-current="page" @endif
                <svg aria-hidden="true" focusable="false" class="w-5 h-5 flex-shrink-0"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0
                          0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                Kelola Pengguna
            </a>

            {{-- Kelola Dokumen --}}
            <a href="{{ route('admin.docs') }}"
               class="nav-link {{ request()->routeIs('admin.docs') ? 'active' : '' }}"
               aria-label="Kelola Dokumen – pantau semua dokumen yang diunggah"
               @if(request()->routeIs('admin.docs')) aria-current="page" @endif
                <svg aria-hidden="true" focusable="false" class="w-5 h-5 flex-shrink-0"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586
                          a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0
                          01-2 2z"/>
                </svg>
                Kelola Dokumen
            </a>

            {{-- ── Divider ── --}}
            <div class="mx-5 my-3 border-t border-black/10" aria-hidden="true"></div>

            {{-- ── GRUP: PERANGKAT ── --}}
            <p class="px-5 pb-1.5 text-[10px] font-bold uppercase tracking-widest
                       text-slate-500 select-none" aria-hidden="true">
                Perangkat
            </p>

            {{-- Manajemen EduBraille --}}
            <a href="{{ route('admin.edubraille') }}"
               class="nav-link {{ request()->routeIs('admin.edubraille') ? 'active' : '' }}"
               aria-label="Manajemen EduBraille – kelola endpoint dan perangkat braille"
               @if(request()->routeIs('admin.edubraille')) aria-current="page" @endif
                <svg aria-hidden="true" focusable="false" class="w-5 h-5 flex-shrink-0"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0
                          0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
                </svg>
                Manajemen EduBraille
            </a>

            {{-- ── Divider ── --}}
            <div class="mx-5 my-3 border-t border-black/10" aria-hidden="true"></div>

            {{-- ── GRUP: AKUN ── --}}
            <p class="px-5 pb-1.5 text-[10px] font-bold uppercase tracking-widest
                       text-slate-500 select-none" aria-hidden="true">
                Akun
            </p>

            <a href="{{ route('admin.profile') }}"
               class="nav-link {{ request()->routeIs('admin.profile') ? 'active' : '' }}"
               aria-label="Profil – kelola informasi akun administrator"
               @if(request()->routeIs('admin.profile')) aria-current="page" @endif
                <svg aria-hidden="true" focusable="false" class="w-5 h-5 flex-shrink-0"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0
                          00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Profil
            </a>

            {{-- Info versi --}}
            <div class="mt-auto px-5 pt-4 pb-2">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-2 h-2 rounded-full bg-green-500" aria-hidden="true"></div>
                    <p class="text-[10px] text-slate-500 font-medium truncate">
                        {{ auth()->user()?->name ?? '' }}
                    </p>
                </div>
                <p class="text-[10px] text-slate-400">
                    VOXORA v1.0 &mdash; Panel Admin
                </p>
            </div>

        </nav>

        @else

        {{-- ─────────────────────────────
             SIDEBAR USER BIASA
        ───────────────────────────── --}}
        <nav
            id="sidebar"
            role="navigation"
            aria-label="Navigasi halaman utama VOXORA"
            class="w-56 flex-shrink-0 flex flex-col py-5 gap-0.5 overflow-y-auto"
            style="background: var(--bg-sidebar);">

            <h2 class="sr-only">Menu Navigasi VOXORA</h2>

            {{-- ── GRUP: DOKUMEN ── --}}
            <p class="px-5 pb-1.5 pt-2 text-[10px] font-bold uppercase tracking-widest
                       text-slate-500 select-none" aria-hidden="true">
                Dokumen
            </p>

            <a href="{{ route('upload.index') }}"
               class="nav-link {{ request()->routeIs('upload.*') ? 'active' : '' }}"
               aria-label="Unggah Dokumen – upload file PDF atau DOCX untuk diremediasi"
               @if(request()->routeIs('upload.*')) aria-current="page" @endif
                <svg aria-hidden="true" focusable="false" class="w-5 h-5 flex-shrink-0"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Unggah Dokumen
            </a>

            <a href="{{ route('pustaka.index') }}"
               class="nav-link {{ request()->routeIs('pustaka.*') ? 'active' : '' }}"
               aria-label="Pustaka – riwayat dokumen yang telah diunggah dan diremediasi"
               @if(request()->routeIs('pustaka.*')) aria-current="page" @endif
                <svg aria-hidden="true" focusable="false" class="w-5 h-5 flex-shrink-0"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168
                          5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477
                          4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0
                          3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5
                          18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                Pustaka
            </a>

            {{-- ── Divider ── --}}
            <div class="mx-5 my-3 border-t border-black/10" aria-hidden="true"></div>

            {{-- ── GRUP: ASISTEN ── --}}
            <p class="px-5 pb-1.5 text-[10px] font-bold uppercase tracking-widest
                       text-slate-500 select-none" aria-hidden="true">
                Asisten
            </p>

            <a href="{{ route('tanya.index') }}"
               class="nav-link {{ request()->routeIs('tanya.*') ? 'active' : '' }}"
               aria-label="Tanya Bot – tanya jawab dokumen dengan asisten AI bersuara"
               @if(request()->routeIs('tanya.*')) aria-current="page" @endif
                <svg aria-hidden="true" focusable="false" class="w-5 h-5 flex-shrink-0"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6
                          a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
                Tanya Bot
            </a>

            <a href="{{ route('braille.index') }}"
               class="nav-link {{ request()->routeIs('braille.*') ? 'active' : '' }}"
               aria-label="Kirim ke EduBraille – kirim hasil remediasi ke perangkat braille"
               @if(request()->routeIs('braille.*')) aria-current="page" @endif
                <svg aria-hidden="true" focusable="false" class="w-5 h-5 flex-shrink-0"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0
                          0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
                </svg>
                Kirim ke EduBraille
            </a>

            {{-- ── Divider ── --}}
            <div class="mx-5 my-3 border-t border-black/10" aria-hidden="true"></div>

            {{-- ── GRUP: AKUN ── --}}
            <p class="px-5 pb-1.5 text-[10px] font-bold uppercase tracking-widest
                       text-slate-500 select-none" aria-hidden="true">
                Akun
            </p>

            <a href="{{ route('profile.show') }}"
               class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}"
               aria-label="Profil – kelola informasi akun Anda"
               @if(request()->routeIs('profile.*')) aria-current="page" @endif
                <svg aria-hidden="true" focusable="false" class="w-5 h-5 flex-shrink-0"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0
                          00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Profil
            </a>

            {{-- Info versi --}}
            <div class="mt-auto px-5 pt-4 pb-2">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-2 h-2 rounded-full bg-green-500" aria-hidden="true"></div>
                    <p class="text-[10px] text-slate-500 font-medium truncate">
                        {{ auth()->user()?->name ?? '' }}
                    </p>
                </div>
                <p class="text-[10px] text-slate-400">
                    VOXORA v1.0 &mdash; Aksesibilitas Dokumen
                </p>
            </div>

        </nav>

        @endif

        {{-- ──────────────────────────────────────
             MAIN CONTENT  – role="main" (Landmark)
        ────────────────────────────────────────── --}}
        <main
            id="main-content"
            role="main"
            tabindex="-1"
            aria-label="@yield('main-label', 'Konten halaman')"
            class="flex-1 overflow-y-auto flex flex-col"
            style="background: var(--bg-main);">

            {{-- ── Flash messages (global) ── --}}
            @if (session('success'))
                <div
                    role="alert"
                    aria-live="assertive"
                    class="mx-6 mt-5 flex items-start gap-3 rounded-xl border border-green-800
                           bg-green-50 px-5 py-4 text-green-900 text-sm font-medium animate-fadeSlide">
                    <svg aria-hidden="true" class="w-5 h-5 mt-0.5 flex-shrink-0 text-green-700"
                         fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9
                              10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                              clip-rule="evenodd"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div
                    role="alert"
                    aria-live="assertive"
                    class="mx-6 mt-5 flex items-start gap-3 rounded-xl border border-red-800
                           bg-red-50 px-5 py-4 text-red-900 text-sm font-medium animate-fadeSlide">
                    <svg aria-hidden="true" class="w-5 h-5 mt-0.5 flex-shrink-0 text-red-700"
                         fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1
                              0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                              clip-rule="evenodd"/>
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            {{-- ── Slot konten per-halaman ── --}}
            <div class="flex-1 p-6 md:p-8 animate-fadeSlide">
                @yield('content')
            </div>
        </main>
    </div>

    {{-- ════════════════════════════════════════════
         FOOTER  – role="contentinfo" (Landmark)
    ════════════════════════════════════════════ --}}
    <footer
        role="contentinfo"
        aria-label="Footer VOXORA"
        class="flex-shrink-0 flex items-center justify-between px-6 py-2 text-xs text-slate-600"
        style="background: var(--bg-sidebar); border-top: 1px solid rgba(0,0,0,.08);">

        <span>VOXORA &copy; {{ date('Y') }} &mdash; Platform Aksesibilitas Dokumen untuk Tunanetra</span>
    </footer>

    {{-- ════════════════════════════════════════════
         JAVASCRIPT GLOBAL
    ════════════════════════════════════════════ --}}
    <script>
    /* ── Aria Announcer (helper global) ──────────────────── */
    function announce(message, priority = 'polite') {
        const el = document.getElementById('aria-announcer');
        if (!el) return;
        el.setAttribute('aria-live', priority);
        // Reset agar NVDA membaca ulang meskipun pesan sama
        el.textContent = '';
        requestAnimationFrame(() => {
            requestAnimationFrame(() => { el.textContent = message; });
        });
    }

    /* ── Profile Dropdown ────────────────────────────────── */
    (function () {
        const btn      = document.getElementById('profile-btn');
        const dropdown = document.getElementById('profile-dropdown');
        if (!btn || !dropdown) return;

        function open() {
            dropdown.classList.remove('hidden');
            btn.setAttribute('aria-expanded', 'true');
            // Fokus ke item pertama
            const first = dropdown.querySelector('[role="menuitem"]');
            if (first) first.focus();
        }

        function close() {
            dropdown.classList.add('hidden');
            btn.setAttribute('aria-expanded', 'false');
        }

        btn.addEventListener('click', () => {
            dropdown.classList.contains('hidden') ? open() : close();
        });

        // Tutup saat klik di luar
        document.addEventListener('click', (e) => {
            if (!btn.contains(e.target) && !dropdown.contains(e.target)) close();
        });

        // Tutup dengan Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !dropdown.classList.contains('hidden')) {
                close();
                btn.focus();
            }
        });

        // Navigasi keyboard di dalam dropdown (Arrow keys)
        dropdown.addEventListener('keydown', (e) => {
            const items = [...dropdown.querySelectorAll('[role="menuitem"]')];
            const idx   = items.indexOf(document.activeElement);
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                items[(idx + 1) % items.length]?.focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                items[(idx - 1 + items.length) % items.length]?.focus();
            }
        });
    })();
    </script>

    {{-- Per-halaman: tambahan script --}}
    @stack('scripts')

</body>
</html>