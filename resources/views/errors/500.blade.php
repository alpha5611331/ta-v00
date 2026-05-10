<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 – Server Error | VOXORA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,700;1,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>tailwind.config={theme:{extend:{fontFamily:{serif:['Fraunces','serif'],sans:['Plus Jakarta Sans','sans-serif']}}}}</script>
    <style>
        :root{--bg:#D9E4FF;--header:#8BABF1;--sidebar:#B3C7F7;--coral:#FAAF90;--peach:#FCC9B5;}
        *,*::before,*::after{box-sizing:border-box;}
        html,body{margin:0;height:100%;font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:#000;}
        :focus-visible{outline:3px solid #000;outline-offset:3px;border-radius:4px;}
        :focus:not(:focus-visible){outline:none;}
        .blob{position:fixed;border-radius:50%;filter:blur(90px);opacity:.5;pointer-events:none;z-index:0;}
        .skip-link{position:absolute;top:-999px;left:1rem;z-index:9999;padding:.5rem 1.25rem;background:#000;color:var(--bg);font-weight:700;border-radius:0 0 .5rem .5rem;text-decoration:none;}
        .skip-link:focus{top:0;}
        .btn-primary{display:inline-flex;align-items:center;gap:.6rem;padding:.875rem 2rem;background:#000;color:#fff;font-weight:700;font-size:.9rem;border-radius:.875rem;text-decoration:none;transition:opacity .18s;}
        .btn-primary:hover{opacity:.82;}
        .btn-secondary{display:inline-flex;align-items:center;gap:.6rem;padding:.875rem 2rem;background:transparent;color:#000;font-weight:700;font-size:.9rem;border:2px solid rgba(0,0,0,.2);border-radius:.875rem;text-decoration:none;transition:background .18s,border-color .18s;}
        .btn-secondary:hover{background:rgba(255,255,255,.5);border-color:#000;}
        @keyframes spin{to{transform:rotate(360deg);}}
        .spin-slow{animation:spin 8s linear infinite;}
    </style>
</head>
<body class="h-full flex flex-col">
<a href="#main-content" class="skip-link">Lewati ke konten utama</a>

<!-- Blob dekoratif -->
<div class="blob" style="width:420px;height:420px;background:var(--peach);top:-80px;right:-100px;" aria-hidden="true"></div>
<div class="blob" style="width:380px;height:380px;background:var(--sidebar);bottom:-60px;left:-80px;" aria-hidden="true"></div>

<!-- Header -->
<header role="banner" aria-label="Header VOXORA"
        class="relative z-10 flex items-center justify-center py-4 border-b border-black/08"
        style="background:rgba(139,171,241,.85);backdrop-filter:blur(10px);">
    <a href="{{ url('/') }}" aria-label="VOXORA – kembali ke halaman utama" class="flex items-center gap-2">
        <span aria-hidden="true" class="w-8 h-8 rounded-lg bg-black flex items-center justify-center text-white font-bold text-sm">V</span>
        <span class="font-serif text-xl font-bold text-black">VOXORA</span>
    </a>
</header>

<!-- Main -->
<main id="main-content" role="main" tabindex="-1" aria-label="Terjadi kesalahan pada server"
      class="relative z-10 flex-1 flex items-center justify-center px-6 py-12 text-center">
    <div>
        <!-- Kode error besar -->
        <p class="font-serif font-bold text-black leading-none mb-2 select-none"
           style="font-size:clamp(6rem,20vw,12rem);opacity:.08;"
           aria-hidden="true">500</p>

        <div style="margin-top:-clamp(3rem,8vw,6rem);" class="relative z-10">
            <!-- Ikon gear berputar -->
            <div class="w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6"
                 style="background:var(--sidebar);" aria-hidden="true">
                <svg class="w-10 h-10 text-black spin-slow" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0
                          002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0
                          001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0
                          00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724
                          0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724
                          1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724
                          1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724
                          1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608
                          2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>

            <h1 class="font-serif text-4xl font-bold text-black mb-3">
                Terjadi Kesalahan Server
            </h1>
            <p class="text-slate-600 text-base mb-2 max-w-md mx-auto leading-relaxed">
                Server mengalami masalah teknis saat memproses permintaan Anda.
                Tim kami sedang bekerja untuk memperbaikinya.
            </p>
            <p class="text-slate-500 text-sm mb-8 max-w-md mx-auto">
                Coba muat ulang halaman, atau kembali beberapa saat lagi.
            </p>

            <!-- Braille dekorasi -->
            <p class="font-mono text-2xl tracking-widest mb-8 select-none"
               style="opacity:.2;" aria-hidden="true">⠼⠑⠼⠚⠼⠚</p>

            <div class="flex flex-wrap justify-center gap-3">
                <a href="javascript:location.reload()" class="btn-primary"
                   aria-label="Muat ulang halaman ini">
                    <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0
                              0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357
                              2H15"/>
                    </svg>
                    Muat Ulang
                </a>
                @auth
                    <a href="{{ route('upload.index') }}" class="btn-secondary"
                       aria-label="Kembali ke halaman Unggah Dokumen">
                        <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Ke Halaman Utama
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-secondary"
                       aria-label="Kembali ke halaman login">
                        <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Ke Halaman Utama
                    </a>
                @endauth
            </div>
        </div>
    </div>
</main>

<!-- Footer -->
<footer role="contentinfo" class="relative z-10 py-3 text-center text-xs text-slate-600 border-t border-black/08"
        style="background:var(--sidebar);">
    VOXORA &copy; {{ date('Y') }} &mdash; Platform Aksesibilitas Dokumen untuk Tunanetra
</footer>
</body>
</html>