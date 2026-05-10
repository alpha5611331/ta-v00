@extends('layouts.app')

@section('title', $document->original_filename ?? 'Detail Dokumen')
@section('main-label', 'Halaman detail hasil remediasi dokumen')

@push('head')
<style>
    .result-full {
        overflow-y: auto;
        max-height: calc(100vh - 280px);
        scrollbar-width: thin;
        scrollbar-color: var(--bg-header) transparent;
        line-height: 1.85;
    }
    .result-full::-webkit-scrollbar { width: 7px; }
    .result-full::-webkit-scrollbar-thumb { background: var(--bg-header); border-radius: 4px; }

    /* Reading progress */
    #reading-bar {
        position: fixed;
        top: 0; left: 0;
        height: 3px;
        background: var(--bg-header);
        z-index: 999;
        transition: width .1s linear;
        width: 0%;
    }
</style>
@endpush

@section('content')

{{-- Reading progress bar (aksesibel via aria-label) --}}
<div id="reading-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"
     aria-label="Kemajuan membaca dokumen"></div>

{{-- ── Breadcrumb ── --}}
<nav aria-label="Breadcrumb" class="mb-4">
    <ol class="flex items-center gap-2 text-sm text-slate-600" role="list">
        <li role="listitem">
            <a href="{{ route('pustaka.index') }}"
               class="hover:text-black underline underline-offset-2 focus:outline-none focus:ring-2 focus:ring-black rounded">
                Pustaka
            </a>
        </li>
        <li role="listitem" aria-hidden="true">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </li>
        <li role="listitem" aria-current="page" class="text-black font-medium truncate max-w-xs">
            {{ $document->original_filename }}
        </li>
    </ol>
</nav>

{{-- ── Header dokumen ── --}}
<div class="flex items-start justify-between gap-4 mb-6 flex-wrap">
    <div>
        <div class="flex items-center gap-2 mb-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold uppercase border"
                  style="{{ strtolower($document->file_type ?? 'pdf') === 'docx'
                    ? 'color:#1e3a8a;background:#dbeafe;border-color:#93c5fd;'
                    : 'color:#7f1d1d;background:#fee2e2;border-color:#fca5a5;' }}"
                  aria-label="Tipe file {{ strtoupper($document->file_type ?? 'PDF') }}">
                {{ strtoupper($document->file_type ?? 'PDF') }}
            </span>
            @if($document->braille_sent_at ?? false)
            <span class="inline-flex items-center gap-1 text-xs font-medium text-blue-800 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-md">
                <svg aria-hidden="true" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Braille Terkirim
            </span>
            @endif
        </div>

        <h1 class="font-serif text-2xl font-bold text-black leading-snug">
            {{ $document->original_filename }}
        </h1>

        <p class="text-sm text-slate-600 mt-1">
            Diunggah
            <time datetime="{{ isset($document->created_at) ? (is_string($document->created_at) ? $document->created_at : $document->created_at->toIso8601String()) : '' }}">
                {{ isset($document->created_at) ? (is_object($document->created_at) ? $document->created_at->format('d F Y, H:i') : $document->created_at) : '-' }}
            </time>
            @if($document->char_count ?? false)
                &nbsp;·&nbsp; {{ number_format($document->char_count) }} karakter
            @endif
        </p>
    </div>

    {{-- Tombol kembali --}}
    <a href="{{ route('pustaka.index') }}"
       class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-black
              border-2 border-black/20 hover:border-black hover:bg-white/60
              focus:outline-none focus:ring-4 focus:ring-black focus:ring-offset-2 transition-all"
       aria-label="Kembali ke daftar pustaka">
        <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        Kembali
    </a>
</div>

{{-- ── Area hasil remediasi penuh ── --}}
<section aria-labelledby="full-result-heading">
    <div class="flex items-center justify-between mb-3">
        <h2 id="full-result-heading" class="text-lg font-bold text-black">
            Hasil Remediasi
        </h2>
        <p class="text-xs text-slate-500">
            <kbd class="px-1 bg-slate-100 rounded font-mono">Tab</kbd> lalu
            <kbd class="px-1 bg-slate-100 rounded font-mono">↓</kbd>
            untuk membaca per kalimat dengan NVDA
        </p>
    </div>

    {{--
        ARIA NOTES:
        · tabindex="0"       → fokusable dengan Tab
        · role="document"    → NVDA masuk ke reading mode, bisa baca per kalimat
        · aria-live="polite" → umumkan perubahan isi
        · aria-label         → konteks bagi screen reader
    --}}
    <div
        id="full-result-box"
        tabindex="0"
        role="document"
        aria-label="Isi lengkap hasil remediasi dokumen {{ $document->original_filename }}. Gunakan tombol panah untuk membaca per kalimat."
        aria-describedby="full-result-heading"
        class="result-full rounded-2xl border-2 border-black bg-white/75 backdrop-blur-sm
               px-6 py-5 text-sm text-black
               focus:outline-none focus:ring-4 focus:ring-black focus:ring-offset-2">

        @if($document->remediated_text ?? false)
            @foreach(explode("\n\n", $document->remediated_text) as $para)
                @if(trim($para))
                    <p class="mb-4 leading-relaxed">{{ trim($para) }}</p>
                @endif
            @endforeach
        @else
            <p class="text-slate-500 italic">Belum ada hasil remediasi untuk dokumen ini.</p>
        @endif
    </div>
</section>

{{-- ── Tombol aksi ── --}}
<section aria-label="Tindakan untuk dokumen ini" class="mt-6">
    <h2 class="sr-only">Tindakan Dokumen</h2>

    <div class="flex flex-wrap gap-3">

        {{-- Ekspor ke Word --}}
        <form method="POST" action="{{ route('upload.export') }}">
            @csrf
            <input type="hidden" name="result_text" value="{{ $document->remediated_text ?? '' }}">
            <input type="hidden" name="document_title" value="{{ $document->original_filename ?? 'Dokumen' }}">
            <button type="submit"
                class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm
                       text-white border-2 border-black hover:brightness-90 active:scale-95
                       focus:outline-none focus:ring-4 focus:ring-green-800 focus:ring-offset-2 transition-all"
                style="background:#15803d;"
                aria-label="Ekspor hasil remediasi ke dokumen Microsoft Word">
                <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                </svg>
                Ekspor ke Word
            </button>
        </form>

        {{-- Tanya Dokumen --}}
        <a href="{{ route('tanya.show', ['id' => $document->id]) }}"
            class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm
                   text-white border-2 border-black hover:brightness-90 active:scale-95
                   focus:outline-none focus:ring-4 focus:ring-blue-800 focus:ring-offset-2 transition-all"
            style="background:#1d4ed8;"
            aria-label="Tanya dokumen ini menggunakan asisten AI VOXORA dengan antarmuka suara">
            <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
            </svg>
            Tanya Dokumen
        </a>

        {{-- Kirim ke EduBraille --}}
        <a href="{{ route('braille.index', ['doc_id' => $document->id]) }}"
            class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm
                   text-white border-2 border-black hover:brightness-90 active:scale-95
                   focus:outline-none focus:ring-4 focus:ring-orange-800 focus:ring-offset-2 transition-all"
            style="background:#c2410c;"
            aria-label="Kirim dokumen ini ke laman pengiriman EduBraille">
            <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            Kirim ke EduBraille
        </a>

        {{-- Hapus --}}
        <form method="POST" action="{{ route('pustaka.destroy', $document->id) }}"
              onsubmit="return confirm('Hapus dokumen ini dari pustaka? Tindakan ini tidak dapat dibatalkan.')">
            @csrf @method('DELETE')
            <button type="submit"
                class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm
                       text-red-800 bg-red-50 border-2 border-red-200 hover:bg-red-100
                       focus:outline-none focus:ring-4 focus:ring-red-700 focus:ring-offset-2 transition-all"
                aria-label="Hapus dokumen {{ $document->original_filename }} dari pustaka">
                <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Hapus Dokumen
            </button>
        </form>
    </div>
</section>

@endsection

@push('scripts')
<script>
/* ── Auto-fokus & umumkan ke NVDA ── */
window.addEventListener('DOMContentLoaded', () => {
    const box = document.getElementById('full-result-box');
    setTimeout(() => {
        box.focus();
        if (typeof announce === 'function') {
            announce('Dokumen {{ addslashes($document->original_filename ?? "") }} siap dibaca. Gunakan tombol panah untuk membaca per kalimat.');
        }
    }, 350);

    /* ── Reading progress bar ── */
    const bar = document.getElementById('reading-bar');
    box.addEventListener('scroll', () => {
        const pct = box.scrollTop / (box.scrollHeight - box.clientHeight) * 100;
        bar.style.width = Math.min(pct, 100) + '%';
        bar.setAttribute('aria-valuenow', Math.round(pct));
    });
});
</script>
@endpush