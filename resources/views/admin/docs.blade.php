@extends('layouts.app')
@section('title', 'Admin – Kelola Dokumen')
@section('main-label', 'Halaman admin: kelola seluruh dokumen di VOXORA')

@push('head')
<style>
    .tbl-row { transition:background .15s; }
    .tbl-row:hover { background:rgba(255,255,255,.65); }
    .badge-pdf  { background:#fee2e2;border:1px solid #fca5a5;color:#7f1d1d; }
    .badge-docx { background:#dbeafe;border:1px solid #93c5fd;color:#1e3a8a; }
    .badge-braille { background:#f0fdf4;border:1px solid #16a34a;color:#14532d; }
    .fi { padding:.6rem .9rem;border:2px solid rgba(0,0,0,.12);border-radius:.75rem;font-size:.85rem;background:rgba(255,255,255,.7);font-family:'Plus Jakarta Sans',sans-serif;color:#000;transition:border-color .18s; }
    .fi:focus { outline:none;border-color:#000;background:#fff;box-shadow:0 0 0 3px rgba(0,0,0,.08); }
    .page-btn { min-width:2.1rem;height:2.1rem;padding:0 .5rem;display:inline-flex;align-items:center;justify-content:center;border-radius:.5rem;border:1.5px solid transparent;font-size:.8rem;font-weight:500;text-decoration:none;color:#000;transition:background .15s; }
    .page-btn:hover { background:rgba(0,0,0,.07); }
    .page-btn.current { background:var(--coral,#FAAF90);border-color:#000;font-weight:700; }
    .page-btn[aria-disabled] { opacity:.4;pointer-events:none; }
    .page-btn:focus-visible { outline:3px solid #000;outline-offset:2px; }
    /* Bar visualisasi char_count */
    .char-bar { height:4px;border-radius:2px;background:var(--bg-header); min-width:4px; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<nav aria-label="Breadcrumb" class="mb-4">
    <ol class="flex items-center gap-2 text-sm text-slate-600" role="list">
        <li><a href="{{ route('admin.index') }}" class="underline underline-offset-2 hover:text-black focus:outline-none focus:ring-2 focus:ring-black rounded">Admin</a></li>
        <li aria-hidden="true"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></li>
        <li aria-current="page" class="font-semibold text-black">Kelola Dokumen</li>
    </ol>
</nav>

{{-- H1 + stat cards --}}
<div class="flex items-start justify-between flex-wrap gap-4 mb-6">
    <div>
        <h1 class="font-serif text-3xl font-bold text-black">Kelola Dokumen</h1>
        <p class="text-sm text-slate-600 mt-1">Seluruh dokumen yang telah diunggah dan diremediasi oleh pengguna.</p>
    </div>
    <div class="flex gap-3 flex-wrap">
        @foreach([
            [$statDocs['total'],     'Total',           'var(--bg-header)'],
            [$statDocs['pdf'],       'PDF',             '#fee2e2'],
            [$statDocs['docx'],      'DOCX',            '#dbeafe'],
            [$statDocs['braille_sent'],'Braille Terkirim','#f0fdf4'],
        ] as [$v,$l,$bg])
        <div class="px-4 py-2.5 rounded-xl text-center border border-black/10" style="background:{{ $bg }};">
            <p class="font-serif text-xl font-bold text-black">{{ $v }}</p>
            <p class="text-[10px] text-slate-600 font-bold uppercase tracking-wide">{{ $l }}</p>
        </div>
        @endforeach
    </div>
</div>

{{-- Total karakter --}}
<div class="mb-6 px-5 py-3.5 rounded-xl border border-black/08 text-sm flex items-center gap-3"
     style="background:rgba(255,255,255,.5);">
    <svg aria-hidden="true" class="w-5 h-5 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7"/>
    </svg>
    <span>Total karakter diremediasi: <strong>{{ number_format($statDocs['total_chars']) }}</strong> karakter</span>
</div>

@if(session('success'))
<div role="alert" aria-live="assertive" class="flex items-center gap-3 rounded-xl border border-green-700 bg-green-50 px-5 py-3.5 text-green-900 text-sm font-medium mb-5">
    <svg aria-hidden="true" class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
    {{ session('success') }}
</div>
@endif

{{-- Filter --}}
<section aria-labelledby="filter-h" class="mb-5">
    <h2 id="filter-h" class="sr-only">Filter Dokumen</h2>
    <form method="GET" action="{{ route('admin.docs') }}" class="flex flex-wrap gap-3 items-end" role="search" aria-label="Cari dan filter dokumen">
        <div class="flex-1 min-w-[200px] relative">
            <label for="q" class="sr-only">Cari nama file atau pemilik</label>
            <span aria-hidden="true" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
            </span>
            <input type="search" id="q" name="q" class="fi w-full pl-9" placeholder="Cari nama file atau pengguna…" value="{{ request('q') }}" aria-label="Cari dokumen">
        </div>

        {{-- Filter tipe --}}
        <div>
            <label for="type" class="sr-only">Filter tipe file</label>
            <select id="type" name="type" class="fi" aria-label="Filter berdasarkan tipe file">
                <option value="all"  {{ request('type','all')==='all'  ?'selected':'' }}>Semua Tipe</option>
                <option value="pdf"  {{ request('type')==='pdf'  ?'selected':'' }}>PDF saja</option>
                <option value="docx" {{ request('type')==='docx' ?'selected':'' }}>DOCX saja</option>
            </select>
        </div>

        {{-- Sort --}}
        <div>
            <label for="sort" class="sr-only">Urutan</label>
            <select id="sort" name="sort" class="fi" aria-label="Urutan dokumen">
                <option value="newest"    {{ request('sort','newest')==='newest'     ?'selected':'' }}>Terbaru</option>
                <option value="oldest"    {{ request('sort')==='oldest'    ?'selected':'' }}>Terlama</option>
                <option value="name"      {{ request('sort')==='name'      ?'selected':'' }}>Nama A–Z</option>
                <option value="size_desc" {{ request('sort')==='size_desc' ?'selected':'' }}>Karakter Terbanyak</option>
                <option value="size_asc"  {{ request('sort')==='size_asc'  ?'selected':'' }}>Karakter Tersedikit</option>
            </select>
        </div>

        <button type="submit" class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm text-white bg-black hover:bg-gray-800 transition-colors focus:outline-none focus:ring-4 focus:ring-black focus:ring-offset-2" aria-label="Terapkan filter">
            <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
            Filter
        </button>
        @if(request('q') || request('type') || request('sort'))
        <a href="{{ route('admin.docs') }}" class="flex items-center gap-1.5 px-4 py-2.5 rounded-xl font-semibold text-sm text-black border-2 border-black/15 hover:border-black focus:outline-none focus:ring-4 focus:ring-black focus:ring-offset-2 transition-all" aria-label="Hapus semua filter">Hapus Filter</a>
        @endif
    </form>
</section>

<p class="text-sm text-slate-600 mb-3" role="status" aria-live="polite" aria-atomic="true">
    Menampilkan <strong>{{ $docs->firstItem() ?? 0 }}–{{ $docs->lastItem() ?? 0 }}</strong> dari <strong>{{ $docs->total() }}</strong> dokumen
    @if(request('q'))
        untuk "<strong>{{ request('q') }}</strong>"
    @endif
</p>

{{-- Tabel --}}
<section aria-labelledby="tbl-h">
    <h2 id="tbl-h" class="sr-only">Tabel Dokumen</h2>
    <div class="rounded-2xl border-2 border-black/10 overflow-hidden backdrop-blur-sm" style="background:rgba(255,255,255,.5);">
        <div class="overflow-x-auto">
            <table class="w-full text-sm" aria-label="Daftar seluruh dokumen di VOXORA">
                <thead>
                    <tr class="text-left text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-black/08" style="background:var(--bg-sidebar);">
                        <th scope="col" class="px-5 py-3.5">Dokumen</th>
                        <th scope="col" class="px-5 py-3.5">Pemilik</th>
                        <th scope="col" class="px-5 py-3.5">Karakter</th>
                        <th scope="col" class="px-5 py-3.5">Braille</th>
                        <th scope="col" class="px-5 py-3.5">Diunggah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black/05">
                    @php $maxChars = $docs->max('char_count') ?: 1; @endphp
                    @forelse($docs as $doc)
                    <tr class="tbl-row" aria-label="Dokumen {{ $doc->original_filename }}">

                        {{-- Nama file + badge tipe --}}
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2.5">
                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase border flex-shrink-0
                                             {{ strtolower($doc->file_type ?? 'pdf') === 'docx' ? 'badge-docx' : 'badge-pdf' }}"
                                      aria-hidden="true">
                                    {{ strtoupper($doc->file_type ?? 'PDF') }}
                                </span>
                                <span class="font-medium text-black text-sm truncate max-w-[200px]"
                                      title="{{ $doc->original_filename }}">
                                    {{ $doc->original_filename }}
                                </span>
                            </div>
                        </td>

                        {{-- Pemilik --}}
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full flex-shrink-0 flex items-center justify-center text-[10px] font-bold text-black"
                                     style="background:var(--bg-sidebar);" aria-hidden="true">
                                    {{ strtoupper(substr($doc->user->name ?? '?', 0, 1)) }}
                                </div>
                                <span class="text-sm text-slate-700">{{ $doc->user->name ?? '-' }}</span>
                            </div>
                        </td>

                        {{-- Karakter + mini bar --}}
                        <td class="px-5 py-4">
                            <div class="flex flex-col gap-1">
                                <span class="font-semibold text-black text-xs">{{ number_format($doc->char_count ?? 0) }}</span>
                                <div class="w-20 bg-black/08 rounded-full h-1">
                                    <div class="char-bar rounded-full"
                                         style="width:{{ min(100, round(($doc->char_count / $maxChars) * 100)) }}%"
                                         role="presentation" aria-hidden="true"></div>
                                </div>
                            </div>
                        </td>

                        {{-- Braille dikirim --}}
                        <td class="px-5 py-4">
                            @if($doc->braille_sent_at)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold badge-braille"
                                      aria-label="Braille telah dikirim">
                                    <svg aria-hidden="true" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Terkirim
                                </span>
                            @else
                                <span class="text-xs text-slate-400" aria-label="Belum dikirim ke braille">—</span>
                            @endif
                        </td>

                        {{-- Tanggal --}}
                        <td class="px-5 py-4">
                            <time datetime="{{ is_object($doc->created_at) ? $doc->created_at->toIso8601String() : $doc->created_at }}"
                                  class="text-xs text-slate-600">
                                {{ is_object($doc->created_at) ? $doc->created_at->format('d M Y') : $doc->created_at }}
                            </time>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-slate-500 italic">
                            @if(request('q'))
                                Tidak ada dokumen untuk "<strong class='not-italic text-black'>{{ request('q') }}</strong>".
                            @else
                                Belum ada dokumen diunggah.
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Paginasi --}}
    @if($docs->hasPages())
    <nav aria-label="Navigasi halaman dokumen" class="flex items-center justify-center gap-1.5 mt-6 flex-wrap">
        @if($docs->onFirstPage())
            <span class="page-btn" aria-disabled="true" aria-label="Halaman sebelumnya tidak tersedia"><svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></span>
        @else
            <a href="{{ $docs->previousPageUrl() }}" class="page-btn" aria-label="Halaman sebelumnya"><svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></a>
        @endif
        @foreach($docs->getUrlRange(1, $docs->lastPage()) as $page => $url)
            @if($page == $docs->currentPage())
                <span class="page-btn current" aria-current="page" aria-label="Halaman {{ $page }}">{{ $page }}</span>
            @else
                <a href="{{ $url }}" class="page-btn" aria-label="Halaman {{ $page }}">{{ $page }}</a>
            @endif
        @endforeach
        @if($docs->hasMorePages())
            <a href="{{ $docs->nextPageUrl() }}" class="page-btn" aria-label="Halaman berikutnya"><svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></a>
        @else
            <span class="page-btn" aria-disabled="true" aria-label="Halaman berikutnya tidak tersedia"><svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></span>
        @endif
    </nav>
    @endif
</section>

@endsection

@push('scripts')
<script>
window.addEventListener('DOMContentLoaded',()=>{
    if(typeof announce==='function') announce('Halaman kelola dokumen. {{ $docs->total() }} dokumen ditemukan.');
});
</script>
@endpush