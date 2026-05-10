@extends('layouts.app')

@section('title', 'Tanya Dokumen')
@section('main-label', 'Halaman tanya jawab dokumen dengan asisten AI VOXORA')

@push('head')
<style>
    /* ── Chat bubble ── */
    .bubble-user {
        background: #000;
        color: #fff;
        border-radius: 1.25rem 1.25rem 0.25rem 1.25rem;
        max-width: 75%;
        margin-left: auto;
    }
    .bubble-bot {
        background: rgba(255,255,255,.75);
        border: 1.5px solid rgba(0,0,0,.12);
        border-radius: 1.25rem 1.25rem 1.25rem 0.25rem;
        max-width: 80%;
    }
    .bubble-bot.speaking {
        border-color: var(--bg-header);
        box-shadow: 0 0 0 3px rgba(139,171,241,.35);
    }

    /* ── Chat window ── */
    #chat-window {
        height: calc(100vh - 360px);
        min-height: 280px;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: var(--bg-header) transparent;
    }
    #chat-window::-webkit-scrollbar { width: 6px; }
    #chat-window::-webkit-scrollbar-thumb { background: var(--bg-header); border-radius: 4px; }

    /* ── Typing dots ── */
    .typing-dot {
        width: 7px; height: 7px;
        background: var(--bg-header);
        border-radius: 50%;
        animation: typingBounce .9s infinite;
    }
    .typing-dot:nth-child(2) { animation-delay: .15s; }
    .typing-dot:nth-child(3) { animation-delay: .30s; }
    @keyframes typingBounce {
        0%,60%,100% { transform: translateY(0); }
        30%          { transform: translateY(-7px); }
    }

    /* ── VUI pulse ring ── */
    @keyframes pulse-ring {
        0%   { transform: scale(1);   opacity: .6; }
        100% { transform: scale(1.5); opacity: 0;  }
    }
    .vui-ring {
        position: absolute; inset: 0;
        border-radius: 50%;
        background: var(--bg-header);
        animation: pulse-ring 1.2s ease-out infinite;
    }
    .vui-ring:nth-child(2) { animation-delay: .4s; }

    /* ── Input area ── */
    #question-input {
        resize: none;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: .9rem;
        background: rgba(255,255,255,.75);
        border: 2px solid rgba(0,0,0,.15);
        border-radius: .875rem;
        padding: .75rem 1rem;
        color: #000;
        transition: border-color .18s, background .18s, box-shadow .18s;
        width: 100%;
    }
    #question-input:focus {
        outline: none;
        border-color: #000;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(0,0,0,.1);
    }

    /* ── Speak button active ── */
    #speak-btn.active {
        background: #b91c1c !important;
        animation: none;
    }
</style>
@endpush

@section('content')

    {{-- ── H1 ── --}}
    <div class="flex items-start justify-between mb-5 flex-wrap gap-3">
        <div>
            <h1 class="font-serif text-3xl font-bold text-black">Tanya Dokumen</h1>
            @if($document)
                <p class="text-sm text-slate-600 mt-1">
                    Konteks: <strong>{{ $document->original_filename }}</strong>
                </p>
            @else
                <p class="text-sm text-slate-600 mt-1">
                    Tidak ada dokumen dipilih — tanya topik umum STEM.
                </p>
            @endif
        </div>

        @if($document)
            <a href="{{ route('pustaka.show', $document->id) }}"
               class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-black
                      border-2 border-black/20 hover:border-black hover:bg-white/60
                      focus:outline-none focus:ring-4 focus:ring-black focus:ring-offset-2 transition-all"
               aria-label="Kembali ke detail dokumen {{ $document->original_filename }}">
                <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali
            </a>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════
         VUI STATUS BAR
    ═══════════════════════════════════════════ --}}
    <div id="vui-status-bar"
         class="flex items-center gap-3 mb-4 px-4 py-2.5 rounded-xl text-sm font-medium"
         style="background: var(--bg-sidebar);"
         role="status" aria-live="polite" aria-atomic="true">

        {{-- Pulse VUI indicator --}}
        <div id="vui-indicator" class="relative w-5 h-5 flex-shrink-0">
            <div class="absolute inset-0 rounded-full" style="background: var(--bg-header);"></div>
            {{-- rings muncul saat bot bicara --}}
        </div>

        <span id="vui-status-text" class="text-black">
            Asisten VOXORA siap. Ketik pertanyaan atau tekan ikon mikrofon untuk bicara.
        </span>

        {{-- Tombol mute TTS --}}
        <button
            id="mute-btn"
            type="button"
            class="ml-auto flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold
                   border-2 border-black/15 bg-white/60 hover:bg-white transition-all
                   focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-1"
            aria-pressed="false"
            aria-label="Suara asisten: aktif. Tekan untuk menonaktifkan suara.">
            <svg id="mute-icon-on" aria-hidden="true" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.536 8.464a5 5 0 010 7.072M12 6v12m-3.536-9.536a5 5 0 000 7.072M6.343 6.343a8 8 0 000 11.314"/>
            </svg>
            <svg id="mute-icon-off" aria-hidden="true" class="w-3.5 h-3.5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
            </svg>
            <span id="mute-label">Suara Aktif</span>
        </button>
    </div>

    {{-- ═══════════════════════════════════════════
         JENDELA CHAT
    ═══════════════════════════════════════════ --}}
    <section aria-labelledby="chat-heading">
        <h2 id="chat-heading" class="sr-only">Riwayat Percakapan</h2>

        <div
            id="chat-window"
            role="log"
            aria-live="polite"
            aria-label="Riwayat percakapan dengan asisten VOXORA"
            aria-relevant="additions"
            class="rounded-2xl border-2 border-black/10 bg-white/40 backdrop-blur-sm
                   px-4 py-5 mb-4 flex flex-col gap-4">

            {{-- Pesan sambutan awal (dari bot) --}}
            <div class="flex items-end gap-2" role="listitem">
                <div aria-hidden="true"
                     class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-white text-xs font-bold"
                     style="background:var(--bg-header);">V</div>
                <div class="bubble-bot px-4 py-3">
                    <p class="text-sm text-black leading-relaxed">
                        Halo! Saya asisten VOXORA.
                        @if($document)
                            Saya siap membantu Anda memahami dokumen
                            <strong>{{ $document->original_filename }}</strong>.
                        @else
                            Saya siap menjawab pertanyaan Anda.
                        @endif
                        Silakan ketik pertanyaan Anda, atau tekan tombol mikrofon untuk bicara.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════
         INPUT AREA
    ═══════════════════════════════════════════ --}}
    <section aria-labelledby="input-heading">
        <h2 id="input-heading" class="sr-only">Formulir Pertanyaan</h2>

        {{-- Konteks dokumen tersembunyi --}}
        <input type="hidden" id="doc-context"
               value="{{ $document->remediated_text ?? '' }}">
        <input type="hidden" id="document-id"
               value="{{ $document->id ?? '' }}">

        <div class="flex items-end gap-3">

            {{-- Tombol mikrofon (Speech-to-Text) --}}
            <button
                id="speak-btn"
                type="button"
                class="flex-shrink-0 relative w-11 h-11 rounded-full flex items-center justify-center text-white
                       focus:outline-none focus:ring-4 focus:ring-black focus:ring-offset-2 transition-all"
                style="background:var(--bg-header);"
                aria-label="Tekan untuk mulai bicara. Pertanyaan Anda akan dikenali secara otomatis."
                aria-pressed="false">
                <svg aria-hidden="true" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 016 0v6a3 3 0 01-3 3z"/>
                </svg>
            </button>

            {{-- Textarea pertanyaan --}}
            <div class="flex-1">
                <label for="question-input" class="sr-only">
                    Ketik pertanyaan Anda tentang dokumen ini
                </label>
                <textarea
                    id="question-input"
                    rows="2"
                    placeholder="Ketik pertanyaan Anda di sini…"
                    aria-label="Pertanyaan untuk asisten VOXORA"
                    aria-describedby="input-hint"
                    onkeydown="handleInputKeydown(event)"></textarea>
                <p id="input-hint" class="sr-only">
                    Tekan Enter untuk mengirim pertanyaan. Tekan Shift+Enter untuk baris baru.
                </p>
            </div>

            {{-- Tombol kirim --}}
            <button
                id="send-btn"
                type="button"
                class="flex-shrink-0 flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold
                       text-sm text-white bg-black hover:bg-gray-800
                       focus:outline-none focus:ring-4 focus:ring-black focus:ring-offset-2
                       disabled:opacity-40 transition-colors"
                aria-label="Kirim pertanyaan ke asisten VOXORA"
                onclick="sendQuestion()">
                <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                Kirim
            </button>
        </div>

        <p class="mt-2 text-xs text-slate-500">
            <kbd class="px-1 bg-slate-100 rounded font-mono text-[10px]">Enter</kbd> kirim ·
            <kbd class="px-1 bg-slate-100 rounded font-mono text-[10px]">Shift+Enter</kbd> baris baru ·
            Jawaban bot akan dibacakan otomatis oleh NVDA dan suara browser
        </p>
    </section>

@endsection

@push('scripts')
<script>
/* ════════════════════════════════════════════════
   KONFIGURASI
════════════════════════════════════════════════ */
const ASK_URL    = "{{ route('tanya.ask') }}";
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const DOC_CTX    = document.getElementById('doc-context').value;
const DOCUMENT_ID = document.getElementById('document-id').value;

let isMuted      = false;   // status suara TTS
let isSpeaking   = false;   // sedang TTS
let currentUtter = null;    // utterance aktif
let recognition  = null;    // Web Speech STT

/* ════════════════════════════════════════════════
   HELPER: tambah bubble ke chat
════════════════════════════════════════════════ */
function addBubble(role, text) {
    const win = document.getElementById('chat-window');
    const wrap = document.createElement('div');
    wrap.setAttribute('role', 'listitem');
    wrap.classList.add('flex', 'items-end', 'gap-2');

    if (role === 'user') {
        wrap.innerHTML = `
            <div class="bubble-user px-4 py-3 ml-auto">
                <p class="text-sm leading-relaxed">${escHtml(text)}</p>
            </div>
            <div aria-hidden="true"
                 class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center
                        text-white text-xs font-bold bg-black">
                Anda
            </div>`;
    } else {
        wrap.innerHTML = `
            <div aria-hidden="true"
                 class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center
                        text-white text-xs font-bold"
                 style="background:var(--bg-header);">V</div>
            <div id="bubble-${Date.now()}" class="bubble-bot px-4 py-3">
                <p class="text-sm text-black leading-relaxed">${escHtml(text)}</p>
            </div>`;
    }

    win.appendChild(wrap);
    win.scrollTop = win.scrollHeight;

    // Umumkan ke NVDA
    if (typeof announce === 'function') {
        announce(role === 'user' ? 'Pertanyaan Anda: ' + text : 'Jawaban asisten: ' + text, 'polite');
    }
    return wrap;
}

/* ════════════════════════════════════════════════
   HELPER: typing indicator
════════════════════════════════════════════════ */
function showTyping() {
    const win = document.getElementById('chat-window');
    const el  = document.createElement('div');
    el.id = 'typing-indicator';
    el.setAttribute('role', 'status');
    el.setAttribute('aria-label', 'Asisten sedang mengetik jawaban');
    el.classList.add('flex', 'items-end', 'gap-2');
    el.innerHTML = `
        <div aria-hidden="true"
             class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center
                    text-white text-xs font-bold"
             style="background:var(--bg-header);">V</div>
        <div class="bubble-bot px-4 py-3">
            <div class="flex items-center gap-1.5">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            </div>
        </div>`;
    win.appendChild(el);
    win.scrollTop = win.scrollHeight;
}
function hideTyping() {
    document.getElementById('typing-indicator')?.remove();
}

/* ════════════════════════════════════════════════
   TTS: Text-to-Speech (Web Speech API)
════════════════════════════════════════════════ */
function speak(text) {
    if (isMuted || !window.speechSynthesis) return;
    window.speechSynthesis.cancel();

    const utter  = new SpeechSynthesisUtterance(text);
    utter.lang   = 'id-ID';
    utter.rate   = 0.95;
    utter.pitch  = 1.05;

    // Pilih suara bahasa Indonesia jika tersedia
    const voices = window.speechSynthesis.getVoices();
    const idVoice = voices.find(v => v.lang.startsWith('id'));
    if (idVoice) utter.voice = idVoice;

    utter.onstart = () => {
        isSpeaking = true;
        currentUtter = utter;
        setVuiSpeaking(true);
    };
    utter.onend = utter.onerror = () => {
        isSpeaking = false;
        currentUtter = null;
        setVuiSpeaking(false);
    };

    window.speechSynthesis.speak(utter);
}

function setVuiSpeaking(active) {
    const indicator = document.getElementById('vui-indicator');
    const statusTxt = document.getElementById('vui-status-text');
    if (active) {
        indicator.innerHTML = `
            <div class="vui-ring"></div>
            <div class="vui-ring"></div>
            <div class="absolute inset-0 rounded-full" style="background:var(--bg-header);"></div>`;
        statusTxt.textContent = 'Asisten sedang berbicara…';
    } else {
        indicator.innerHTML = `<div class="absolute inset-0 rounded-full" style="background:var(--bg-header);"></div>`;
        statusTxt.textContent = 'Asisten VOXORA siap. Ketik pertanyaan atau tekan ikon mikrofon.';
    }
}

/* ── Mute toggle ── */
document.getElementById('mute-btn').addEventListener('click', function() {
    isMuted = !isMuted;
    this.setAttribute('aria-pressed', String(isMuted));
    this.setAttribute('aria-label', isMuted
        ? 'Suara asisten: nonaktif. Tekan untuk mengaktifkan kembali.'
        : 'Suara asisten: aktif. Tekan untuk menonaktifkan suara.');
    document.getElementById('mute-icon-on').classList.toggle('hidden', isMuted);
    document.getElementById('mute-icon-off').classList.toggle('hidden', !isMuted);
    document.getElementById('mute-label').textContent = isMuted ? 'Suara Mati' : 'Suara Aktif';

    if (isMuted && window.speechSynthesis) {
        window.speechSynthesis.cancel();
        setVuiSpeaking(false);
    }
    if (typeof announce === 'function') {
        announce(isMuted ? 'Suara asisten dinonaktifkan.' : 'Suara asisten diaktifkan.');
    }
});

/* ════════════════════════════════════════════════
   STT: Speech-to-Text (Web Speech API)
════════════════════════════════════════════════ */
(function setupSTT() {
    const btn    = document.getElementById('speak-btn');
    const input  = document.getElementById('question-input');
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    if (!SpeechRecognition) {
        btn.title = 'Browser Anda tidak mendukung pengenalan suara';
        btn.style.opacity = '.4';
        return;
    }

    recognition = new SpeechRecognition();
    recognition.lang          = 'id-ID';
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;

    recognition.onstart = () => {
        btn.classList.add('active');
        btn.setAttribute('aria-pressed', 'true');
        btn.setAttribute('aria-label', 'Sedang mendengarkan… Tekan untuk berhenti.');
        document.getElementById('vui-status-text').textContent = 'Mendengarkan suara Anda…';
        if (typeof announce === 'function') announce('Mendengarkan. Silakan bicara sekarang.');
    };

    recognition.onresult = (e) => {
        const transcript = e.results[0][0].transcript;
        input.value = transcript;
        input.focus();
        if (typeof announce === 'function') announce('Pertanyaan dikenali: ' + transcript);
    };

    recognition.onend = recognition.onerror = () => {
        btn.classList.remove('active');
        btn.setAttribute('aria-pressed', 'false');
        btn.setAttribute('aria-label', 'Tekan untuk mulai bicara.');
        document.getElementById('vui-status-text').textContent = 'Asisten VOXORA siap.';
    };

    btn.addEventListener('click', () => {
        if (btn.classList.contains('active')) {
            recognition.stop();
        } else {
            if (isSpeaking && window.speechSynthesis) {
                window.speechSynthesis.cancel();
                setVuiSpeaking(false);
            }
            recognition.start();
        }
    });
})();

/* ════════════════════════════════════════════════
   KIRIM PERTANYAAN
════════════════════════════════════════════════ */
async function sendQuestion() {
    const input   = document.getElementById('question-input');
    const sendBtn = document.getElementById('send-btn');
    const question = input.value.trim();
    if (!question) {
        if (typeof announce === 'function') announce('Harap ketik pertanyaan terlebih dahulu.');
        input.focus();
        return;
    }

    // Tampilkan pertanyaan user
    addBubble('user', question);
    input.value = '';
    input.focus();
    sendBtn.disabled = true;

    // Typing indicator
    showTyping();
    document.getElementById('vui-status-text').textContent = 'Asisten sedang menyiapkan jawaban…';

    try {
        const resp = await fetch(ASK_URL, {
            method: 'POST',
            headers: {
                'Content-Type':     'application/json',
                'X-CSRF-TOKEN':     CSRF_TOKEN,
                'Accept':           'application/json',
            },
            body: JSON.stringify({
                question:    question,
                doc_context: DOC_CTX,
                document_id: DOCUMENT_ID || null,
            }),
        });

        const data = await resp.json();
        hideTyping();

        const answer = data.answer || 'Maaf, tidak ada jawaban yang tersedia saat ini.';
        addBubble('bot', answer);

        // TTS: bacakan jawaban
        speak(answer);

    } catch (err) {
        hideTyping();
        const errMsg = 'Maaf, terjadi kesalahan koneksi. Silakan coba lagi.';
        addBubble('bot', errMsg);
        speak(errMsg);
    } finally {
        sendBtn.disabled = false;
    }
}

function handleInputKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendQuestion();
    }
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── Ucapkan salam saat halaman dimuat ── */
window.addEventListener('DOMContentLoaded', () => {
    const greeting = "{{ $document ? 'Halo! Saya asisten VOXORA. Saya siap membantu Anda memahami dokumen ' . addslashes($document->original_filename) . '. Silakan ajukan pertanyaan.' : 'Halo! Saya asisten VOXORA. Silakan ajukan pertanyaan seputar materi STEM.' }}";
    // Tunda sedikit agar suara tidak bentrok dengan NVDA
    setTimeout(() => speak(greeting), 800);
    document.getElementById('question-input').focus();
});
</script>
@endpush
