@extends('layouts.app')

@section('title', 'Unggah Dokumen')
@section('main-label', 'Halaman unggah dan remediasi dokumen')

@push('head')
<style>
    .drop-zone { transition: background .2s ease, border-color .2s ease, transform .2s ease; }
    .drop-zone.drag-over { border-color: #000 !important; background: rgba(139,171,241,.25) !important; transform: scale(1.01); }
    .result-box { max-height: 320px; overflow-y: auto; scrollbar-width: thin; scrollbar-color: var(--bg-header) transparent; }
    .result-box::-webkit-scrollbar { width: 7px; }
    .result-box::-webkit-scrollbar-thumb { background: var(--bg-header); border-radius: 4px; }
    .spinner { width:20px;height:20px;border:3px solid rgba(0,0,0,.12);border-top-color:#000;border-radius:50%;animation:spin .7s linear infinite;display:inline-block;vertical-align:middle;flex-shrink:0; }
    @keyframes spin { to { transform:rotate(360deg); } }
    #progress-bar { transition: width .4s ease; }
    .status-banner { display:flex;align-items:flex-start;gap:.75rem;border-radius:.875rem;padding:.875rem 1.125rem;font-size:.875rem;font-weight:500;animation:fadeSlide .35s ease forwards; }
    .status-banner.success { background:#f0fdf4;border:1.5px solid #15803d;color:#14532d; }
    .status-banner.error { background:#fef2f2;border:1.5px solid #b91c1c;color:#7f1d1d; }
</style>
@endpush

@section('content')

<h1 class="font-serif text-3xl font-bold text-black mb-6">Unggah Dokumen</h1>

@if(session('success'))
<div role="alert" aria-live="assertive" aria-atomic="true" class="status-banner success mb-5">
    <svg aria-hidden="true" class="w-5 h-5 mt-0.5 flex-shrink-0 text-green-700" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
    <div>
        <p class="font-bold">Dokumen berhasil diproses!</p>
        <p class="text-sm mt-0.5">{{ session('success') }} Hasil remediasi tersedia di bawah. Tekan Tab untuk membacanya dengan NVDA.</p>
    </div>
</div>
@endif

@if($errors->any())
<div role="alert" aria-live="assertive" aria-atomic="true" class="status-banner error mb-5">
    <svg aria-hidden="true" class="w-5 h-5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
    <div>
        <p class="font-bold">Upload gagal:</p>
        <ul class="mt-1 space-y-0.5 text-sm list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
</div>
@endif

{{-- BOX 1: UPLOAD --}}
<section aria-labelledby="upload-heading" class="mb-6">
    <h2 id="upload-heading" class="text-lg font-bold text-black mb-3">Pilih Dokumen</h2>
    <form id="upload-form" method="POST" action="{{ route('upload.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        <label id="drop-zone" for="document-input"
            class="drop-zone flex flex-col items-center justify-center gap-3 border-2 border-dashed rounded-2xl bg-white/60 backdrop-blur-sm p-10 cursor-pointer hover:bg-white/80 focus-within:ring-4 focus-within:ring-black focus-within:ring-offset-2 transition-all"
            style="border-color:var(--bg-header);"
            role="button" tabindex="0"
            aria-label="Zona unggah. Seret dan lepas file di sini, atau tekan Enter untuk membuka dialog pemilihan file."
            onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();document.getElementById('document-input').click()}"
            ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event)">
            <div aria-hidden="true" class="w-14 h-14 rounded-full flex items-center justify-center" style="background:var(--bg-main);border:2px solid var(--bg-header);">
                <svg class="w-7 h-7 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            </div>
            <p class="text-base font-semibold text-black">Unggah Dokumen di sini</p>
            <p class="text-sm text-slate-600" aria-hidden="true">Seret &amp; lepas, atau klik untuk memilih file</p>
            <p id="file-type-hint" class="text-xs text-slate-500">Format: <strong>PDF</strong> atau <strong>DOCX</strong> · Maks. 20 MB</p>
            <p id="selected-file-name" class="hidden text-sm font-semibold text-black bg-white border border-gray-300 px-3 py-1.5 rounded-lg" role="status" aria-live="polite" aria-atomic="true"></p>
        </label>
        <input type="file" id="document-input" name="document" accept=".pdf,.docx" class="sr-only" aria-describedby="file-type-hint" onchange="handleFileSelect(this)">
        <div class="mt-4 flex justify-end">
            <button type="submit" id="submit-btn"
                class="flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-sm text-white bg-black hover:bg-gray-800 focus:outline-none focus:ring-4 focus:ring-black focus:ring-offset-2 disabled:opacity-40 transition-colors"
                aria-label="Mulai proses remediasi dokumen yang dipilih">
                <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Proses Remediasi
            </button>
        </div>
    </form>
    <div id="progress-container" class="hidden mt-4" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" aria-label="Kemajuan proses remediasi">
        <div class="flex items-center gap-3 mb-2">
            <span class="spinner" aria-hidden="true"></span>
            <p id="progress-label" class="text-sm font-medium text-black">Mengunggah dokumen…</p>
        </div>
        <div class="w-full rounded-full h-2" style="background:var(--bg-sidebar);">
            <div id="progress-bar" class="h-2 rounded-full w-0" style="background:var(--bg-header);"></div>
        </div>
    </div>
</section>

{{-- BOX 2: HASIL --}}
<section aria-labelledby="result-heading" class="mb-6">
    <h2 id="result-heading" class="text-lg font-bold text-black mb-3">Hasil Remediasi Dokumen</h2>
    <div id="result-box"
        class="result-box rounded-2xl border-2 border-black bg-white/70 backdrop-blur-sm px-5 py-4 text-sm text-black leading-relaxed focus:outline-none focus:ring-4 focus:ring-black focus:ring-offset-2"
        tabindex="0" role="region" aria-live="polite" aria-atomic="true"
        aria-label="Hasil remediasi dokumen. Gunakan tombol panah untuk membaca per kalimat dengan NVDA."
        aria-describedby="result-heading">
        @if(isset($remediationResult) && $remediationResult)
            <div class="prose prose-sm max-w-none text-black">{!! nl2br(e($remediationResult)) !!}</div>
        @else
            <p class="text-slate-500 italic" id="result-placeholder">Hasil teks natural dari dokumen yang telah diremediasi akan muncul di sini setelah Anda memilih dan memproses dokumen di atas. Area ini dapat difokus dengan tombol Tab dan dibaca oleh NVDA.</p>
        @endif
    </div>
    <div class="flex justify-between items-center mt-1">
        <p class="text-xs text-slate-500">Gunakan <kbd class="px-1 bg-slate-100 rounded text-[10px] font-mono">Tab</kbd> lalu tombol panah untuk membaca per kalimat dengan NVDA</p>
        <p id="char-count" class="text-xs text-slate-500" aria-live="polite" aria-atomic="true">
            @if(isset($remediationResult))
                {{ number_format(strlen($remediationResult)) }} karakter
            @endif
        </p>
    </div>
</section>

{{-- BOX 3: TOMBOL --}}
<section aria-label="Tombol tindakan untuk dokumen yang telah diremediasi">
    <h2 class="sr-only">Tindakan Dokumen</h2>
    <div class="flex flex-wrap gap-3">

        {{-- Ekspor ke Word --}}
        <form method="POST" action="{{ route('upload.export') }}">
            @csrf
            <input type="hidden" name="result_text" value="{{ $remediationResult ?? '' }}">
            <button type="submit"
                class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm text-white border-2 border-black focus:outline-none focus:ring-4 focus:ring-green-800 focus:ring-offset-2 hover:brightness-90 active:scale-95 transition-all {{ !isset($remediationResult) ? 'opacity-40 cursor-not-allowed' : '' }}"
                style="background:#15803d;"
                aria-label="Ekspor hasil remediasi ke dokumen Microsoft Word"
                {{ !isset($remediationResult) ? 'disabled' : '' }}>
                <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/></svg>
                Ekspor ke Word
            </button>
        </form>

        {{-- Tanya Dokumen --}}
        <a href="{{ isset($document) ? route('tanya.show', ['id' => $document->id]) : route('tanya.index') }}"
            class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm text-white border-2 border-black focus:outline-none focus:ring-4 focus:ring-blue-800 focus:ring-offset-2 hover:brightness-90 active:scale-95 transition-all {{ !isset($remediationResult) ? 'opacity-40 pointer-events-none' : '' }}"
            style="background:#1d4ed8;"
            aria-label="Tanya dokumen ini menggunakan asisten AI VOXORA dengan antarmuka suara"
            @if(!isset($remediationResult)) aria-disabled="true" @endif>
            <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            Tanya Dokumen
        </a>

        {{-- Kirim ke EduBraille --}}
        <div class="flex items-center gap-2">
            <label for="chunk-size" class="sr-only">Ukuran chunk braille</label>
            <select id="chunk-size" name="chunk_size" form="braille-form"
                class="rounded-lg border-2 border-black/20 px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-1"
                aria-label="Pilih ukuran chunk karakter braille">
                <option value="5">5 karakter/chunk</option>
                <option value="20" selected>20 karakter/chunk</option>
            </select>
            <form method="POST" action="{{ route('upload.braille') }}" id="braille-form">
                @csrf
                <input type="hidden" name="result_text" value="{{ $remediationResult ?? '' }}">
                <button type="submit"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm text-white border-2 border-black focus:outline-none focus:ring-4 focus:ring-orange-800 focus:ring-offset-2 hover:brightness-90 active:scale-95 transition-all {{ !isset($remediationResult) ? 'opacity-40 cursor-not-allowed' : '' }}"
                    style="background:#c2410c;"
                    aria-label="Kirim hasil remediasi ke perangkat EduBraille"
                    {{ !isset($remediationResult) ? 'disabled' : '' }}>
                    <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    Kirim ke EduBraille
                </button>
            </form>
        </div>

    </div>
</section>

@if(isset($brailleChunks))
<section aria-labelledby="braille-result-heading" class="mt-6">
    <h2 id="braille-result-heading" class="text-base font-bold text-black mb-3">Pratinjau Braille</h2>
    <div class="rounded-2xl border-2 border-black bg-white/70 p-5">
        <div class="flex flex-wrap gap-2" role="list" aria-label="Daftar chunk braille">
            @foreach($brailleChunks as $i => $chunk)
            <span role="listitem" class="font-mono text-xs rounded-lg px-2 py-1" style="background:var(--bg-main);border:1px solid var(--bg-header);" aria-label="Chunk {{ $i+1 }}: {{ $chunk['text'] }}">{{ $chunk['braille'] }}</span>
            @endforeach
        </div>
        <p class="mt-3 text-sm text-green-800 font-semibold" role="status" aria-live="assertive">✓ {{ count($brailleChunks) }} chunk berhasil dikirim ke EduBraille.</p>
    </div>
</section>
@endif

@endsection

@push('scripts')
<script>
function announceUpload(msg) {
    if (typeof announce === 'function') { announce(msg, 'assertive'); return; }
    const el = document.getElementById('aria-announcer');
    if (!el) return;
    el.setAttribute('aria-live','assertive');
    el.textContent = '';
    requestAnimationFrame(() => { el.textContent = msg; });
}
function handleFileSelect(input) {
    if (!input.files.length) return;
    const file = input.files[0];
    if (file.size > 20*1024*1024) { announceUpload('Gagal: ukuran file melebihi 20 megabyte.'); input.value=''; return; }
    const el = document.getElementById('selected-file-name');
    el.textContent = 'File dipilih: ' + file.name;
    el.classList.remove('hidden');
    announceUpload('File ' + file.name + ' berhasil dipilih. Tekan tombol Proses Remediasi untuk melanjutkan.');
}
function handleDragOver(e) { e.preventDefault(); document.getElementById('drop-zone').classList.add('drag-over'); }
function handleDragLeave(e) { e.preventDefault(); document.getElementById('drop-zone').classList.remove('drag-over'); }
function handleDrop(e) {
    e.preventDefault();
    document.getElementById('drop-zone').classList.remove('drag-over');
    const files = e.dataTransfer.files;
    if (!files.length) return;
    const input = document.getElementById('document-input');
    const dt = new DataTransfer(); dt.items.add(files[0]); input.files = dt.files;
    const el = document.getElementById('selected-file-name');
    el.textContent = 'File dipilih: ' + files[0].name;
    el.classList.remove('hidden');
    announceUpload('File ' + files[0].name + ' berhasil dijatuhkan. Tekan Proses Remediasi untuk melanjutkan.');
}
document.getElementById('upload-form').addEventListener('submit', function(e) {
    const input = document.getElementById('document-input');
    if (!input.files.length) { e.preventDefault(); announceUpload('Harap pilih dokumen terlebih dahulu.'); input.focus(); return; }
    const steps = [
        {pct:20, msg:'Mengunggah dokumen ke server…'},
        {pct:40, msg:'Menyanitasi konten, menghapus header dan footer…'},
        {pct:65, msg:'Mendeteksi notasi matematika…'},
        {pct:85, msg:'Mengonversi ke teks natural dengan kecerdasan buatan…'},
        {pct:100,msg:'Hampir selesai, menyiapkan hasil…'},
    ];
    const container=document.getElementById('progress-container');
    const bar=document.getElementById('progress-bar');
    const label=document.getElementById('progress-label');
    const btn=document.getElementById('submit-btn');
    container.classList.remove('hidden'); btn.disabled=true;
    let i=0;
    const tick=setInterval(()=>{ if(i>=steps.length){clearInterval(tick);return;} bar.style.width=steps[i].pct+'%'; label.textContent=steps[i].msg; container.setAttribute('aria-valuenow',steps[i].pct); announceUpload(steps[i].msg); i++; },700);
});
window.addEventListener('DOMContentLoaded',()=>{
    @if(isset($remediationResult) && $remediationResult)
    const box=document.getElementById('result-box');
    setTimeout(()=>{ box.focus(); announceUpload('Remediasi selesai. Hasil dokumen sudah tersedia dan siap dibaca. Anda sekarang berada di area hasil remediasi.'); },400);
    @endif
});
</script>
@endpush