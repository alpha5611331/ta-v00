<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOXORA – Platform Aksesibilitas Dokumen untuk Tunanetra</title>
    <meta name="description" content="VOXORA membantu tunanetra membaca dokumen PDF dan Word dengan mengubah konten STEM menjadi teks natural yang ramah screen reader dan braille.">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,600;0,700;1,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: { extend: {
                fontFamily: {
                    serif: ['Fraunces','Georgia','serif'],
                    sans:  ['Plus Jakarta Sans','sans-serif'],
                },
                colors: {
                    coral:    '#FAAF90',
                    peach:    '#FCC9B5',
                    lavender: '#D9E4FF',
                    sky:      '#B3C7F7',
                    steel:    '#8BABF1',
                },
            }}
        }
    </script>

    <style>
        :root {
            --bg-main:    #D9E4FF;
            --bg-header:  #8BABF1;
            --bg-sidebar: #B3C7F7;
            --coral:      #FAAF90;
            --peach:      #FCC9B5;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-main);
            color: #000;
        }

        /* ── Aksesibilitas ── */
        :focus-visible { outline: 3px solid #000; outline-offset: 3px; border-radius: 4px; }
        :focus:not(:focus-visible) { outline: none; }
        .skip-link {
            position: absolute; top: -999px; left: 1rem; z-index: 9999;
            padding: .5rem 1.25rem; background: #000; color: var(--bg-main);
            font-weight: 700; font-size: .875rem;
            border-radius: 0 0 .5rem .5rem; text-decoration: none;
        }
        .skip-link:focus { top: 0; }

        /* ── Blob dekoratif ── */
        .blob {
            position: absolute; border-radius: 50%;
            filter: blur(90px); opacity: .55; pointer-events: none;
        }

        /* ── Kartu fitur ── */
        .feature-card {
            background: rgba(255,255,255,.55);
            backdrop-filter: blur(12px);
            border: 1.5px solid rgba(0,0,0,.08);
            border-radius: 1.25rem;
            padding: 1.75rem 1.5rem;
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 40px rgba(0,0,0,.09);
            border-color: rgba(0,0,0,.16);
        }

        /* ── Step card ── */
        .step-card {
            background: rgba(255,255,255,.45);
            backdrop-filter: blur(10px);
            border: 1.5px solid rgba(0,0,0,.07);
            border-radius: 1.25rem;
            padding: 1.5rem;
        }

        /* ── Tombol CTA utama ── */
        .btn-primary {
            display: inline-flex; align-items: center; gap: .6rem;
            padding: .875rem 2rem;
            background: #000; color: #fff;
            font-weight: 700; font-size: .9rem;
            border-radius: .875rem; text-decoration: none;
            transition: opacity .18s ease, transform .12s ease;
        }
        .btn-primary:hover   { opacity: .82; }
        .btn-primary:active  { transform: scale(.97); }

        .btn-secondary {
            display: inline-flex; align-items: center; gap: .6rem;
            padding: .875rem 2rem;
            background: transparent; color: #000;
            font-weight: 700; font-size: .9rem;
            border: 2px solid rgba(0,0,0,.25);
            border-radius: .875rem; text-decoration: none;
            transition: background .18s ease, border-color .18s ease;
        }
        .btn-secondary:hover { background: rgba(255,255,255,.5); border-color: #000; }

        /* ── Nav link ── */
        .nav-top-link {
            font-size: .875rem; font-weight: 600; color: #000;
            text-decoration: none; padding: .4rem .75rem;
            border-radius: .5rem;
            transition: background .15s ease;
        }
        .nav-top-link:hover { background: rgba(255,255,255,.45); }

        /* ── Animasi fade-in saat scroll ── */
        .reveal {
            opacity: 0; transform: translateY(24px);
            transition: opacity .55s ease, transform .55s ease;
        }
        .reveal.visible { opacity: 1; transform: none; }

        /* ── Braille unicode dekorasi ── */
        .braille-deco {
            font-size: 2.5rem; letter-spacing: .2em;
            opacity: .18; user-select: none; pointer-events: none;
        }
    </style>
</head>

<body>
<a href="#main-content" class="skip-link">Lewati ke konten utama</a>
<div id="aria-announcer" role="status" aria-live="polite" aria-atomic="true"
     style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);"></div>

{{-- ════════════════════════════════════════════════════════════
     HEADER / NAVBAR
════════════════════════════════════════════════════════════ --}}
<header role="banner" aria-label="Header navigasi VOXORA"
        class="sticky top-0 z-50 backdrop-blur-md border-b border-black/08"
        style="background: rgba(139,171,241,.85);">
    <div class="max-w-6xl mx-auto px-6 py-3 flex items-center justify-between">

        {{-- Logo --}}
        <a href="{{ url('/') }}" aria-label="VOXORA – kembali ke halaman utama"
           class="flex items-center gap-2.5">
            <span aria-hidden="true"
                  class="w-8 h-8 rounded-lg bg-black flex items-center justify-center
                         text-white font-bold text-sm">V</span>
            <span class="font-serif text-xl font-bold text-black tracking-tight">VOXORA</span>
        </a>

        {{-- Nav tengah (desktop) --}}
        <nav aria-label="Navigasi halaman" class="hidden md:flex items-center gap-1">
            <a href="#fitur"     class="nav-top-link">Fitur</a>
            <a href="#cara-kerja" class="nav-top-link">Cara Kerja</a>
            <a href="#testimoni" class="nav-top-link">Testimoni</a>
        </nav>

        {{-- CTA kanan --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('login') }}"
               class="nav-top-link hidden sm:inline-flex"
               aria-label="Masuk ke akun VOXORA">
                Masuk
            </a>
            <a href="{{ route('register') }}"
               class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-bold
                      text-white bg-black hover:opacity-80 transition-opacity
                      focus:outline-none focus:ring-4 focus:ring-black focus:ring-offset-2"
               aria-label="Daftar akun VOXORA gratis">
                Mulai Gratis
            </a>
        </div>
    </div>
</header>

<main id="main-content" tabindex="-1" role="main">

    {{-- ════════════════════════════════════════════════════════════
         HERO SECTION
    ════════════════════════════════════════════════════════════ --}}
    <section aria-labelledby="hero-heading"
             class="relative min-h-[90vh] flex items-center overflow-hidden px-6 py-20">

        {{-- Blob dekoratif --}}
        <div class="blob w-[520px] h-[520px] top-[-100px] left-[-140px]"
             style="background:var(--bg-header);" aria-hidden="true"></div>
        <div class="blob w-[400px] h-[400px] bottom-[-80px] right-[-100px]"
             style="background:var(--coral);" aria-hidden="true"></div>
        <div class="blob w-[280px] h-[280px] top-[35%] left-[55%]"
             style="background:var(--peach);" aria-hidden="true"></div>

        <div class="relative z-10 max-w-6xl mx-auto w-full grid md:grid-cols-2 gap-12 items-center">

            {{-- Teks hero --}}
            <div>
                {{-- Badge --}}
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold
                            mb-6 border border-black/15"
                     style="background: rgba(255,255,255,.6);">
                    <span class="w-2 h-2 rounded-full bg-green-500" aria-hidden="true"></span>
                    Platform Aksesibilitas Dokumen STEM
                </div>

                <h1 id="hero-heading"
                    class="font-serif text-5xl md:text-6xl font-bold text-black leading-[1.1] mb-6">
                    Baca Dokumen<br>
                    <em class="not-italic" style="color:#1d4ed8;">Tanpa Batas</em><br>
                    untuk Tunanetra
                </h1>

                <p class="text-lg text-slate-700 leading-relaxed mb-8 max-w-lg">
                    VOXORA mengubah dokumen PDF dan Word—termasuk rumus matematika dan
                    simbol STEM—menjadi teks natural yang dapat dibaca screen reader dan
                    dikirim ke perangkat braille EduBraille.
                </p>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="btn-primary"
                       aria-label="Mulai menggunakan VOXORA secara gratis">
                        <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Mulai Sekarang — Gratis
                    </a>
                    <a href="#cara-kerja" class="btn-secondary"
                       aria-label="Lihat cara kerja VOXORA">
                        Lihat Cara Kerja
                        <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </a>
                </div>

                {{-- Stats kecil --}}
                <div class="flex flex-wrap gap-6 mt-10" role="list" aria-label="Statistik platform">
                    @foreach([['100%','WCAG 2.1 Compliant'],['NVDA','Screen Reader Ready'],['EduBraille','Braille Display']] as [$num,$label])
                    <div role="listitem" class="text-center">
                        <p class="font-serif text-2xl font-bold text-black">{{ $num }}</p>
                        <p class="text-xs text-slate-600 mt-0.5">{{ $label }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Visual hero: mock UI --}}
            <div class="hidden md:block" aria-hidden="true">
                <div class="relative">
                    {{-- Window mock --}}
                    <div class="rounded-2xl border-2 border-black/10 overflow-hidden shadow-2xl"
                         style="background: rgba(255,255,255,.7); backdrop-filter:blur(16px);">

                        {{-- Titlebar --}}
                        <div class="flex items-center gap-1.5 px-4 py-3 border-b border-black/08"
                             style="background:var(--bg-header);">
                            <div class="w-3 h-3 rounded-full bg-black/20"></div>
                            <div class="w-3 h-3 rounded-full bg-black/15"></div>
                            <div class="w-3 h-3 rounded-full bg-black/10"></div>
                            <span class="ml-3 text-xs font-semibold text-black/70">VOXORA – Hasil Remediasi</span>
                        </div>

                        {{-- Konten mock --}}
                        <div class="p-5 space-y-3">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold text-red-800"
                                     style="background:#fee2e2;border:1px solid #fca5a5;">PDF</div>
                                <div>
                                    <p class="text-xs font-semibold text-black">Aljabar_Kelas9.pdf</p>
                                    <p class="text-[10px] text-slate-500">512 karakter · Diproses barusan</p>
                                </div>
                                <span class="ml-auto text-[10px] font-bold text-green-700 bg-green-50 px-2 py-0.5 rounded-full border border-green-200">✓ Selesai</span>
                            </div>

                            @foreach([
                                'Persamaan linier satu variabel adalah persamaan yang memuat satu variabel dengan pangkat satu.',
                                'Bentuk umum: a x ditambah b sama dengan nol, di mana a tidak sama dengan nol.',
                                'Contoh soal: Tentukan nilai x pada persamaan dua x ditambah enam sama dengan nol.',
                                'Penyelesaian: dua x sama dengan negatif enam, maka x sama dengan negatif tiga.',
                            ] as $i => $line)
                            <div class="flex gap-2.5 items-start">
                                <span class="flex-shrink-0 w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-bold text-black mt-0.5"
                                      style="background:var(--bg-sidebar);">{{ $i+1 }}</span>
                                <p class="text-xs text-black leading-relaxed">{{ $line }}</p>
                            </div>
                            @endforeach

                            {{-- Tombol aksi mock --}}
                            <div class="flex gap-2 pt-2">
                                <div class="px-3 py-1.5 rounded-lg text-[10px] font-bold text-white"
                                     style="background:#15803d;">Ekspor ke Word</div>
                                <div class="px-3 py-1.5 rounded-lg text-[10px] font-bold text-white"
                                     style="background:#1d4ed8;">Tanya Dokumen</div>
                                <div class="px-3 py-1.5 rounded-lg text-[10px] font-bold text-white"
                                     style="background:#c2410c;">EduBraille</div>
                            </div>
                        </div>
                    </div>

                    {{-- Floating badge braille --}}
                    <div class="absolute -bottom-4 -left-6 px-4 py-2.5 rounded-xl shadow-lg border border-black/08"
                         style="background:rgba(255,255,255,.85);backdrop-filter:blur(8px);">
                        <p class="text-[10px] text-slate-500 mb-0.5">Unicode Braille</p>
                        <p class="font-mono text-base tracking-widest text-black">⠁⠃⠉⠙⠑⠋⠛</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ════════════════════════════════════════════════════════════
         FITUR UTAMA
    ════════════════════════════════════════════════════════════ --}}
    <section id="fitur" aria-labelledby="fitur-heading"
             class="relative py-20 px-6 overflow-hidden">
        <div class="blob w-[350px] h-[350px] top-0 right-[-80px]"
             style="background:var(--bg-sidebar);opacity:.4;" aria-hidden="true"></div>

        <div class="max-w-6xl mx-auto relative z-10">
            <div class="text-center mb-14 reveal">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-3">
                    Apa yang Bisa VOXORA Lakukan
                </p>
                <h2 id="fitur-heading"
                    class="font-serif text-4xl font-bold text-black mb-4">
                    Fitur Utama
                </h2>
                <p class="text-slate-600 max-w-xl mx-auto">
                    Dirancang khusus untuk memaksimalkan kemandirian belajar tunanetra
                    dalam mengakses materi STEM.
                </p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">

                @php
                $features = [
                    [
                        'icon'  => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z',
                        'color' => '#8BABF1',
                        'title' => 'Remediasi Dokumen STEM',
                        'desc'  => 'Upload PDF atau DOCX. VOXORA mengubah rumus matematika, simbol, dan notasi ilmiah menjadi kalimat natural bahasa Indonesia.',
                        'badge' => 'PDF & DOCX',
                    ],
                    [
                        'icon'  => 'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 016 0v6a3 3 0 01-3 3z',
                        'color' => '#FAAF90',
                        'title' => 'Asisten AI Bersuara (VUI)',
                        'desc'  => 'Ajukan pertanyaan tentang dokumen secara lisan atau teks. Asisten menjawab dan membacakan jawaban dengan suara langsung di browser.',
                        'badge' => 'Voice UI',
                    ],
                    [
                        'icon'  => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                        'color' => '#FCC9B5',
                        'title' => 'Kirim ke EduBraille',
                        'desc'  => 'Teks hasil remediasi dipecah menjadi chunk 5–40 karakter, dikonversi ke Unicode Braille, lalu dikirim langsung ke perangkat EduBraille.',
                        'badge' => 'Braille Display',
                    ],
                    [
                        'icon'  => 'M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z',
                        'color' => '#B3C7F7',
                        'title' => 'Ekspor ke Word',
                        'desc'  => 'Hasil remediasi bisa diekspor ke file Microsoft Word (.docx) yang rapi, siap dibagikan atau dicetak.',
                        'badge' => 'DOCX Export',
                    ],
                    [
                        'icon'  => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
                        'color' => '#8BABF1',
                        'title' => 'Pustaka Dokumen',
                        'desc'  => 'Semua dokumen tersimpan di pustaka pribadi Anda. Cari, buka kembali, dan lanjutkan membaca kapan saja.',
                        'badge' => 'Riwayat',
                    ],
                    [
                        'icon'  => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                        'color' => '#FAAF90',
                        'title' => 'WCAG 2.1 AA Compliant',
                        'desc'  => 'Seluruh antarmuka dirancang dengan hierarki heading, landmark ARIA, kontras warna tinggi, dan fokus keyboard yang jelas.',
                        'badge' => 'Aksesibel',
                    ],
                ];
                @endphp

                @foreach($features as $i => $f)
                <div class="feature-card reveal" style="animation-delay:{{ $i * 0.08 }}s;"
                     role="article" aria-label="Fitur: {{ $f['title'] }}">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center"
                             style="background:{{ $f['color'] }};" aria-hidden="true">
                            <svg class="w-5 h-5 text-black" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $f['icon'] }}"/>
                            </svg>
                        </div>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider
                                     px-2 py-0.5 rounded-full border border-black/10 bg-white/50">
                            {{ $f['badge'] }}
                        </span>
                    </div>
                    <h3 class="font-serif text-lg font-bold text-black mb-2">{{ $f['title'] }}</h3>
                    <p class="text-sm text-slate-700 leading-relaxed">{{ $f['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ════════════════════════════════════════════════════════════
         CARA KERJA
    ════════════════════════════════════════════════════════════ --}}
    <section id="cara-kerja" aria-labelledby="cara-kerja-heading"
             class="relative py-20 px-6"
             style="background:rgba(139,171,241,.18);">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-14 reveal">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-3">
                    Mudah Digunakan
                </p>
                <h2 id="cara-kerja-heading"
                    class="font-serif text-4xl font-bold text-black mb-4">
                    Cara Kerja VOXORA
                </h2>
                <p class="text-slate-600 max-w-lg mx-auto">
                    Tiga langkah sederhana dari dokumen ke teks yang bisa Anda dengar dan baca.
                </p>
            </div>

            {{-- Steps --}}
            <ol class="grid sm:grid-cols-3 gap-6" aria-label="Langkah-langkah menggunakan VOXORA">
                @php
                $steps = [
                    ['01','Upload Dokumen','Unggah file PDF atau DOCX dari komputer Anda. Seret dan lepas, atau klik untuk memilih.','#FAAF90'],
                    ['02','AI Meremediasi','VOXORA membersihkan dan mengubah seluruh konten—termasuk rumus matematika—menjadi kalimat natural.','#8BABF1'],
                    ['03','Baca & Kirim','Hasil dibaca NVDA secara otomatis. Ekspor ke Word, tanya lewat bot, atau kirim ke EduBraille.','#B3C7F7'],
                ];
                @endphp

                @foreach($steps as $i => $step)
                <li class="step-card reveal" style="animation-delay:{{ $i * 0.12 }}s;">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center
                                font-serif text-xl font-bold text-black mb-4"
                         style="background:{{ $step[3] }};" aria-hidden="true">
                        {{ $step[0] }}
                    </div>
                    <h3 class="font-serif text-lg font-bold text-black mb-2">{{ $step[1] }}</h3>
                    <p class="text-sm text-slate-700 leading-relaxed">{{ $step[2] }}</p>
                </li>
                @endforeach
            </ol>

            {{-- Contoh konversi --}}
            <div class="mt-12 reveal">
                <div class="rounded-2xl border-2 border-black/10 overflow-hidden"
                     style="background:rgba(255,255,255,.6);backdrop-filter:blur(10px);">
                    <div class="px-6 py-4 border-b border-black/08 flex items-center gap-3"
                         style="background:rgba(255,255,255,.5);">
                        <svg aria-hidden="true" class="w-4 h-4 text-slate-500" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                        </svg>
                        <span class="text-sm font-semibold text-black">Contoh Konversi Otomatis</span>
                    </div>
                    <div class="grid md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-black/08">
                        <div class="p-6">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">
                                Sebelum (dokumen asli)
                            </p>
                            <div class="space-y-2 font-mono text-sm text-black">
                                <p>f(x) = x² + 3x - 4</p>
                                <p>∫₀¹ x² dx = ⅓</p>
                                <p>√(a² + b²) = c</p>
                                <p>∑ᵢ₌₁ⁿ i = n(n+1)/2</p>
                            </div>
                        </div>
                        <div class="p-6" style="background:rgba(217,228,255,.3);">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">
                                Sesudah (ramah screen reader)
                            </p>
                            <div class="space-y-2 text-sm text-black leading-relaxed">
                                <p>f dari x sama dengan x kuadrat ditambah tiga x dikurangi empat.</p>
                                <p>Integral dari nol sampai satu dari x kuadrat dx sama dengan satu per tiga.</p>
                                <p>Akar kuadrat dari a kuadrat ditambah b kuadrat sama dengan c.</p>
                                <p>Jumlah i dari i sama dengan satu sampai n sama dengan n dikali n tambah satu dibagi dua.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    
    {{-- ════════════════════════════════════════════════════════════
         CTA BAWAH
    ════════════════════════════════════════════════════════════ --}}
    <section aria-labelledby="cta-heading"
             class="relative py-24 px-6 text-center overflow-hidden"
             style="background:rgba(139,171,241,.22);">
        <div class="blob w-[400px] h-[400px] top-[-80px] left-[50%] -translate-x-1/2"
             style="background:var(--bg-header);opacity:.35;" aria-hidden="true"></div>

        <div class="relative z-10 max-w-2xl mx-auto reveal">
            <p class="braille-deco text-4xl mb-4" aria-hidden="true">⠧⠕⠭⠕⠗⠁</p>
            <h2 id="cta-heading"
                class="font-serif text-4xl md:text-5xl font-bold text-black mb-5">
                Mulai Belajar Tanpa Hambatan
            </h2>
            <p class="text-slate-700 text-lg mb-8 leading-relaxed">
                Daftar sekarang dan rasakan kemudahan membaca dokumen STEM
                dengan screen reader dan braille display.
            </p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="{{ route('register') }}" class="btn-primary"
                   aria-label="Daftar akun VOXORA gratis sekarang">
                    <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    Daftar Gratis Sekarang
                </a>
                <a href="{{ route('login') }}" class="btn-secondary"
                   aria-label="Masuk ke akun yang sudah ada">
                    Sudah punya akun? Masuk
                </a>
            </div>
        </div>
    </section>

</main>

{{-- ════════════════════════════════════════════════════════════
     FOOTER
════════════════════════════════════════════════════════════ --}}
<footer role="contentinfo" aria-label="Footer VOXORA"
        class="py-8 px-6 border-t border-black/08"
        style="background:var(--bg-sidebar);">
    <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <span aria-hidden="true"
                  class="w-7 h-7 rounded-lg bg-black flex items-center justify-center
                         text-white font-bold text-xs">V</span>
            <span class="font-serif text-base font-bold text-black">VOXORA</span>
        </div>
        <p class="text-xs text-slate-600 text-center">
            &copy; {{ date('Y') }} VOXORA &mdash; Platform Aksesibilitas Dokumen untuk Tunanetra
        </p>
        <nav aria-label="Tautan footer">
            <ul class="flex items-center gap-4 list-none m-0 p-0">
                <li>
                    <a href="{{ route('login') }}"
                       class="text-xs font-semibold text-slate-700 hover:text-black underline underline-offset-2
                              focus:outline-none focus:ring-2 focus:ring-black rounded transition-colors">
                        Masuk
                    </a>
                </li>
                <li>
                    <a href="{{ route('register') }}"
                       class="text-xs font-semibold text-slate-700 hover:text-black underline underline-offset-2
                              focus:outline-none focus:ring-2 focus:ring-black rounded transition-colors">
                        Daftar
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</footer>

<script>
/* ── Scroll reveal ── */
const observer = new IntersectionObserver((entries) => {
    entries.forEach(el => {
        if (el.isIntersecting) {
            el.target.classList.add('visible');
            observer.unobserve(el.target);
        }
    });
}, { threshold: 0.12 });

document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

/* ── Smooth scroll untuk anchor link ── */
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        const target = document.querySelector(a.getAttribute('href'));
        if (!target) return;
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        // Fokus ke target untuk aksesibilitas keyboard
        target.setAttribute('tabindex', '-1');
        target.focus({ preventScroll: true });
    });
});
</script>

</body>
</html>