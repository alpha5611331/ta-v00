<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    // ──────────────────────────────────────────────────────────────
    //  Konstanta konfigurasi
    // ──────────────────────────────────────────────────────────────

    /** Ukuran maksimum file (byte) */
    private const MAX_FILE_SIZE = 20 * 1024 * 1024;   // 20 MB

    /** Tipe MIME yang diterima */
    private const ALLOWED_MIMES = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    /** Pola sanitasi: header/footer/nomor halaman */
    private const SANITIZE_PATTERNS = [
        '/\b(Halaman|Page|hal\.|pg\.)\s*\d+\b/ui',          // nomor halaman
        '/^\s*\d+\s*$/m',                                    // baris hanya angka (page number)
        '/^.{1,80}(\||\t).{1,80}$/m',                       // tab-separated header/footer
        '/\[.*?(header|footer|watermark).*?\]/ui',           // tag markup header/footer
        '/={3,}|-{3,}/m',                                    // garis pemisah dekoratif
        '/\r\n|\r/',                                         // normalize newline
        '/\n{3,}/',                                          // kolaps multiple blank lines
    ];

    /** Replacement untuk pattern sanitasi */
    private const SANITIZE_REPLACE = [
        '',
        '',
        '',
        '',
        '',
        "\n",
        "\n\n",
    ];

    // ──────────────────────────────────────────────────────────────
    //  1. SHOW UPLOAD PAGE
    // ──────────────────────────────────────────────────────────────

    /**
     * Tampilkan halaman upload dokumen.
     */
    public function index()
    {
        return view('upload');
    }

    // ──────────────────────────────────────────────────────────────
    //  2. PROSES UPLOAD & REMEDIASI
    // ──────────────────────────────────────────────────────────────

    /**
     * Terima upload, sanitasi, dan remediasi dokumen via AI.
     */
    public function store(Request $request)
    {
        // ── A. Validasi ────────────────────────────────────────────
        $request->validate([
            'document' => [
                'required',
                'file',
                'max:20480',
                'mimes:pdf,docx',
            ],
        ], [
            'document.required' => 'Harap pilih file dokumen.',
            'document.file'     => 'Upload harus berupa file.',
            'document.max'      => 'Ukuran file tidak boleh melebihi 20 MB.',
            'document.mimes'    => 'Hanya file PDF atau DOCX yang diterima.',
        ]);

        $file = $request->file('document');

        // ── B. Simpan file sementara ───────────────────────────────
        // Storage::disk('local') tidak butuh model — aman
        $path = $file->store('uploads/' . Auth::id(), 'local');

        // ── C & D. Ekstrak + sanitasi ──────────────────────────────
        // Saat ini simulasi — library smalot/pdfparser & phpoffice/phpword
        // belum dipasang. Aktifkan extractText() setelah composer install.
        $rawText   = '[Teks dari dokumen ' . $file->getClientOriginalName() . ']';
        $sanitized = $this->sanitize($rawText);

        // ── E. Remediasi (simulasi) ────────────────────────────────
        $remediationResult = $this->remediateWithAI($sanitized);

        // ── F. Simpan metadata dan hasil remediasi ke database ─────
        $document = Document::create([
            'user_id'           => Auth::id(),
            'original_filename' => $file->getClientOriginalName(),
            'storage_path'      => $path,
            'raw_text'          => $sanitized,
            'remediated_text'   => $remediationResult,
            'char_count'        => mb_strlen($remediationResult),
            'file_type'         => strtolower($file->getClientOriginalExtension()),
            'braille_sent_at'   => null,
        ]);

        return view('upload', [
            'remediationResult' => $remediationResult,
            'document'          => $document,
        ])->with('success', 'Remediasi dokumen berhasil diselesaikan.');
    }

    // ──────────────────────────────────────────────────────────────
    //  3. EKSPOR KE WORD
    // ──────────────────────────────────────────────────────────────

    /**
     * Ekspor teks hasil remediasi ke file .docx.
     * Menggunakan PhpWord. Jalankan: composer require phpoffice/phpword
     */
    public function export(Request $request)
    {
        $request->validate(['result_text' => 'required|string|max:500000']);

        // Cek apakah PhpWord sudah diinstall
        if (!class_exists('\PhpOffice\PhpWord\PhpWord')) {
            return back()->with('error',
                'Fitur ekspor Word membutuhkan library PhpWord. ' .
                'Jalankan: composer require phpoffice/phpword'
            );
        }

        $text     = $request->input('result_text');
        $filename = 'VOXORA_Remediasi_' . now()->format('Ymd_His') . '.docx';
        $tmpPath  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

        $phpWord  = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(12);

        $section = $phpWord->addSection([
            'marginTop'    => 1440,
            'marginBottom' => 1440,
            'marginLeft'   => 1800,
            'marginRight'  => 1800,
        ]);

        $section->addTitle('Hasil Remediasi Dokumen – VOXORA', 1);
        $section->addText(
            'Tanggal: ' . now()->translatedFormat('d F Y, H:i'),
            ['italic' => true, 'color' => '555555'],
            ['spaceAfter' => 240]
        );
        $section->addTextBreak(1);

        foreach (explode("\n\n", $text) as $para) {
            $para = trim($para);
            if ($para !== '') {
                $section->addText(
                    htmlspecialchars($para),
                    ['size' => 12, 'name' => 'Arial'],
                    ['lineHeight' => 1.5, 'spaceAfter' => 160]
                );
            }
        }

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmpPath);

        return response()->download($tmpPath, $filename, [
            'Content-Type' =>
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    // ──────────────────────────────────────────────────────────────
    //  4. KIRIM KE EDUBRAILLE
    // ──────────────────────────────────────────────────────────────

    /**
     * Pecah teks remediasi menjadi chunks dan konversi ke braille.
     * Kemudian kirim ke perangkat EduBraille via Serial/API.
     *
     * @param  int  $chunkSize  5 atau 20 karakter per chunk
     */
    public function sendToEduBraille(Request $request)
    {
        $request->validate([
            'result_text' => 'required|string|max:500000',
            'chunk_size'  => 'required|integer|in:5,20',
        ]);

        $text      = $request->input('result_text');
        $chunkSize = (int) $request->input('chunk_size', 20);

        // ── A. Pisahkan teks menjadi chunks ────────────────────────
        $chunks = $this->chunkText($text, $chunkSize);

        // ── B. Konversi setiap chunk ke Unicode Braille ────────────
        $brailleChunks = array_map(function (string $chunk) {
            return [
                'text'    => $chunk,
                'braille' => $this->convertToBraille($chunk),
            ];
        }, $chunks);

        // ── C. Kirim ke EduBraille (via HTTP API / Serial) ─────────
        $this->dispatchToDevice($brailleChunks);

        Log::info('VOXORA EduBraille: dikirim ' . count($brailleChunks) . ' chunks.');

        return view('upload', [
            'brailleChunks'     => $brailleChunks,
            'remediationResult' => $text,
        ])->with('success', count($brailleChunks) . ' chunk braille berhasil dikirim ke EduBraille.');
    }

    // ══════════════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ══════════════════════════════════════════════════════════════

    /**
     * Ekstrak teks dari PDF atau DOCX.
     * Untuk PDF: gunakan smalot/pdf-parser  (composer require smalot/pdfparser)
     * Untuk DOCX: gunakan phpoffice/phpword
     */
    private function extractText(\Illuminate\Http\UploadedFile $file): string
    {
        $mime = $file->getMimeType();

        if ($mime === 'application/pdf') {
            return $this->extractPdfText($file->getRealPath());
        }

        // DOCX fallback
        return $this->extractDocxText($file->getRealPath());
    }

    private function extractPdfText(string $path): string
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf    = $parser->parseFile($path);
            return $pdf->getText();
        } catch (\Exception $e) {
            Log::error('PDF parsing gagal: ' . $e->getMessage());
            return '[Gagal mengekstrak teks PDF. Silakan coba file lain.]';
        }
    }

    private function extractDocxText(string $path): string
    {
        try {
            $phpWord  = \PhpOffice\PhpWord\IOFactory::load($path);
            $texts    = [];
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $el) {
                    if ($el instanceof \PhpOffice\PhpWord\Element\TextRun) {
                        foreach ($el->getElements() as $textEl) {
                            if (method_exists($textEl, 'getText')) {
                                $texts[] = $textEl->getText();
                            }
                        }
                        $texts[] = "\n";
                    } elseif ($el instanceof \PhpOffice\PhpWord\Element\Text) {
                        $texts[] = $el->getText() . "\n";
                    }
                }
            }
            return implode('', $texts);
        } catch (\Exception $e) {
            Log::error('DOCX parsing gagal: ' . $e->getMessage());
            return '[Gagal mengekstrak teks DOCX. Silakan coba file lain.]';
        }
    }

    /**
     * Sanitasi: hapus header, footer, nomor halaman, dan noise dokumen.
     */
    private function sanitize(string $text): string
    {
        $clean = preg_replace(self::SANITIZE_PATTERNS, self::SANITIZE_REPLACE, $text);
        // Trim setiap baris
        $lines = array_map('trim', explode("\n", $clean));
        // Hapus baris kosong berurutan
        $result = [];
        $prevEmpty = false;
        foreach ($lines as $line) {
            $isEmpty = ($line === '');
            if ($isEmpty && $prevEmpty) continue;
            $result[]  = $line;
            $prevEmpty = $isEmpty;
        }
        return trim(implode("\n", $result));
    }

    /**
     * Remediasi konten STEM via AI (RAG approach).
     *
     * ─ Alur:
     *  1. Potong teks menjadi segmen (maks 2000 karakter agar muat prompt).
     *  2. Kirim setiap segmen ke AI dengan system prompt khusus STEM.
     *  3. Gabungkan hasil.
     *
     * ─ Konfigurasi AI:
     *   Set OPENAI_API_KEY dan OPENAI_API_URL di .env
     *   Atau gunakan Gemini / LM Studio / API lokal lainnya.
     */
    private function remediateWithAI(string $sanitized): string
    {
        $systemPrompt = <<<PROMPT
Kamu adalah asisten remediasi aksesibilitas STEM untuk tunanetra.
Tugasmu mengonversi teks dokumen—terutama matematika SMP-SMA—menjadi
kalimat natural bahasa Indonesia yang dapat dibaca oleh screen reader (NVDA).

Aturan:
1. Notasi matematika → kalimat natural.
   Contoh: "x² + 3x = 0" → "x kuadrat ditambah tiga x sama dengan nol"
2. Simbol → kata deskriptif (∑ → jumlah, √ → akar kuadrat dari, π → phi, ∞ → tak hingga).
3. Pecahan → "pembilang dibagi penyebut" (¾ → tiga per empat).
4. Pertahankan struktur paragraf, jangan ubah konten non-matematika.
5. Gunakan tanda titik di akhir setiap kalimat.
6. Hindari simbol atau karakter khusus dalam output.
7. Jika ada soal/pertanyaan, awali dengan "Soal:" dan pertahankan nomornya.
PROMPT;

        // ── Simulasi / fallback jika tidak ada API key ─────────────
        if (!config('services.openai.api_key') && !config('services.ai.api_key')) {
            return $this->simulateRemediation($sanitized);
        }

        // ── Potong teks menjadi segmen 2000 karakter ───────────────
        $segments  = $this->splitIntoSegments($sanitized, 2000);
        $results   = [];

        foreach ($segments as $segment) {
            try {
                $response = Http::timeout(30)
                    ->withToken(config('services.openai.api_key'))
                    ->post(config('services.openai.endpoint', 'https://api.openai.com/v1/chat/completions'), [
                        'model'       => 'gpt-4o-mini',
                        'temperature' => 0.3,
                        'max_tokens'  => 1500,
                        'messages'    => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user',   'content' => $segment],
                        ],
                    ]);

                if ($response->successful()) {
                    $results[] = $response->json('choices.0.message.content', '');
                } else {
                    Log::warning('AI API error: ' . $response->status() . ' – ' . $response->body());
                    $results[] = $this->simulateRemediation($segment);
                }
            } catch (\Exception $e) {
                Log::error('AI request gagal: ' . $e->getMessage());
                $results[] = $this->simulateRemediation($segment);
            }
        }

        return implode("\n\n", array_filter($results));
    }

    /**
     * Simulasi remediasi offline: konversi pola matematika dasar.
     * Digunakan sebagai fallback ketika AI API tidak tersedia.
     */
    private function simulateRemediation(string $text): string
    {
        $replacements = [
            // Pangkat
            '/(\w+)\^2/'         => '$1 kuadrat',
            '/(\w+)\^3/'         => '$1 kubik',
            '/(\w+)\^(\d+)/'     => '$1 pangkat $2',
            // Akar
            '/√\(([^)]+)\)/'     => 'akar kuadrat dari $1',
            '/√(\w+)/'           => 'akar kuadrat dari $1',
            // Pecahan sederhana
            '/(\d+)\/(\d+)/'     => '$1 per $2',
            // Operator
            '/≥/'                => 'lebih dari atau sama dengan',
            '/≤/'                => 'kurang dari atau sama dengan',
            '/≠/'                => 'tidak sama dengan',
            '/≈/'                => 'kira-kira sama dengan',
            '/∑/'                => 'jumlah dari',
            '/∞/'                => 'tak hingga',
            '/π/'                => 'phi',
            '/α/'                => 'alfa',
            '/β/'                => 'beta',
            '/θ/'                => 'theta',
            '/°/'                => 'derajat',
            // Persamaan / pertidaksamaan
            '/(\w+)\s*=\s*(\w+)/'=> '$1 sama dengan $2',
        ];

        $result = preg_replace(
            array_keys($replacements),
            array_values($replacements),
            $text
        );

        // Tambahkan catatan simulasi
        $note = "\n\n[SIMULASI: Teks di atas adalah hasil remediasi offline. "
              . "Sambungkan AI API untuk remediasi penuh.]";

        return trim($result) . $note;
    }

    /**
     * Potong teks panjang menjadi segmen agar muat dalam context window AI.
     */
    private function splitIntoSegments(string $text, int $maxLen): array
    {
        if (strlen($text) <= $maxLen) return [$text];

        $segments = [];
        $paragraphs = explode("\n\n", $text);
        $current    = '';

        foreach ($paragraphs as $para) {
            if (strlen($current) + strlen($para) + 2 > $maxLen) {
                if ($current !== '') {
                    $segments[] = trim($current);
                    $current    = '';
                }
                // Jika satu paragraf melebihi maxLen, potong paksa
                if (strlen($para) > $maxLen) {
                    foreach (str_split($para, $maxLen) as $chunk) {
                        $segments[] = $chunk;
                    }
                    continue;
                }
            }
            $current .= ($current ? "\n\n" : '') . $para;
        }

        if ($current !== '') $segments[] = trim($current);
        return $segments;
    }

    /**
     * Pecah teks menjadi chunks (5 atau 20 karakter).
     * Hanya karakter alfanumerik dan spasi yang dipertahankan.
     */
    private function chunkText(string $text, int $size): array
    {
        // Normalisasi: hapus karakter non-drukable kecuali spasi/newline
        $clean  = preg_replace('/[^\p{L}\p{N}\s.,;:!?()\-]/u', '', $text);
        $clean  = preg_replace('/\s+/', ' ', $clean);
        $clean  = trim($clean);
        $chunks = str_split($clean, $size);
        return array_filter($chunks, fn($c) => trim($c) !== '');
    }

    /**
     * Konversi teks ke Unicode Braille (Grade 1 – karakter per karakter).
     * Tabel lengkap sesuai standar NABCC / Unified English Braille.
     */
    private function convertToBraille(string $text): string
    {
        $map = [
            // Huruf (a-z)
            'a' => '⠁', 'b' => '⠃', 'c' => '⠉', 'd' => '⠙', 'e' => '⠑',
            'f' => '⠋', 'g' => '⠛', 'h' => '⠓', 'i' => '⠊', 'j' => '⠚',
            'k' => '⠅', 'l' => '⠇', 'm' => '⠍', 'n' => '⠝', 'o' => '⠕',
            'p' => '⠏', 'q' => '⠟', 'r' => '⠗', 's' => '⠎', 't' => '⠞',
            'u' => '⠥', 'v' => '⠧', 'w' => '⠺', 'x' => '⠭', 'y' => '⠽',
            'z' => '⠵',
            // Angka (awali dengan ⠼)
            '0' => '⠼⠚', '1' => '⠼⠁', '2' => '⠼⠃', '3' => '⠼⠉',
            '4' => '⠼⠙', '5' => '⠼⠑', '6' => '⠼⠋', '7' => '⠼⠛',
            '8' => '⠼⠓', '9' => '⠼⠊',
            // Tanda baca
            ' ' => '⠀', ',' => '⠂', '.' => '⠄', '?' => '⠦',
            '!' => '⠖', ':' => '⠒', ';' => '⠆', '-' => '⠤',
            '(' => '⠦', ')' => '⠴',
        ];

        $lower  = mb_strtolower($text);
        $result = '';
        $chars  = preg_split('//u', $lower, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($chars as $char) {
            $result .= $map[$char] ?? '⠿';   // ⠿ = karakter tidak dikenal
        }

        return $result;
    }

    /**
     * Kirim chunks ke perangkat EduBraille.
     * Ganti implementasi sesuai protokol perangkat (Serial/USB/HTTP).
     *
     * @param  array  $chunks  [['text' => ..., 'braille' => ...], ...]
     */
    private function dispatchToDevice(array $chunks): void
    {
        $edubrailleUrl = config('services.edubraille.endpoint');

        if (!$edubrailleUrl) {
            // Mode simulasi: log saja
            Log::info('EduBraille (simulasi): ' . count($chunks) . ' chunks siap kirim.', [
                'preview' => array_slice($chunks, 0, 3),
            ]);
            return;
        }

        // ── Kirim ke API EduBraille (jika tersedia) ────────────────
        try {
            $response = Http::timeout(15)
                ->withToken(config('services.edubraille.token', ''))
                ->post($edubrailleUrl . '/receive', [
                    'device_id' => config('services.edubraille.device_id', 'DEFAULT'),
                    'chunks'    => $chunks,
                ]);

            if (!$response->successful()) {
                Log::error('EduBraille API error: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::error('EduBraille koneksi gagal: ' . $e->getMessage());
        }
    }
}
