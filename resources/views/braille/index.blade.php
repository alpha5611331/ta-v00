@extends('layouts.app')
@section('title', 'Kirim ke EduBraille')
@section('main-label', 'Halaman pengiriman teks ke perangkat EduBraille')

@push('head')
<style>
    .chunk-cell {
        display:inline-flex; flex-direction:column; align-items:center; gap:.25rem;
        padding:.5rem .75rem; border-radius:.625rem; border:1.5px solid var(--bg-header);
        background:rgba(255,255,255,.7); font-size:.7rem; cursor:default;
        transition:background .15s, border-color .15s, transform .12s;
    }
    .chunk-cell:hover,.chunk-cell:focus {
        background:var(--bg-main); border-color:#000; transform:translateY(-2px);
        outline:2px solid #000; outline-offset:2px;
    }
    .chunk-braille { font-size:1.15rem; letter-spacing:.05em; }
    .chunk-latin   { color:#475569; font-family:monospace; }
    #send-bar { transition:width .3s ease; }
    .preview-area {
        font-family:'Plus Jakarta Sans',sans-serif; font-size:.875rem;
        background:rgba(255,255,255,.7); border:2px solid rgba(0,0,0,.15);
        border-radius:.875rem; padding:.75rem 1rem; color:#000;
        resize:vertical; min-height:140px; width:100%;
        transition:border-color .18s, box-shadow .18s;
    }
    .preview-area:focus { outline:none; border-color:#000; box-shadow:0 0 0 3px rgba(0,0,0,.1); }
</style>
@endpush

@section('content')

<h1 class="font-serif text-3xl font-bold text-black mb-2">Kirim ke EduBraille</h1>
<p class="text-sm text-slate-600 mb-6">Masukkan teks hasil remediasi, pilih ukuran chunk, lalu kirim ke perangkat EduBraille.</p>

@if(session('success'))
<div role="alert" aria-live="assertive" aria-atomic="true"
     class="flex items-start gap-3 rounded-xl px-5 py-4 mb-5 text-sm font-medium
            bg-green-50 border border-green-700 text-green-900">
    <svg aria-hidden="true" class="w-5 h-5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
    </svg>
    <div><p class="font-bold">Berhasil dikirim!</p><p class="mt-0.5">{{ session('success') }}</p></div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- FORM --}}
    <section aria-labelledby="form-heading">
        <h2 id="form-heading" class="text-lg font-bold text-black mb-4">Pengaturan Pengiriman</h2>
        <form id="braille-form" method="POST" action="{{ route('braille.send') }}" novalidate>
            @csrf
            @if($document)
                <input type="hidden" name="document_id" value="{{ $document->id }}">
            @endif

            <div class="mb-5">
                <label for="result_text" class="block text-sm font-semibold text-black mb-1.5">
                    Teks Hasil Remediasi <span aria-hidden="true" class="text-red-600">*</span>
                </label>
                <textarea id="result_text" name="result_text" class="preview-area" rows="6"
                    required aria-required="true" aria-describedby="text-hint"
                    placeholder="Tempel teks hasil remediasi di sini…">{{ old('result_text', $document->remediated_text ?? '') }}</textarea>
                <p id="text-hint" class="mt-1 text-xs text-slate-500">Hanya karakter alfanumerik dan tanda baca dasar yang dikonversi ke braille.</p>
                @error('result_text')<p role="alert" class="mt-1 text-xs text-red-700 font-medium">{{ $message }}</p>@enderror
            </div>

            <div class="mb-5">
                <label for="chunk_size" class="block text-sm font-semibold text-black mb-1.5">
                    Ukuran Chunk <span aria-hidden="true" class="text-red-600">*</span>
                </label>
                <select id="chunk_size" name="chunk_size"
                        class="w-full px-4 py-2.5 rounded-xl border-2 bg-white/60 text-sm text-black
                               border-black/15 focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                        required aria-required="true">
                    @foreach([['5','5 karakter - Lambat, sangat detail'],['10','10 karakter - Standar kecil'],['20','20 karakter - Standar umum'],['40','40 karakter - Cepat, ringkas']] as [$v,$l])
                        <option value="{{ $v }}" {{ old('chunk_size','20')===$v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                @error('chunk_size')<p role="alert" class="mt-1 text-xs text-red-700 font-medium">{{ $message }}</p>@enderror
            </div>

            <div class="mb-5">
                <label for="device_id" class="block text-sm font-semibold text-black mb-1.5">ID Perangkat EduBraille</label>
                <input type="text" id="device_id" name="device_id"
                       class="w-full px-4 py-2.5 rounded-xl border-2 bg-white/60 text-sm text-black
                              border-black/15 focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                       placeholder="DEFAULT" value="{{ old('device_id','DEFAULT') }}"
                       aria-describedby="device-hint">
                <p id="device-hint" class="mt-1 text-xs text-slate-500">Biarkan DEFAULT jika hanya ada satu perangkat terhubung.</p>
            </div>

            <button type="submit" id="send-submit"
                class="w-full flex items-center justify-center gap-2 px-6 py-3 rounded-xl
                       font-semibold text-sm text-white bg-black hover:bg-gray-800
                       focus:outline-none focus:ring-4 focus:ring-black focus:ring-offset-2
                       disabled:opacity-40 transition-colors"
                aria-label="Kirim teks ke perangkat EduBraille dalam format braille">
                <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                Kirim ke EduBraille
            </button>

            <div id="send-progress" class="hidden mt-4">
                <p id="send-label" class="text-sm font-medium text-black mb-2" role="status" aria-live="polite">Memproses…</p>
                <div class="w-full rounded-full h-2" style="background:var(--bg-sidebar);">
                    <div id="send-bar" class="h-2 rounded-full w-0" style="background:var(--bg-header);"></div>
                </div>
            </div>
        </form>
    </section>

    {{-- PREVIEW CHUNKS --}}
    <section aria-labelledby="preview-heading">
        <h2 id="preview-heading" class="text-lg font-bold text-black mb-4">Pratinjau Chunk Braille</h2>

        @if(isset($brailleChunks) && count($brailleChunks) > 0)
            <div class="flex items-center gap-3 mb-4 p-3 rounded-xl text-sm bg-green-50 border border-green-700 text-green-900">
                <svg aria-hidden="true" class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="font-bold">{{ count($brailleChunks) }} chunk berhasil dikirim</p>
                    <p>{{ $chunkSize ?? '?' }} karakter/chunk{{ isset($sentAt) ? ' · Pukul '.$sentAt : '' }}</p>
                </div>
            </div>
            <div class="rounded-2xl border-2 border-black/10 bg-white/40 p-4 max-h-[440px] overflow-y-auto"
                 style="scrollbar-width:thin;scrollbar-color:var(--bg-header) transparent;"
                 role="region" aria-label="Daftar {{ count($brailleChunks) }} chunk braille">
                <div class="flex flex-wrap gap-2" role="list">
                    @foreach($brailleChunks as $i => $chunk)
                    <div class="chunk-cell" role="listitem" tabindex="0"
                         aria-label="Chunk {{ $i+1 }}: {{ $chunk['text'] }}">
                        <span class="chunk-braille" aria-hidden="true">{{ $chunk['braille'] }}</span>
                        <span class="chunk-latin"   aria-hidden="true">{{ $chunk['text'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="flex flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed border-black/15 bg-white/30 p-10 text-center" role="status">
                <div aria-hidden="true" class="w-14 h-14 rounded-full flex items-center justify-center" style="background:var(--bg-sidebar);">
                    <svg class="w-7 h-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-black">Belum ada chunk dihasilkan</p>
                <p class="text-xs text-slate-500">Isi teks di sebelah kiri dan tekan "Kirim ke EduBraille".</p>
            </div>
        @endif
    </section>
</div>

@endsection

@push('scripts')
<script>
document.getElementById('braille-form').addEventListener('submit', function(e) {
    const text = document.getElementById('result_text').value.trim();
    if (!text) { e.preventDefault(); if(typeof announce==='function') announce('Harap isi teks terlebih dahulu.'); document.getElementById('result_text').focus(); return; }
    const btn=document.getElementById('send-submit'), prog=document.getElementById('send-progress'), bar=document.getElementById('send-bar'), lbl=document.getElementById('send-label');
    btn.disabled=true; prog.classList.remove('hidden');
    const steps=[{pct:25,msg:'Membersihkan teks…'},{pct:55,msg:'Memecah menjadi chunk…'},{pct:80,msg:'Mengonversi ke kode braille…'},{pct:95,msg:'Mengirim ke EduBraille…'}];
    let i=0; const t=setInterval(()=>{ if(i>=steps.length){clearInterval(t);return;} bar.style.width=steps[i].pct+'%'; lbl.textContent=steps[i].msg; if(typeof announce==='function') announce(steps[i].msg); i++; },450);
});
window.addEventListener('DOMContentLoaded',()=>{
    @if(isset($brailleChunks) && count($brailleChunks) > 0)
    if(typeof announce==='function') announce('{{ count($brailleChunks) }} chunk braille berhasil dikirim. Pratinjau tersedia di kolom kanan.');
    @endif
});
</script>
@endpush
