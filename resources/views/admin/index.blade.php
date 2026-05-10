@extends('layouts.app')
@section('title', 'Dashboard Admin')
@section('main-label', 'Halaman admin VOXORA – kelola pengguna dan dokumen')

@section('content')

<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <h1 class="font-serif text-3xl font-bold text-black">Dashboard Admin</h1>
    <span class="px-3 py-1 rounded-full text-xs font-bold bg-black text-white" aria-label="Status: mode administrator">
        ADMINISTRATOR
    </span>
</div>

{{-- ── Statistik utama ── --}}
<section aria-labelledby="stats-heading" class="mb-8">
    <h2 id="stats-heading" class="sr-only">Statistik Platform</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach([
            ['Total Pengguna',   $stats['total_users']   ?? '—', '#8BABF1', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
            ['Total Dokumen',    $stats['total_docs']    ?? '—', '#FAAF90', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z'],
            ['Chunk Dikirim',   $stats['total_chunks']  ?? '—', '#B3C7F7', 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
            ['Pengguna Aktif',  $stats['active_users']  ?? '—', '#FCC9B5', 'M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z'],
        ] as [$label,$val,$bg,$path])
        <div class="rounded-2xl border-2 border-black/10 bg-white/60 p-5 backdrop-blur-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                     style="background:{{ $bg }};" aria-hidden="true">
                    <svg class="w-5 h-5 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-black font-serif">{{ $val }}</p>
            <p class="text-xs text-slate-600 mt-1">{{ $label }}</p>
        </div>
        @endforeach
    </div>
</section>

{{-- ── Tabel pengguna ── --}}
<section aria-labelledby="users-heading" class="mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 id="users-heading" class="text-lg font-bold text-black">Pengguna Terdaftar</h2>
        <a href="{{ route('admin.users') }}"
           class="text-sm font-semibold underline underline-offset-2 hover:opacity-70 focus:outline-none focus:ring-2 focus:ring-black rounded"
           aria-label="Lihat semua pengguna">
            Lihat semua →
        </a>
    </div>

    <div class="rounded-2xl border-2 border-black/10 bg-white/60 overflow-hidden backdrop-blur-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm" aria-label="Daftar 5 pengguna terbaru">
                <thead>
                    <tr class="text-left text-xs font-bold uppercase tracking-wider text-slate-600 border-b border-black/10"
                        style="background:var(--bg-sidebar);">
                        <th scope="col" class="px-5 py-3">Nama</th>
                        <th scope="col" class="px-5 py-3">Email</th>
                        <th scope="col" class="px-5 py-3">Dokumen</th>
                        <th scope="col" class="px-5 py-3">Bergabung</th>
                        <th scope="col" class="px-5 py-3">
                            <span class="sr-only">Aksi</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black/05">
                    @forelse($recentUsers ?? [] as $u)
                    <tr class="hover:bg-white/60 transition-colors">
                        <td class="px-5 py-3 font-medium text-black">{{ $u->name }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ $u->email }}</td>
                        <td class="px-5 py-3">{{ $u->documents_count ?? 0 }}</td>
                        <td class="px-5 py-3 text-slate-500 text-xs">{{ $u->created_at?->format('d M Y') }}</td>
                        <td class="px-5 py-3">
                            <form method="POST" action="{{ route('admin.users.delete', $u->id) }}"
                                  onsubmit="return confirm('Hapus pengguna {{ addslashes($u->name) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="text-xs text-red-700 hover:text-red-900 font-semibold focus:outline-none focus:ring-2 focus:ring-red-600 rounded"
                                    aria-label="Hapus pengguna {{ $u->name }}">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-8 text-center text-slate-500 italic">
                            Belum ada pengguna terdaftar.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

{{-- ── Tabel dokumen terbaru ── --}}
<section aria-labelledby="docs-heading">
    <div class="flex items-center justify-between mb-4">
        <h2 id="docs-heading" class="text-lg font-bold text-black">Dokumen Terbaru</h2>
        <a href="{{ route('admin.docs') }}"
           class="text-sm font-semibold underline underline-offset-2 hover:opacity-70 focus:outline-none focus:ring-2 focus:ring-black rounded"
           aria-label="Lihat semua dokumen">
            Lihat semua →
        </a>
    </div>
    <div class="rounded-2xl border-2 border-black/10 bg-white/60 overflow-hidden backdrop-blur-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm" aria-label="Daftar dokumen terbaru">
                <thead>
                    <tr class="text-left text-xs font-bold uppercase tracking-wider text-slate-600 border-b border-black/10"
                        style="background:var(--bg-sidebar);">
                        <th scope="col" class="px-5 py-3">Nama File</th>
                        <th scope="col" class="px-5 py-3">Pemilik</th>
                        <th scope="col" class="px-5 py-3">Tipe</th>
                        <th scope="col" class="px-5 py-3">Karakter</th>
                        <th scope="col" class="px-5 py-3">Diunggah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black/05">
                    @forelse($recentDocs ?? [] as $doc)
                    <tr class="hover:bg-white/60 transition-colors">
                        <td class="px-5 py-3 font-medium text-black max-w-[200px] truncate">{{ $doc->original_filename ?? '-' }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ $doc->user?->name ?? '-' }}</td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 rounded text-xs font-bold uppercase {{ ($doc->file_type ?? 'pdf') === 'docx' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800' }}">
                                {{ strtoupper($doc->file_type ?? 'PDF') }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-600">{{ number_format($doc->char_count ?? 0) }}</td>
                        <td class="px-5 py-3 text-slate-500 text-xs">{{ $doc->created_at?->format('d M Y') ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-8 text-center text-slate-500 italic">
                            Belum ada dokumen diunggah.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

@endsection