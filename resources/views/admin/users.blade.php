@extends('layouts.app')
@section('title', 'Admin – Kelola Pengguna')
@section('main-label', 'Halaman admin: kelola pengguna VOXORA')

@push('head')
<style>
    .tbl-row { transition:background .15s; }
    .tbl-row:hover { background:rgba(255,255,255,.65); }
    .badge-active   { background:#f0fdf4;border:1px solid #16a34a;color:#14532d; }
    .badge-inactive { background:#fef2f2;border:1px solid #dc2626;color:#7f1d1d; }
    .badge-admin    { background:#eff6ff;border:1px solid #2563eb;color:#1e3a8a; }
    .fi { padding:.6rem .9rem;border:2px solid rgba(0,0,0,.12);border-radius:.75rem;font-size:.85rem;background:rgba(255,255,255,.7);font-family:'Plus Jakarta Sans',sans-serif;color:#000;transition:border-color .18s; }
    .fi:focus { outline:none;border-color:#000;background:#fff;box-shadow:0 0 0 3px rgba(0,0,0,.08); }
    .page-btn { min-width:2.1rem;height:2.1rem;padding:0 .5rem;display:inline-flex;align-items:center;justify-content:center;border-radius:.5rem;border:1.5px solid transparent;font-size:.8rem;font-weight:500;text-decoration:none;color:#000;transition:background .15s; }
    .page-btn:hover { background:rgba(0,0,0,.07); }
    .page-btn.current { background:var(--coral,#FAAF90);border-color:#000;font-weight:700; }
    .page-btn[aria-disabled] { opacity:.4;pointer-events:none; }
    .page-btn:focus-visible { outline:3px solid #000;outline-offset:2px; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<nav aria-label="Breadcrumb" class="mb-4">
    <ol class="flex items-center gap-2 text-sm text-slate-600" role="list">
        <li><a href="{{ route('admin.index') }}" class="underline underline-offset-2 hover:text-black focus:outline-none focus:ring-2 focus:ring-black rounded">Admin</a></li>
        <li aria-hidden="true"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></li>
        <li aria-current="page" class="font-semibold text-black">Kelola Pengguna</li>
    </ol>
</nav>

{{-- H1 + stat cards --}}
<div class="flex items-start justify-between flex-wrap gap-4 mb-6">
    <div>
        <h1 class="font-serif text-3xl font-bold text-black">Kelola Pengguna</h1>
        <p class="text-sm text-slate-600 mt-1">Pantau dan kelola semua akun yang terdaftar.</p>
    </div>
    <div class="flex gap-3 flex-wrap">
        @foreach([[$users->total(),'Total','var(--bg-header)'],[$totalActive,'Aktif','#dcfce7'],[$totalInactive,'Nonaktif','#fee2e2']] as [$v,$l,$bg])
        <div class="px-4 py-2.5 rounded-xl text-center border border-black/10" style="background:{{ $bg }};">
            <p class="font-serif text-xl font-bold text-black">{{ $v }}</p>
            <p class="text-[10px] text-slate-600 font-bold uppercase tracking-wide">{{ $l }}</p>
        </div>
        @endforeach
    </div>
</div>

@if(session('success'))
<div role="alert" aria-live="assertive" class="flex items-center gap-3 rounded-xl border border-green-700 bg-green-50 px-5 py-3.5 text-green-900 text-sm font-medium mb-5">
    <svg aria-hidden="true" class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
    {{ session('success') }}
</div>
@endif

{{-- Filter --}}
<section aria-labelledby="filter-h" class="mb-5">
    <h2 id="filter-h" class="sr-only">Filter Pengguna</h2>
    <form method="GET" action="{{ route('admin.users') }}" class="flex flex-wrap gap-3 items-end" role="search" aria-label="Cari dan filter pengguna">
        <div class="flex-1 min-w-[200px] relative">
            <label for="q" class="sr-only">Cari nama atau email</label>
            <span aria-hidden="true" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
            </span>
            <input type="search" id="q" name="q" class="fi w-full pl-9" placeholder="Cari nama atau email…" value="{{ request('q') }}" aria-label="Cari pengguna berdasarkan nama atau email">
        </div>
        <div>
            <label for="sort" class="sr-only">Urutan</label>
            <select id="sort" name="sort" class="fi" aria-label="Urutan pengguna">
                <option value="newest" {{ request('sort','newest')==='newest'?'selected':'' }}>Terbaru</option>
                <option value="oldest" {{ request('sort')==='oldest'?'selected':'' }}>Terlama</option>
                <option value="name"   {{ request('sort')==='name'  ?'selected':'' }}>Nama A–Z</option>
                <option value="docs"   {{ request('sort')==='docs'  ?'selected':'' }}>Dokumen Terbanyak</option>
            </select>
        </div>
        <button type="submit" class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm text-white bg-black hover:bg-gray-800 transition-colors focus:outline-none focus:ring-4 focus:ring-black focus:ring-offset-2" aria-label="Terapkan filter">
            <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
            Filter
        </button>
        @if(request('q') || request('sort'))
        <a href="{{ route('admin.users') }}" class="flex items-center gap-1.5 px-4 py-2.5 rounded-xl font-semibold text-sm text-black border-2 border-black/15 hover:border-black focus:outline-none focus:ring-4 focus:ring-black focus:ring-offset-2 transition-all" aria-label="Hapus filter">Hapus Filter</a>
        @endif
    </form>
</section>

<p class="text-sm text-slate-600 mb-3" role="status" aria-live="polite" aria-atomic="true">
    Menampilkan <strong>{{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }}</strong> dari <strong>{{ $users->total() }}</strong> pengguna@if(request('q')) untuk "<strong>{{ request('q') }}</strong>"@endif
</p>

{{-- Tabel --}}
<section aria-labelledby="tbl-h">
    <h2 id="tbl-h" class="sr-only">Tabel Pengguna</h2>
    <div class="rounded-2xl border-2 border-black/10 overflow-hidden backdrop-blur-sm" style="background:rgba(255,255,255,.5);">
        <div class="overflow-x-auto">
            <table class="w-full text-sm" aria-label="Daftar pengguna VOXORA">
                <thead>
                    <tr class="text-left text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-black/08" style="background:var(--bg-sidebar);">
                        <th scope="col" class="px-5 py-3.5">Pengguna</th>
                        <th scope="col" class="px-5 py-3.5">Status</th>
                        <th scope="col" class="px-5 py-3.5 text-right">Dokumen</th>
                        <th scope="col" class="px-5 py-3.5">Bergabung</th>
                        <th scope="col" class="px-5 py-3.5"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black/05">
                    @forelse($users as $u)
                    <tr class="tbl-row" aria-label="Pengguna {{ $u->name }}">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center text-sm font-bold text-black" style="background:var(--bg-header);" aria-hidden="true">{{ strtoupper(substr($u->name,0,1)) }}</div>
                                <div>
                                    <p class="font-semibold text-black">{{ $u->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $u->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-1.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold {{ $u->is_active ? 'badge-active' : 'badge-inactive' }}" aria-label="Status: {{ $u->is_active ? 'Aktif' : 'Nonaktif' }}">
                                    <span aria-hidden="true" class="w-1.5 h-1.5 rounded-full mr-1 {{ $u->is_active ? 'bg-green-600' : 'bg-red-500' }}"></span>
                                    {{ $u->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                                @if($u->is_admin)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold badge-admin" aria-label="Role: Admin">Admin</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <span class="font-semibold text-black">{{ $u->documents_count }}</span>
                            <span class="text-slate-400 text-xs ml-1">dok.</span>
                        </td>
                        <td class="px-5 py-4">
                            <time datetime="{{ is_object($u->created_at) ? $u->created_at->toIso8601String() : $u->created_at }}" class="text-slate-600 text-xs">
                                {{ is_object($u->created_at) ? $u->created_at->format('d M Y') : $u->created_at }}
                            </time>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3 justify-end">
                                <a href="{{ route('admin.docs') }}?q={{ urlencode($u->name) }}" class="text-xs font-semibold text-blue-700 hover:text-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-600 rounded" aria-label="Lihat dokumen milik {{ $u->name }}">Dokumen</a>
                                @if(!$u->is_admin)
                                <form method="POST" action="{{ route('admin.users.delete', $u->id) }}" onsubmit="return confirm('Hapus akun {{ addslashes($u->name) }}? Tindakan ini tidak dapat dibatalkan.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-red-700 hover:text-red-900 focus:outline-none focus:ring-2 focus:ring-red-600 rounded" aria-label="Hapus akun {{ $u->name }}">Hapus</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-slate-500 italic">
                            @if(request('q'))Tidak ada pengguna untuk "<strong class="not-italic text-black">{{ request('q') }}</strong>".
                            @else Belum ada pengguna terdaftar. @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Paginasi --}}
    @if($users->hasPages())
    <nav aria-label="Navigasi halaman pengguna" class="flex items-center justify-center gap-1.5 mt-6 flex-wrap">
        @if($users->onFirstPage())
            <span class="page-btn" aria-disabled="true" aria-label="Halaman sebelumnya tidak tersedia"><svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></span>
        @else
            <a href="{{ $users->previousPageUrl() }}" class="page-btn" aria-label="Halaman sebelumnya"><svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></a>
        @endif
        @foreach($users->getUrlRange(1, $users->lastPage()) as $page => $url)
            @if($page == $users->currentPage())
                <span class="page-btn current" aria-current="page" aria-label="Halaman {{ $page }}">{{ $page }}</span>
            @else
                <a href="{{ $url }}" class="page-btn" aria-label="Halaman {{ $page }}">{{ $page }}</a>
            @endif
        @endforeach
        @if($users->hasMorePages())
            <a href="{{ $users->nextPageUrl() }}" class="page-btn" aria-label="Halaman berikutnya"><svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></a>
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
    if(typeof announce==='function') announce('Halaman kelola pengguna. {{ $users->total() }} pengguna terdaftar.');
});
</script>
@endpush