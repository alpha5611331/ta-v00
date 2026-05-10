@extends('layouts.app')
@section('title', 'Profil Saya')
@section('main-label', 'Halaman profil pengguna')

@section('content')
<h1 class="font-serif text-3xl font-bold text-black mb-6">Profil Saya</h1>

@if(session('success'))
<div role="alert" aria-live="assertive" class="flex items-start gap-3 rounded-xl border border-green-700 bg-green-50 px-5 py-4 text-green-900 text-sm font-medium mb-5">
    <svg aria-hidden="true" class="w-5 h-5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
    {{ session('success') }}
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- INFO PROFIL --}}
    <section aria-labelledby="profile-heading">
        <h2 id="profile-heading" class="text-lg font-bold text-black mb-4">Informasi Akun</h2>
        <div class="rounded-2xl border-2 border-black/10 bg-white/60 p-6 backdrop-blur-sm">

            {{-- Avatar --}}
            <div class="flex items-center gap-4 mb-6 pb-5 border-b border-black/08">
                <div class="w-16 h-16 rounded-full flex items-center justify-center text-white text-2xl font-bold"
                     style="background:var(--bg-header);" aria-hidden="true">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <p class="text-lg font-bold text-black">{{ $user->name }}</p>
                    <p class="text-sm text-slate-600">{{ $user->email }}</p>
                    <p class="text-xs text-slate-500 mt-1">Bergabung: {{ $user->created_at?->format('d F Y') ?? '-' }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" novalidate aria-label="Formulir update informasi profil">
                @csrf @method('PUT')

                <div class="mb-4">
                    <label for="name" class="block text-sm font-semibold text-black mb-1.5">Nama Lengkap <span aria-hidden="true" class="text-red-600">*</span></label>
                    <input type="text" id="name" name="name"
                           class="w-full px-4 py-2.5 rounded-xl border-2 bg-white/70 text-sm text-black border-black/15 focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10 {{ $errors->has('name') ? 'border-red-600' : '' }}"
                           value="{{ old('name', $user->name) }}" required aria-required="true">
                    @error('name')<p role="alert" class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                </div>

                <div class="mb-5">
                    <label for="email" class="block text-sm font-semibold text-black mb-1.5">Alamat Email <span aria-hidden="true" class="text-red-600">*</span></label>
                    <input type="email" id="email" name="email"
                           class="w-full px-4 py-2.5 rounded-xl border-2 bg-white/70 text-sm text-black border-black/15 focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10 {{ $errors->has('email') ? 'border-red-600' : '' }}"
                           value="{{ old('email', $user->email) }}" required aria-required="true">
                    @error('email')<p role="alert" class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                </div>

                <button type="submit"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm text-white bg-black hover:bg-gray-800 focus:outline-none focus:ring-4 focus:ring-black focus:ring-offset-2 transition-colors"
                    aria-label="Simpan perubahan informasi profil">
                    Simpan Perubahan
                </button>
            </form>
        </div>
    </section>

    {{-- GANTI PASSWORD --}}
    <section aria-labelledby="password-heading">
        <h2 id="password-heading" class="text-lg font-bold text-black mb-4">Ganti Kata Sandi</h2>
        <div class="rounded-2xl border-2 border-black/10 bg-white/60 p-6 backdrop-blur-sm">
            <form method="POST" action="{{ route('profile.password') }}" novalidate aria-label="Formulir ganti kata sandi">
                @csrf @method('PUT')

                <div class="mb-4">
                    <label for="current_password" class="block text-sm font-semibold text-black mb-1.5">Kata Sandi Saat Ini <span aria-hidden="true" class="text-red-600">*</span></label>
                    <input type="password" id="current_password" name="current_password"
                           class="w-full px-4 py-2.5 rounded-xl border-2 bg-white/70 text-sm text-black border-black/15 focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10 {{ $errors->has('current_password') ? 'border-red-600' : '' }}"
                           required aria-required="true" autocomplete="current-password">
                    @error('current_password')<p role="alert" class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                </div>

                <div class="mb-4">
                    <label for="new_password" class="block text-sm font-semibold text-black mb-1.5">Kata Sandi Baru <span aria-hidden="true" class="text-red-600">*</span></label>
                    <input type="password" id="new_password" name="password"
                           class="w-full px-4 py-2.5 rounded-xl border-2 bg-white/70 text-sm text-black border-black/15 focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                           required aria-required="true" autocomplete="new-password" aria-describedby="pw-new-hint">
                    <p id="pw-new-hint" class="mt-1 text-xs text-slate-500">Minimal 8 karakter.</p>
                    @error('password')<p role="alert" class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                </div>

                <div class="mb-5">
                    <label for="password_confirmation" class="block text-sm font-semibold text-black mb-1.5">Konfirmasi Kata Sandi Baru <span aria-hidden="true" class="text-red-600">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="w-full px-4 py-2.5 rounded-xl border-2 bg-white/70 text-sm text-black border-black/15 focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                           required aria-required="true" autocomplete="new-password">
                </div>

                <button type="submit"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm text-white bg-black hover:bg-gray-800 focus:outline-none focus:ring-4 focus:ring-black focus:ring-offset-2 transition-colors"
                    aria-label="Simpan kata sandi baru">
                    Simpan Kata Sandi
                </button>
            </form>
        </div>
    </section>

</div>



@endsection