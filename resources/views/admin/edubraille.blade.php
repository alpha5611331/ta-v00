@extends('layouts.app')
@section('title', 'Manajemen EduBraille')
@section('main-label', 'Halaman admin: konfigurasi dan manajemen perangkat EduBraille')

@push('head')
<style>
    .status-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;}
    .status-dot.online {background:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.2);}
    .status-dot.offline{background:#dc2626;box-shadow:0 0 0 3px rgba(220,38,38,.2);}
    .status-dot.unknown{background:#94a3b8;}
    .test-badge{display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1rem;border-radius:.75rem;font-size:.8rem;font-weight:600;border:1.5px solid;}
    .test-badge.success{background:#f0fdf4;border-color:#16a34a;color:#14532d;}
    .test-badge.error  {background:#fef2f2;border-color:#dc2626;color:#7f1d1d;}
    .test-badge.loading{background:var(--bg-sidebar);border-color:rgba(0,0,0,.15);color:#475569;}
    .config-input{width:100%;padding:.75rem 1rem;border:2px solid rgba(0,0,0,.15);border-radius:.75rem;font-family:'Plus Jakarta Sans',monospace;font-size:.875rem;color:#000;background:rgba(255,255,255,.75);transition:border-color .18s,box-shadow .18s;}
    .config-input:focus{outline:none;border-color:#000;background:#fff;box-shadow:0 0 0 3px rgba(0,0,0,.08);}
    .log-row{transition:background .15s;} .log-row:hover{background:rgba(255,255,255,.6);}
    .badge-success{background:#f0fdf4;border:1px solid #16a34a;color:#14532d;}
    .badge-failed {background:#fef2f2;border:1px solid #dc2626;color:#7f1d1d;}
    @keyframes pulse{0%,100%{opacity:1;}50%{opacity:.4;}} .pulsing{animation:pulse .9s ease-in-out infinite;}
    .chunk-preview{display:flex;flex-wrap:wrap;gap:.375rem;max-height:160px;overflow-y:auto;padding:.75rem;border-radius:.75rem;background:rgba(255,255,255,.5);border:1.5px solid rgba(0,0,0,.1);}
    .chunk-item{display:inline-flex;flex-direction:column;align-items:center;gap:.15rem;padding:.35rem .6rem;border-radius:.5rem;border:1px solid var(--bg-header);background:rgba(255,255,255,.7);font-size:.65rem;}
    .chunk-braille{font-size:1rem;letter-spacing:.04em;}
</style>
@endpush

@section('content')

<div class="flex items-start justify-between flex-wrap gap-4 mb-6">
    <div>
        <h1 class="font-serif text-3xl font-bold text-black">Manajemen EduBraille</h1>
        <p class="text-sm text-slate-600 mt-1">Kelola endpoint, uji koneksi, dan pantau log pengiriman perangkat EduBraille.</p>
    </div>
    <div id="connection-status" class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl border border-black/10"
         style="background:rgba(255,255,255,.6);" role="status" aria-live="polite" aria-label="Status koneksi EduBraille">
        <div class="status-dot unknown" id="status-dot" aria-hidden="true"></div>
        <span id="status-text" class="text-sm font-semibold text-black">
            {{ config('services.edubraille.endpoint') ? 'Endpoint terkonfigurasi' : 'Endpoint belum diset' }}
        </span>
    </div>
</div>

@if(session('success'))
<div role="alert" aria-live="assertive" class="flex items-center gap-3 rounded-xl border border-green-700 bg-green-50 px-5 py-3.5 text-green-900 text-sm font-medium mb-5">
    <svg aria-hidden="true" class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
    {{ session('success') }}
</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    <div class="xl:col-span-2 flex flex-col gap-6">

        {{-- Statistik --}}
        <section aria-labelledby="stat-h">
            <h2 id="stat-h" class="sr-only">Statistik Pengiriman</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach([
                    [$stats['total_sessions'],'Sesi Kirim','var(--bg-header)'],
                    [$stats['total_sent'],'Total Chunk','var(--accent-warm,#FAAF90)'],
                    [$stats['total_failed'],'Gagal','#fee2e2'],
                    [
                        $stats['last_activity']
                            ? (is_object($stats['last_activity']) ? $stats['last_activity']->diffForHumans() : \Carbon\Carbon::parse($stats['last_activity'])->diffForHumans())
                            : '—',
                        'Aktivitas Terakhir','var(--bg-sidebar)'
                    ],
                ] as [$v,$l,$bg])
                <div class="rounded-2xl border border-black/08 p-4 text-center" style="background:{{ $bg }};">
                    <p class="font-serif text-2xl font-bold text-black">{{ $v }}</p>
                    <p class="text-[10px] text-slate-600 font-bold uppercase tracking-wide mt-1">{{ $l }}</p>
                </div>
                @endforeach
            </div>
        </section>

        {{-- Registry Perangkat --}}
        <section aria-labelledby="device-h">
            <h2 id="device-h" class="text-lg font-bold text-black mb-4">Daftar Perangkat EduBraille</h2>
            <div class="rounded-2xl border-2 border-black/10 p-6 backdrop-blur-sm" style="background:rgba(255,255,255,.55);">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-slate-600">
                                <th class="py-2 pr-3">Device ID</th>
                                <th class="py-2 pr-3">Endpoint</th>
                                <th class="py-2 pr-3">Status</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="align-top">
                            @forelse(($devices ?? []) as $dev)
                                <tr class="border-t border-black/10">
                                    <td class="py-3 pr-3 font-mono font-semibold text-black">{{ $dev->device_id }}</td>
                                    <td class="py-3 pr-3 font-mono text-slate-700 break-all">{{ $dev->endpoint }}</td>
                                    <td class="py-3 pr-3">
                                        <span class="inline-flex items-center gap-2 px-2 py-1 rounded-lg text-xs font-semibold {{ $dev->is_active ? 'bg-green-50 text-green-900 border border-green-700' : 'bg-slate-100 text-slate-700 border border-black/10' }}">
                                            <span class="status-dot {{ $dev->is_active ? 'online' : 'unknown' }}" aria-hidden="true"></span>
                                            {{ $dev->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="py-3">
                                        <div class="flex flex-wrap gap-2">
                                            <form method="POST" action="{{ route('admin.edubraille.active') }}">
                                                @csrf
                                                <input type="hidden" name="device_id" value="{{ $dev->device_id }}">
                                                <button type="submit"
                                                        class="px-3 py-2 rounded-lg text-xs font-semibold border-2 border-black/20 bg-white/70 hover:bg-white"
                                                        aria-label="Jadikan perangkat {{ $dev->device_id }} sebagai aktif">
                                                    Jadikan Aktif
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.edubraille.delete') }}"
                                                  onsubmit="return confirm('Hapus perangkat {{ $dev->device_id }}?')">
                                                @csrf
                                                <input type="hidden" name="device_id" value="{{ $dev->device_id }}">
                                                <button type="submit"
                                                        class="px-3 py-2 rounded-lg text-xs font-semibold border-2 border-red-200 bg-red-50 text-red-800 hover:bg-red-100"
                                                        aria-label="Hapus perangkat {{ $dev->device_id }}">
                                                    Hapus
                                                </button>
                                            </form>
                                            <button type="button"
                                                    class="px-3 py-2 rounded-lg text-xs font-semibold border-2 border-black/20 bg-white/70 hover:bg-white"
                                                    aria-label="Uji koneksi perangkat {{ $dev->device_id }}"
                                                    onclick="testConnection('{{ $dev->device_id }}')">
                                                Test
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="border-t border-black/10">
                                    <td colspan="4" class="py-4 text-slate-500 italic">Belum ada perangkat terdaftar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        {{-- Tambah / Update Perangkat --}}
        <section aria-labelledby="config-h">
            <h2 id="config-h" class="text-lg font-bold text-black mb-4">Tambah / Update Perangkat</h2>
            <div class="rounded-2xl border-2 border-black/10 p-6 backdrop-blur-sm" style="background:rgba(255,255,255,.55);">
                <form method="POST" action="{{ route('admin.edubraille.save') }}" novalidate aria-label="Formulir tambah atau update perangkat EduBraille">
                    @csrf

                    <div class="mb-6">
                        <label for="device_id" class="block text-sm font-semibold text-black mb-1.5">
                            Device ID <span aria-hidden="true" class="text-red-600">*</span>
                        </label>
                        <input type="text" id="device_id" name="device_id"
                               class="config-input font-mono {{ $errors->has('device_id') ? 'border-red-600' : '' }}"
                               value="{{ old('device_id', '') }}"
                               required aria-required="true" aria-describedby="device-hint"
                               placeholder="DEFAULT">
                        <p id="device-hint" class="mt-1 text-xs text-slate-500">Identifikasi unik perangkat, misalnya DEFAULT, KELAS-1, LAB-A.</p>
                        @error('device_id')<p role="alert" class="mt-1 text-xs text-red-700 font-medium">{{ $message }}</p>@enderror
                    </div>

                    <div class="mb-6">
                        <label for="endpoint" class="block text-sm font-semibold text-black mb-1.5">
                            URL Endpoint <span aria-hidden="true" class="text-red-600">*</span>
                        </label>
                        <input type="url" id="endpoint" name="endpoint"
                               class="config-input font-mono {{ $errors->has('endpoint') ? 'border-red-600' : '' }}"
                               value="{{ old('endpoint', '') }}"
                               required aria-required="true" aria-describedby="endpoint-hint"
                               placeholder="http://192.168.1.100/receive">
                        <p id="endpoint-hint" class="mt-1 text-xs text-slate-500">Endpoint HTTP perangkat untuk menerima payload chunks.</p>
                        @error('endpoint')<p role="alert" class="mt-1 text-xs text-red-700 font-medium">{{ $message }}</p>@enderror
                    </div>

                    <div class="mb-6">
                        <label for="token" class="block text-sm font-semibold text-black mb-1.5">Token Autentikasi</label>
                        <input type="text" id="token" name="token"
                               class="config-input font-mono"
                               value="{{ old('token', '') }}"
                               aria-describedby="token-hint"
                               placeholder="Bearer token atau API key (opsional)">
                        <p id="token-hint" class="mt-1 text-xs text-slate-500">Dikosongkan jika endpoint tidak butuh autentikasi.</p>
                    </div>

                    <label class="inline-flex items-center gap-2 mb-6 text-sm font-semibold text-black">
                        <input type="checkbox" name="is_active" value="1" class="accent-black" {{ old('is_active', true) ? 'checked' : '' }}>
                        Aktif
                    </label>

                    <div class="flex flex-wrap gap-3">
                        <button type="submit"
                                class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm text-white bg-black hover:bg-gray-800 focus:outline-none focus:ring-4 focus:ring-black focus:ring-offset-2 transition-colors"
                                aria-label="Simpan perangkat EduBraille">
                            Simpan Perangkat
                        </button>
                    </div>
                </form>
                <div id="test-result" class="hidden mt-4" role="status" aria-live="polite" aria-atomic="true"></div>
            </div>
        </section>

        {{-- Konfigurasi Endpoint (legacy, .env) --}}
        <section aria-labelledby="config-legacy-h">
            <h2 id="config-legacy-h" class="text-lg font-bold text-black mb-4">Konfigurasi Endpoint (.env — legacy)</h2>
            <div class="rounded-2xl border-2 border-black/10 p-6 backdrop-blur-sm" style="background:rgba(255,255,255,.55);">
                <form method="POST" action="{{ route('admin.edubraille.save') }}" novalidate aria-label="Formulir konfigurasi endpoint EduBraille">
                    @csrf

                    <div class="mb-5">
                        <label for="endpoint" class="block text-sm font-semibold text-black mb-1.5">
                            URL Endpoint <span aria-hidden="true" class="text-red-600">*</span>
                        </label>
                        <input type="url" id="endpoint" name="endpoint"
                               class="config-input font-mono {{ $errors->has('endpoint') ? 'border-red-600' : '' }}"
                               value="{{ old('endpoint', $config['endpoint']) }}"
                               required aria-required="true" aria-describedby="endpoint-hint"
                               placeholder="http://192.168.1.100/receive">
                        <p id="endpoint-hint" class="mt-1 text-xs text-slate-500">
                            URL HTTP dari pihak ketiga EduBraille. Contoh: <code class="bg-slate-100 px-1 rounded">http://192.168.1.100/receive</code>
                        </p>
                        @error('endpoint')<p role="alert" class="mt-1 text-xs text-red-700 font-medium">{{ $message }}</p>@enderror
                    </div>

                    <div class="mb-5">
                        <label for="token" class="block text-sm font-semibold text-black mb-1.5">Token Autentikasi</label>
                        <div class="relative">
                            <input type="password" id="token" name="token"
                                   class="config-input font-mono pr-12"
                                   value="{{ old('token', $config['token']) }}"
                                   aria-describedby="token-hint"
                                   placeholder="Bearer token atau API key (opsional)">
                            <button type="button"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-black p-1 rounded focus:outline-none focus:ring-2 focus:ring-black"
                                    aria-label="Tampilkan atau sembunyikan token"
                                    onclick="this.previousElementSibling.type==='password'?(this.previousElementSibling.type='text',this.setAttribute('aria-label','Sembunyikan token')):(this.previousElementSibling.type='password',this.setAttribute('aria-label','Tampilkan token'))">
                                <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                        <p id="token-hint" class="mt-1 text-xs text-slate-500">Dikosongkan jika endpoint tidak butuh autentikasi.</p>
                    </div>

                    <div class="mb-6">
                        <label for="device_id" class="block text-sm font-semibold text-black mb-1.5">
                            Device ID <span aria-hidden="true" class="text-red-600">*</span>
                        </label>
                        <input type="text" id="device_id" name="device_id"
                               class="config-input font-mono {{ $errors->has('device_id') ? 'border-red-600' : '' }}"
                               value="{{ old('device_id', $config['device_id']) }}"
                               required aria-required="true" aria-describedby="device-hint"
                               placeholder="DEFAULT">
                        <p id="device-hint" class="mt-1 text-xs text-slate-500">Identifikasi unik perangkat. Biarkan DEFAULT jika hanya satu perangkat.</p>
                        @error('device_id')<p role="alert" class="mt-1 text-xs text-red-700 font-medium">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button type="button" id="test-btn" onclick="testConnection()"
                                class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm text-black border-2 border-black/20 hover:border-black bg-white/60 hover:bg-white/80 focus:outline-none focus:ring-4 focus:ring-black focus:ring-offset-2 transition-all"
                                aria-label="Uji koneksi ke perangkat EduBraille">
                            <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Uji Koneksi
                        </button>
                    </div>
                </form>
            </div>
        </section>

        {{-- Test Kirim --}}
        <section aria-labelledby="test-send-h">
            <h2 id="test-send-h" class="text-lg font-bold text-black mb-4">Test Kirim ke Perangkat</h2>
            <div class="rounded-2xl border-2 border-black/10 p-6 backdrop-blur-sm" style="background:rgba(255,255,255,.55);">
                <div class="mb-4">
                    <label for="test-text" class="block text-sm font-semibold text-black mb-1.5">Teks yang Akan Dikirim</label>
                    <textarea id="test-text" rows="3" class="config-input resize-none"
                              placeholder="Ketik teks test di sini…"
                              aria-describedby="test-hint">Halo ini adalah test pengiriman dari VOXORA ke EduBraille.</textarea>
                    <p id="test-hint" class="mt-1 text-xs text-slate-500">Teks akan dipecah menjadi chunk dan dikirim ke perangkat.</p>
                </div>
                <div class="flex items-center gap-3 mb-4 flex-wrap">
                    <div>
                        <label for="test-chunk-size" class="sr-only">Ukuran chunk</label>
                        <select id="test-chunk-size"
                                class="px-3 py-2 rounded-lg border-2 border-black/15 bg-white/70 text-sm font-medium focus:outline-none focus:border-black"
                                aria-label="Ukuran chunk untuk test">
                            <option value="5">5 karakter/chunk</option>
                            <option value="10">10 karakter/chunk</option>
                            <option value="20" selected>20 karakter/chunk</option>
                            <option value="40">40 karakter/chunk</option>
                        </select>
                    </div>
                    <button type="button" onclick="sendTestChunk()" id="send-test-btn"
                            class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm text-white border-2 border-black hover:brightness-90 focus:outline-none focus:ring-4 focus:ring-orange-800 focus:ring-offset-2 transition-all"
                            style="background:#c2410c;" aria-label="Kirim teks test ke EduBraille">
                        <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        Kirim Test
                    </button>
                </div>
                <div id="chunk-preview-area" class="hidden mb-3">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Pratinjau Chunk</p>
                    <div id="chunk-preview" class="chunk-preview" role="list" aria-label="Pratinjau chunk braille"></div>
                </div>
                <div id="send-result" class="hidden" role="status" aria-live="polite" aria-atomic="true"></div>
            </div>
        </section>
    </div>

    {{-- LOG PENGIRIMAN --}}
    <section aria-labelledby="log-h">
        <h2 id="log-h" class="text-lg font-bold text-black mb-4">Log Pengiriman</h2>
        <div class="rounded-2xl border-2 border-black/10 overflow-hidden backdrop-blur-sm" style="background:rgba(255,255,255,.5);">
            <div class="px-4 py-3 border-b border-black/08 flex items-center justify-between" style="background:var(--bg-sidebar);">
                <p class="text-xs font-bold text-slate-600 uppercase tracking-wide">Riwayat Pengiriman</p>
                <span class="text-xs text-slate-500">{{ $logs->count() }} entri</span>
            </div>
            <ul role="list" aria-label="Log pengiriman EduBraille" class="divide-y divide-black/05">
                @forelse($logs as $log)
                <li class="log-row px-4 py-3.5" aria-label="{{ $log->user }}, {{ $log->doc }}, {{ $log->chunks }} chunk, {{ $log->status === 'success' ? 'berhasil' : 'gagal' }}">
                    <div class="flex items-start justify-between gap-2 mb-1">
                        <p class="text-xs font-semibold text-black truncate max-w-[160px]" title="{{ $log->doc }}">{{ $log->doc }}</p>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold {{ $log->status === 'success' ? 'badge-success' : 'badge-failed' }} flex-shrink-0" aria-hidden="true">
                            {{ $log->status === 'success' ? '✓' : '✗' }}
                        </span>
                    </div>
                    <p class="text-[10px] text-slate-600 mb-0.5">
                        <span class="font-medium">{{ $log->user }}</span>
                        · {{ $log->chunks }} chunk ({{ $log->chunk_size }}kar)
                    </p>
                    <time class="text-[10px] text-slate-400" datetime="{{ is_object($log->sent_at) ? $log->sent_at->toIso8601String() : $log->sent_at }}">
                        {{ is_object($log->sent_at) ? $log->sent_at->diffForHumans() : $log->sent_at }}
                    </time>
                </li>
                @empty
                <li class="px-4 py-8 text-center text-slate-500 text-sm italic">Belum ada log pengiriman.</li>
                @endforelse
            </ul>
        </div>

        {{-- Info .env aktif --}}
        <div class="mt-4 rounded-xl border border-black/10 p-4 text-xs" style="background:rgba(255,255,255,.4);">
            <p class="font-bold text-black mb-2">Konfigurasi Aktif (.env)</p>
            <div class="space-y-2 font-mono text-slate-600 break-all">
                <div><span class="text-slate-400 not-italic font-sans text-[10px] uppercase tracking-wide">ENDPOINT</span><br>{{ $config['endpoint'] ?: '—' }}</div>
                <div><span class="text-slate-400 not-italic font-sans text-[10px] uppercase tracking-wide">DEVICE_ID</span><br>{{ $config['device_id'] ?: '—' }}</div>
                <div><span class="text-slate-400 not-italic font-sans text-[10px] uppercase tracking-wide">TOKEN</span><br>{{ $config['token'] ? str_repeat('•', min(strlen($config['token']),20)) : '—' }}</div>
            </div>
        </div>
    </section>
</div>

@endsection

@push('scripts')
<script>
const CSRF     = document.querySelector('meta[name="csrf-token"]').content;
const TEST_URL = "{{ route('admin.edubraille.test') }}";
const SEND_URL = "{{ route('admin.edubraille.send') }}";
const BRAILLE  = {'a':'⠁','b':'⠃','c':'⠉','d':'⠙','e':'⠑','f':'⠋','g':'⠛','h':'⠓','i':'⠊','j':'⠚','k':'⠅','l':'⠇','m':'⠍','n':'⠝','o':'⠕','p':'⠏','q':'⠟','r':'⠗','s':'⠎','t':'⠞','u':'⠥','v':'⠧','w':'⠺','x':'⠭','y':'⠽','z':'⠵',' ':'⠀',',':'⠂','.':'⠄','?':'⠦','!':'⠖'};

function toBraille(str){ return [...str.toLowerCase()].map(c=>BRAILLE[c]||'⠿').join(''); }

async function testConnection(deviceId = '') {
    const btn=document.getElementById('test-btn'), result=document.getElementById('test-result');
    const dot=document.getElementById('status-dot'), stxt=document.getElementById('status-text');
    btn.disabled=true;
    result.className='mt-4';
    result.innerHTML='<span class="test-badge loading pulsing">Menguji koneksi ke EduBraille…</span>';
    result.classList.remove('hidden');
    dot.className='status-dot unknown pulsing';
    if(typeof announce==='function') announce('Menguji koneksi ke EduBraille…');
    try {
        const res=await fetch(TEST_URL,{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({device_id: deviceId})});
        const data=await res.json(); const ok=data.status==='success';
        result.innerHTML=`<span class="test-badge ${ok?'success':'error'}"><svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="${ok?'M5 13l4 4L19 7':'M6 18L18 6M6 6l12 12'}"/></svg>${data.message}${data.latency?' ('+data.latency+')':''}</span>`;
        dot.className=`status-dot ${ok?'online':'offline'}`;
        stxt.textContent=ok?'Perangkat online':'Perangkat tidak merespons';
        if(typeof announce==='function') announce(data.message);
    } catch {
        result.innerHTML='<span class="test-badge error">Gagal menghubungi server.</span>';
        dot.className='status-dot offline'; stxt.textContent='Koneksi gagal';
    } finally { btn.disabled=false; }
}

async function sendTestChunk() {
    const text=document.getElementById('test-text').value.trim();
    const size=parseInt(document.getElementById('test-chunk-size').value);
    const btn=document.getElementById('send-test-btn');
    const result=document.getElementById('send-result');
    const previewArea=document.getElementById('chunk-preview-area');
    const previewEl=document.getElementById('chunk-preview');
    if(!text){ if(typeof announce==='function') announce('Harap isi teks test.'); return; }
    btn.disabled=true;
    if(typeof announce==='function') announce('Mengirim chunk test…');
    // Preview chunk
    const chunks=text.match(new RegExp(`.{1,${size}}`,'g'))||[];
    previewEl.innerHTML=chunks.map(c=>`<div class="chunk-item" role="listitem"><span class="chunk-braille" aria-hidden="true">${toBraille(c)}</span><span aria-hidden="true" style="color:#94a3b8">${c}</span></div>`).join('');
    previewArea.classList.remove('hidden');
    try {
        const res=await fetch(SEND_URL,{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({text,chunk_size:size})});
        const data=await res.json(); const ok=data.status==='success';
        result.innerHTML=`<span class="test-badge ${ok?'success':'error'} mt-3"><svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="${ok?'M5 13l4 4L19 7':'M6 18L18 6M6 6l12 12'}"/></svg>${data.message}${data.simulated?' (simulasi)':''}</span>`;
        result.classList.remove('hidden');
        if(typeof announce==='function') announce(data.message);
    } catch {
        result.innerHTML='<span class="test-badge error mt-3">Gagal menghubungi server.</span>';
        result.classList.remove('hidden');
    } finally { btn.disabled=false; }
}

window.addEventListener('DOMContentLoaded',()=>{
    @if(config('services.edubraille.endpoint'))
    setTimeout(testConnection, 900);
    @endif
    if(typeof announce==='function') announce('Halaman Manajemen EduBraille dimuat. {{ config("services.edubraille.endpoint") ? "Endpoint terkonfigurasi." : "Endpoint belum dikonfigurasi." }}');
});
</script>
@endpush