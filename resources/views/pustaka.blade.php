{{--
┌──────────────────────────────────────────────────────────────┐
│  pustaka.blade.php                                            │
│  Laman Pustaka – riwayat dokumen yang telah diremediasi      │
│  Extends: layouts/app.blade.php                              │
└──────────────────────────────────────────────────────────────┘
--}}

@extends('layouts.app')

@section('title', 'Pustaka')

@section('main-label', 'Pustaka dokumen yang telah diunggah dan diremediasi')

{{-- ── Tambahan style khusus halaman ini ── --}}
@push('head')
<style>
    /* ── Kartu dokumen ────────────────────────────── */
    .doc-card {
        display:         block;
        text-decoration: none;
        color:           var(--text-main);
        background:      rgba(255, 255, 255, .62);
        border:          1.5px solid rgba(0, 0, 0, .10);
        border-radius:   1rem;
        padding:         1.25rem 1.5rem;
        transition:      background .18s ease,
                         border-color .18s ease,
                         transform .18s ease,
                         box-shadow .18s ease;
        position: relative;
    }
    .doc-card:hover {
        background:    rgba(255, 255, 255, .85);
        border-color:  rgba(0, 0, 0, .25);
        transform:     translateY(-2px);
        box-shadow:    0 6px 20px rgba(0, 0, 0, .08);
    }
    .doc-card:focus-visible {
        outline:       3px solid #000;
        outline-offset: 3px;
        border-color:  #000;
        background:    rgba(255, 255, 255, .92);
    }

    /* ── Badge tipe file ── */
    .badge {
        display:       inline-flex;
        align-items:   center;
        padding:       .15rem .55rem;
        border-radius: .35rem;
        font-size:     .7rem;
        font-weight:   700;
        letter-spacing: .04em;
        text-transform: uppercase;
        border:        1.5px solid currentColor;
    }
    .badge-pdf  { color: #7f1d1d; background: #fee2e2; }
    .badge-docx { color: #1e3a8a; background: #dbeafe; }

    /* ── Search bar ── */
    .search-input {
        width:         100%;
        padding:       .75rem 1rem .75rem 3rem;
        background:    rgba(255,255,255,.7);
        border:        2px solid rgba(0,0,0,.15);
        border-radius: .875rem;
        font-family:   'Plus Jakarta Sans', sans-serif;
        font-size:     .9rem;
        color:         var(--text-main);
        transition:    border-color .18s ease, background .18s ease, box-shadow .18s ease;
    }
    .search-input::placeholder { color: #64748b; }
    .search-input:focus {
        outline:      none;
        border-color: var(--text-main);
        background:   #fff;
        box-shadow:   0 0 0 3px rgba(0,0,0,.12);
    }

    /* ── Empty state ── */
    .empty-state {
        display:         flex;
        flex-direction:  column;
        align-items:     center;
        justify-content: center;
        gap:             1rem;
        padding:         4rem 2rem;
        text-align:      center;
        color:           #475569;
    }

    /* ── Staggered card animation ── */
    .doc-card { opacity: 0; animation: fadeSlide .3s ease forwards; }
    @keyframes fadeSlide {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: none; }
    }
    .doc-card:nth-child(1)  { animation-delay: .04s; }
    .doc-card:nth-child(2)  { animation-delay: .08s; }
    .doc-card:nth-child(3)  { animation-delay: .12s; }
    .doc-card:nth-child(4)  { animation-delay: .16s; }
    .doc-card:nth-child(5)  { animation-delay: .20s; }
    .doc-card:nth-child(6)  { animation-delay: .24s; }
    .doc-card:nth-child(7)  { animation-delay: .28s; }
    .doc-card:nth-child(8)  { animation-delay: .32s; }
    .doc-card:nth-child(n+9){ animation-delay: .36s; }

    /* ── Pagination ── */
    .page-btn {
        min-width:    2.25rem;
        height:       2.25rem;
        padding:      0 .6rem;
        display:      inline-flex;
        align-items:  center;
        justify-content: center;
        border-radius: .5rem;
        border:       1.5px solid transparent;
        font-size:    .85rem;
        font-weight:  500;
        transition:   background .15s ease, border-color .15s ease;
        text-decoration: none;
        color:        var(--text-main);
    }
    .page-btn:hover          { background: rgba(0,0,0,.08); }
    .page-btn.current        { background: var(--accent-warm); border-color: #000; font-weight: 700; }
    .page-btn:focus-visible  { outline: 3px solid #000; outline-offset: 2px; }
    .page-btn[disabled]      { opacity: .4; pointer-events: none; }
</style>
@endpush

{{-- ════════════════════════════════════════════════════════
     KONTEN HALAMAN
════════════════════════════════════════════════════════ --}}
@section('content')

    {{-- ── Heading Halaman (h1) ──────────────────────────── --}}
    <div class="flex items-end justify-between mb-6 flex-wrap gap-4">
        <div>
            <h1 class="font-serif text-3xl font-bold text-black leading-tight">
                Pustaka
            </h1>
            <p class="mt-1 text-sm text-slate-600">
                Seluruh dokumen yang telah Anda unggah dan diremediasi oleh VOXORA.
            </p>
        </div>

        {{-- Tombol unggah baru --}}
        <a
            href="{{ route('upload.index') }}"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold
                   text-white transition-opacity hover:opacity-90"
            style="background: var(--text-main);"
            aria-label="Unggah dokumen baru">
            <svg aria-hidden="true" focusable="false" class="w-4 h-4" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Unggah Baru
        </a>
    </div>

    {{-- ── Search Bar (h2 implisit via label) ───────────── --}}
    <section aria-labelledby="search-heading">
        <h2 id="search-heading" class="sr-only">Cari Dokumen</h2>

        <div class="relative mb-6">
            {{-- Ikon kaca pembesar (dekoratif) --}}
            <span
                aria-hidden="true"
                class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                </svg>
            </span>

            {{--
                Label dihubungkan via `for` = `id` (WCAG 1.3.1 / 4.1.2)
                Teks label tersembunyi secara visual tapi dibaca NVDA
            --}}
            <label for="search-input" class="sr-only">
                Cari dokumen berdasarkan judul atau isi
            </label>
            <input
                type="search"
                id="search-input"
                name="q"
                class="search-input"
                placeholder="Cari judul atau isi dokumen…"
                value="{{ request('q') }}"
                autocomplete="off"
                aria-describedby="search-hint"
                aria-controls="doc-list"
                aria-label="Cari dokumen berdasarkan judul atau isi">
            <p id="search-hint" class="sr-only">
                Ketik untuk memfilter daftar dokumen secara langsung.
                Hasil akan diperbarui otomatis.
            </p>
        </div>
    </section>

    {{-- ── Ringkasan hasil ──────────────────────────────── --}}
    <div
        id="result-summary"
        role="status"
        aria-live="polite"
        aria-atomic="true"
        class="mb-4 text-sm text-slate-600">
        @if($documents->total() > 0)
            Menampilkan
            <strong>{{ $documents->firstItem() }}–{{ $documents->lastItem() }}</strong>
            dari
            <strong>{{ $documents->total() }}</strong>
            dokumen
            @if(request('q'))
                untuk pencarian "<strong>{{ request('q') }}</strong>"
            @endif
        @else
            Tidak ada dokumen yang ditemukan.
        @endif
    </div>

    {{-- ── Daftar Dokumen ───────────────────────────────── --}}
    <section aria-labelledby="doclist-heading">
        <h2 id="doclist-heading" class="sr-only">Daftar Dokumen</h2>

        @if($documents->isEmpty())
            {{-- Empty state --}}
            <div class="empty-state" role="status" aria-live="polite">
                <div
                    aria-hidden="true"
                    class="w-16 h-16 rounded-2xl flex items-center justify-center"
                    style="background: var(--bg-sidebar);">
                    <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3
                              6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13
                              C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13
                              C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>

                @if(request('q'))
                    <p class="text-base font-semibold text-black">
                        Tidak ada hasil untuk "{{ request('q') }}"
                    </p>
                    <p class="text-sm">Coba kata kunci yang berbeda.</p>
                    <a
                        href="{{ route('pustaka.index') }}"
                        class="mt-2 text-sm font-semibold underline underline-offset-2 hover:text-black">
                        Tampilkan semua dokumen
                    </a>
                @else
                    <p class="text-base font-semibold text-black">
                        Pustaka Anda masih kosong
                    </p>
                    <p class="text-sm">Unggah dokumen pertama Anda untuk memulai.</p>
                    <a
                        href="{{ route('upload.index') }}"
                        class="mt-2 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm
                               font-semibold text-white hover:opacity-90 transition-opacity"
                        style="background: var(--text-main);">
                        <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Unggah Dokumen
                    </a>
                @endif
            </div>

        @else
            {{-- ─ Grid kartu dokumen ─ --}}
            <ul
                id="doc-list"
                role="list"
                aria-label="Daftar dokumen di pustaka Anda"
                class="grid gap-3 sm:grid-cols-1 md:grid-cols-2 xl:grid-cols-2">

                @foreach($documents as $doc)
                    {{--
                        Setiap <li> berisi satu <a> yang focusable via Tab.
                        Judul sebagai <h3> mempertahankan hierarki h1>h2>h3.
                    --}}
                    @php
                        $previewText = $doc->preview_text ?? \Illuminate\Support\Str::limit($doc->remediated_text ?? 'Belum ada hasil remediasi.', 200);
                        $createdAt = is_object($doc->created_at) ? $doc->created_at : \Carbon\Carbon::parse($doc->created_at);
                    @endphp
                    <li role="listitem">
                        <a
                            href="{{ route('pustaka.show', $doc->id) }}"
                            class="doc-card"
                            aria-label="Buka dokumen: {{ $doc->original_filename }}, tipe {{ strtoupper($doc->file_type ?? 'PDF') }}, diunggah {{ $createdAt->diffForHumans() }}. Pratinjau: {{ \Illuminate\Support\Str::limit($previewText, 100) }}">

                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="badge {{ strtolower($doc->file_type ?? 'pdf') === 'docx' ? 'badge-docx' : 'badge-pdf' }}" aria-hidden="true">
                                    {{ strtoupper($doc->file_type ?? 'PDF') }}
                                </span>
                                <time datetime="{{ $createdAt->toIso8601String() }}" class="text-xs text-slate-500 flex-shrink-0">
                                    {{ $createdAt->format('d M Y') }}
                                </time>
                            </div>

                            <h3 class="text-base font-bold text-black leading-snug mb-2 line-clamp-2">
                                {{ $doc->original_filename }}
                            </h3>

                            <p class="text-sm text-slate-700 leading-relaxed line-clamp-3 mb-1">
                                {{ $previewText }}
                            </p>

                            {{-- ── Footer kartu: ukuran & karakter ── --}}
                            <div class="flex items-center gap-4 mt-3 pt-3
                                        border-t border-black/08 text-xs text-slate-500">
                                @if($doc->char_count)
                                    <span class="flex items-center gap-1">
                                        <svg aria-hidden="true" focusable="false" class="w-3.5 h-3.5"
                                             fill="none" viewBox="0 0 24 24"
                                             stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M4 6h16M4 12h16M4 18h7"/>
                                        </svg>
                                        {{ number_format($doc->char_count) }} karakter
                                    </span>
                                @endif

                                {{-- Indikator braille sudah dikirim --}}
                                @if($doc->braille_sent_at)
                                    <span class="flex items-center gap-1 text-blue-700 font-medium">
                                        <svg aria-hidden="true" focusable="false" class="w-3.5 h-3.5"
                                             fill="none" viewBox="0 0 24 24"
                                             stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Braille Terkirim
                                    </span>
                                @endif

                                {{-- Chevron kanan (dekoratif) --}}
                                <svg
                                    aria-hidden="true"
                                    focusable="false"
                                    class="w-4 h-4 ml-auto text-slate-400 flex-shrink-0"
                                    fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>

            {{-- ── Paginasi ──────────────────────────────── --}}
            @if($documents->hasPages())
                <nav
                    aria-label="Navigasi halaman daftar dokumen"
                    class="flex items-center justify-center gap-2 mt-8 flex-wrap">

                    {{-- Tombol Sebelumnya --}}
                    @if($documents->onFirstPage())
                        <span
                            class="page-btn"
                            aria-disabled="true"
                            aria-label="Tidak ada halaman sebelumnya">
                            <svg aria-hidden="true" class="w-4 h-4" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </span>
                    @else
                        <a
                            href="{{ $documents->previousPageUrl() }}"
                            class="page-btn"
                            aria-label="Halaman sebelumnya">
                            <svg aria-hidden="true" class="w-4 h-4" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </a>
                    @endif

                    {{-- Nomor halaman --}}
                    @foreach($documents->getUrlRange(1, $documents->lastPage()) as $page => $url)
                        @if($page == $documents->currentPage())
                            <span
                                class="page-btn current"
                                aria-current="page"
                                aria-label="Halaman {{ $page }}, halaman saat ini">
                                {{ $page }}
                            </span>
                        @else
                            <a
                                href="{{ $url }}"
                                class="page-btn"
                                aria-label="Halaman {{ $page }}">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach

                    {{-- Tombol Berikutnya --}}
                    @if($documents->hasMorePages())
                        <a
                            href="{{ $documents->nextPageUrl() }}"
                            class="page-btn"
                            aria-label="Halaman berikutnya">
                            <svg aria-hidden="true" class="w-4 h-4" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7 7-7"/>
                            </svg>
                        </a>
                    @else
                        <span
                            class="page-btn"
                            aria-disabled="true"
                            aria-label="Tidak ada halaman berikutnya">
                            <svg aria-hidden="true" class="w-4 h-4" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7 7-7"/>
                            </svg>
                        </span>
                    @endif
                </nav>
            @endif

        @endif
    </section>

@endsection

{{-- ════════════════════════════════════════════════════════
     JAVASCRIPT KHUSUS HALAMAN INI
════════════════════════════════════════════════════════ --}}
@push('scripts')
<script>
(function () {
    /* ── Live search (client-side filter + server-side fallback) ─ */
    const searchInput  = document.getElementById('search-input');
    const docList      = document.getElementById('doc-list');
    const resultSummary= document.getElementById('result-summary');

    if (!searchInput || !docList) return;

    let debounceTimer;

    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const query = searchInput.value.trim().toLowerCase();

            // ── Client-side filter (kartu yang sudah ada di DOM) ──
            let visible = 0;
            docList.querySelectorAll('li').forEach(li => {
                const title   = li.querySelector('h3')?.textContent?.toLowerCase() ?? '';
                const preview = li.querySelector('p')?.textContent?.toLowerCase()  ?? '';
                const match   = !query || title.includes(query) || preview.includes(query);
                li.hidden = !match;
                if (match) visible++;
            });

            // Update ringkasan (screen reader akan membaca via aria-live)
            if (query) {
                resultSummary.textContent =
                    `${visible} dokumen ditemukan untuk pencarian "${searchInput.value.trim()}"`;
            } else {
                resultSummary.textContent =
                    `Menampilkan ${visible} dokumen`;
            }

            // ── Server-side search untuk query panjang (≥ 3 karakter) ──
            // Hanya trigger jika tidak ada hasil dari client-side filter
            if (query.length >= 3 && visible === 0) {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    const url = new URL(window.location.href);
                    url.searchParams.set('q', query);
                    window.location.href = url.toString();
                }, 800);
            }
        }, 300);
    });

    /* ── Umumkan jumlah hasil via NVDA setelah load ── */
    if (typeof announce === 'function') {
        const total = docList ? docList.querySelectorAll('li').length : 0;
        if (total > 0) {
            announce(`Pustaka dimuat. ${total} dokumen tersedia.`);
        } else {
            announce('Pustaka kosong. Unggah dokumen untuk memulai.');
        }
    }
})();
</script>
@endpush