@extends('layouts.app')
@section('title', 'Profil Administrator')
@section('main-label', 'Halaman profil akun administrator VOXORA')

@section('content')

<h1 class="font-serif text-3xl font-bold text-black mb-6">Profil Administrator</h1>

{{-- Flash --}}
@if(session('success'))
<div role="alert" aria-live="assertive"
     class="flex items-start gap-3 rounded-xl border border-green-700 bg-green-50
            px-5 py-4 text-green-900 text-sm font-medium mb-5">
    <svg aria-hidden="true" class="w-5 h-5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0
        00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
        clip-rule="evenodd"/>
    </svg>
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div role="alert" aria-live="assertive"
     class="flex items-start gap-3 rounded-xl border border-red-700 bg-red-50
            px-5 py-4 text-red-900 text-sm font-medium mb-5">
    <svg aria-hidden="true" class="w-5 h-5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0
        1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
    </svg>
    {{ session('error') }}
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- ══════════════════════════════
         INFO AKUN ADMIN
    ══════════════════════════════ --}}
    <section aria-labelledby="profile-heading">
        <h2 id="profile-heading" class="text-lg font-bold text-black mb-4">Informasi Akun</h2>

        <div class="rounded-2xl border-2 border-black/10 bg-white/60 p-6 backdrop-blur-sm">

            {{-- Avatar + info --}}
            <div class="flex items-center gap-4 mb-6 pb-5 border-b border-black/08">
                <div class="w-16 h-16 rounded-full flex items-center justify-center
                            text-white text-2xl font-bold flex-shrink-0"
                     style="background: var(--bg-header);" aria-hidden="true">
                    {{ strtoupper(substr(auth()->user()?->name ?? 'A', 0, 1)) }}
                </div>
                <div>
                    <p class="text-lg font-bold text-black">{{ auth()->user()?->name }}</p>
                    <p class="text-sm text-slate-600">{{ auth()->user()?->email }}</p>
                    <div class="flex items-center gap-1.5 mt-1.5">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                                     text-[10px] font-bold text-white bg-black"
                              aria-label="Role: Administrator">
                            <svg aria-hidden="true" class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112
                                      2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0
                                      003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332
                                      9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            Administrator
                        </span>
                        <span class="text-xs text-slate-500">
                            Bergabung: {{ auth()->user()?->created_at?->format('d F Y') ?? '-' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Form update info --}}
            {{--
                PENTING: action ke route('profile.update.admin')
                bukan route('profile.update') yang milik user biasa
            --}}
            <form method="POST" action="{{ route('admin.profile.update') }}"
                  novalidate aria-label="Formulir update informasi akun administrator">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="name"
                           class="block text-sm font-semibold text-black mb-1.5">
                        Nama Lengkap
                        <span aria-hidden="true" class="text-red-600 ml-0.5">*</span>
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="w-full px-4 py-2.5 rounded-xl border-2 bg-white/70 text-sm
                               text-black transition-colors
                               {{ $errors->has('name') ? 'border-red-600' : 'border-black/15' }}
                               focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                        value="{{ old('name', auth()->user()?->name) }}"
                        required
                        aria-required="true"
                        autocomplete="name">
                    @error('name')
                        <p role="alert" class="mt-1 text-xs text-red-700 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-5">
                    <label for="email"
                           class="block text-sm font-semibold text-black mb-1.5">
                        Alamat Email
                        <span aria-hidden="true" class="text-red-600 ml-0.5">*</span>
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="w-full px-4 py-2.5 rounded-xl border-2 bg-white/70 text-sm
                               text-black transition-colors
                               {{ $errors->has('email') ? 'border-red-600' : 'border-black/15' }}
                               focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                        value="{{ old('email', auth()->user()?->email) }}"
                        required
                        aria-required="true"
                        autocomplete="email">
                    @error('email')
                        <p role="alert" class="mt-1 text-xs text-red-700 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold
                           text-sm text-white bg-black hover:bg-gray-800
                           focus:outline-none focus:ring-4 focus:ring-black focus:ring-offset-2
                           transition-colors"
                    aria-label="Simpan perubahan informasi akun administrator">
                    <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2
                              0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                    Simpan Perubahan
                </button>
            </form>
        </div>
    </section>

    {{-- ══════════════════════════════
         GANTI KATA SANDI
    ══════════════════════════════ --}}
    <section aria-labelledby="password-heading">
        <h2 id="password-heading" class="text-lg font-bold text-black mb-4">Ganti Kata Sandi</h2>

        <div class="rounded-2xl border-2 border-black/10 bg-white/60 p-6 backdrop-blur-sm">

            {{--
                PENTING: action ke route('profile.password.admin')
                bukan route('profile.password') yang milik user biasa
            --}}
            <form method="POST" action="{{ route('admin.profile.password.admin') }}"
                  novalidate aria-label="Formulir ganti kata sandi administrator">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="current_password"
                           class="block text-sm font-semibold text-black mb-1.5">
                        Kata Sandi Saat Ini
                        <span aria-hidden="true" class="text-red-600 ml-0.5">*</span>
                    </label>
                    <input
                        type="password"
                        id="current_password"
                        name="current_password"
                        class="w-full px-4 py-2.5 rounded-xl border-2 bg-white/70 text-sm
                               text-black transition-colors
                               {{ $errors->has('current_password') ? 'border-red-600' : 'border-black/15' }}
                               focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                        required
                        aria-required="true"
                        autocomplete="current-password">
                    @error('current_password')
                        <p role="alert" class="mt-1 text-xs text-red-700 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password"
                           class="block text-sm font-semibold text-black mb-1.5">
                        Kata Sandi Baru
                        <span aria-hidden="true" class="text-red-600 ml-0.5">*</span>
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="w-full px-4 py-2.5 rounded-xl border-2 bg-white/70 text-sm
                               text-black transition-colors
                               {{ $errors->has('password') ? 'border-red-600' : 'border-black/15' }}
                               focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                        required
                        aria-required="true"
                        autocomplete="new-password"
                        aria-describedby="pw-hint">
                    <p id="pw-hint" class="mt-1 text-xs text-slate-500">Minimal 8 karakter.</p>
                    @error('password')
                        <p role="alert" class="mt-1 text-xs text-red-700 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-5">
                    <label for="password_confirmation"
                           class="block text-sm font-semibold text-black mb-1.5">
                        Konfirmasi Kata Sandi Baru
                        <span aria-hidden="true" class="text-red-600 ml-0.5">*</span>
                    </label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        class="w-full px-4 py-2.5 rounded-xl border-2 border-black/15
                               bg-white/70 text-sm text-black
                               focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                        required
                        aria-required="true"
                        autocomplete="new-password">
                </div>

                <button
                    type="submit"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold
                           text-sm text-white bg-black hover:bg-gray-800
                           focus:outline-none focus:ring-4 focus:ring-black focus:ring-offset-2
                           transition-colors"
                    aria-label="Simpan kata sandi baru administrator">
                    <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2
                              2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Simpan Kata Sandi
                </button>
            </form>
        </div>
    </section>

</div>

{{-- ══════════════════════════════
     STATISTIK PLATFORM (khusus admin)
══════════════════════════════ --}}
<section aria-labelledby="stats-heading" class="mt-6">
    <h2 id="stats-heading" class="text-lg font-bold text-black mb-4">Ringkasan Platform</h2>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach([
            ['8',  'Total Pengguna',    'var(--bg-header)',          'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
            ['8',  'Total Dokumen',     'var(--accent-warm,#FAAF90)','M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z'],
            ['148','Chunk Dikirim',     'var(--bg-sidebar)',         'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
            ['5',  'Sesi EduBraille',   '#dbeafe',                  'M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18'],
        ] as [$val, $label, $bg, $path])
        <div class="rounded-2xl border-2 border-black/08 bg-white/60 p-5 text-center backdrop-blur-sm">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center mx-auto mb-3"
                 style="background:{{ $bg }};" aria-hidden="true">
                <svg class="w-5 h-5 text-black" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/>
                </svg>
            </div>
            <p class="text-2xl font-bold text-black font-serif">{{ $val }}</p>
            <p class="text-xs text-slate-600 mt-1">{{ $label }}</p>
        </div>
        @endforeach
    </div>

    <p class="mt-3 text-xs text-slate-400 text-right">
        * Data dummy – akan terhubung ke database setelah migration selesai
    </p>
</section>

@endsection

@push('scripts')
<script>
window.addEventListener('DOMContentLoaded', () => {
    if (typeof announce === 'function') {
        announce('Halaman profil administrator dimuat.');
    }
});
</script>
@endpush