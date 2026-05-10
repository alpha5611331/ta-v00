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
        $rawText   = $this->extractText($file);
        $sanitized = $this->sanitize($rawText);

        // ── E. Remediasi (simulasi) ────────────────────────────────
        $remediationResult = $this->remediateWithAI($sanitized);

        // ── F. Simpan dokumen ke database ────────────────────────
        $document = Document::create([
            'user_id'           => Auth::id(),
            'original_filename' => $file->getClientOriginalName(),
            'storage_path'      => $path,
            'raw_text'          => $rawText,
            'remediated_text'   => $remediationResult,
            'char_count'        => strlen($remediationResult),
            'file_type'         => strtolower($file->getClientOriginalExtension()),
            'braille_sent_at'   => null,
        ]);

        return view('upload', [
            'remediationResult' => $remediationResult,
            'document'          => $document,
            'originalFilename'  => $file->getClientOriginalName(),
        ])->with('success', 'Remediasi dokumen berhasil diselesaikan.');
    }

    // ──────────────────────────────────────────────────────────────
    //  3. EKSPOR KE WORD
    // ──────────────────────────────────────────────────────────────

    /**
     * Ekspor teks hasil remediasi ke file .docx.
     * Menggunakan PhpWord. Jalankan: composer require phpoffice/phpword
     */
    /**
     * Simpan teks remediasi ke session lalu arahkan ke halaman /braille.
     * Dipakai tombol "Kirim ke EduBraille" di halaman upload.
     */
    public function toBraille(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'result_text' => 'required|string|max:500000',
        ]);

        // Simpan teks ke session agar bisa dibaca di halaman /braille
        session(['braille_text' => $request->input('result_text')]);

        return redirect()->route('braille.index')
            ->with('success', 'Teks hasil remediasi siap dikirim. Pilih ukuran chunk lalu tekan Kirim.');
    }

    public function export(Request $request)
    {
        $request->validate([
            'result_text' => 'required|string|max:500000',
            'document_title' => 'nullable|string|max:255'
        ]);

        // Cek apakah PhpWord sudah diinstall
        if (!class_exists('\PhpOffice\PhpWord\PhpWord')) {
            return back()->with('error',
                'Fitur ekspor Word membutuhkan library PhpWord. ' .
                'Jalankan: composer require phpoffice/phpword'
            );
        }

        $text = $request->input('result_text');
        $documentTitle = $request->input('document_title', 'Dokumen');
        
        // Clean filename: remove extension and special characters
        $cleanTitle = preg_replace('/\.[^.]+$/', '', $documentTitle); // remove extension
        $cleanTitle = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $cleanTitle); // remove special chars
        $cleanTitle = trim($cleanTitle);
        
        $filename = 'Remediasi Dokumen ' . $cleanTitle . '.docx';
        $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(12);

        $section = $phpWord->addSection([
            'marginTop' => 1440,
            'marginBottom' => 1440,
            'marginLeft' => 1800,
            'marginRight' => 1800,
        ]);

        // Title as Heading 1
        $section->addTitle('Hasil Remediasi Dokumen ' . $cleanTitle . ' oleh VOXORA', 1);
        
        // Content as Normal text (strip HTML tags)
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                // Strip HTML tags
                $cleanLine = strip_tags($line);
                $section->addText(
                    $cleanLine,
                    ['size' => 12, 'name' => 'Arial'],
                    ['lineHeight' => 1.5, 'spaceAfter' => 120]
                );
            } else {
                // Add empty line for paragraph breaks
                $section->addTextBreak(1);
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
            // Clean UTF-8 encoding to prevent JSON errors
            $cleanChunk = mb_convert_encoding($chunk, 'UTF-8', 'UTF-8');
            $cleanChunk = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $cleanChunk);
            
            // Convert to braille and ensure UTF-8
            $brailleText = $this->convertToBraille($cleanChunk);
            $brailleText = mb_convert_encoding($brailleText, 'UTF-8', 'UTF-8');
            
            return [
                'text'    => $cleanChunk,
                'braille' => $brailleText,
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
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($path);
            $lines   = [];
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $el) {
                    $line = $this->extractElementText($el);
                    if ($line !== '') {
                        $lines[] = $line;
                    }
                }
            }
            return implode("\n", $lines);
        } catch (\Exception $e) {
            Log::error('DOCX parsing gagal: ' . $e->getMessage());
            return '[Gagal mengekstrak teks DOCX. Silakan coba file lain.]';
        }
    }

    private function extractElementText(object $el): string
    {
        // Title / heading — prefix with hashes so the AI can identify hierarchy
        if ($el instanceof \PhpOffice\PhpWord\Element\Title) {
            $depth = method_exists($el, 'getDepth') ? (int) $el->getDepth() : 1;
            $inner = $el->getText();
            $text  = is_string($inner) ? $inner : $this->extractElementText($inner);
            return str_repeat('#', max(1, $depth)) . ' ' . trim($text);
        }

        // Paragraph and TextRun — recurse into children
        if ($el instanceof \PhpOffice\PhpWord\Element\Paragraph ||
            $el instanceof \PhpOffice\PhpWord\Element\TextRun) {
            $parts = [];
            foreach ($el->getElements() as $child) {
                $t = $this->extractElementText($child);
                if ($t !== '') $parts[] = $t;
            }
            return implode('', $parts);
        }

        // Plain text leaf
        if ($el instanceof \PhpOffice\PhpWord\Element\Text) {
            return $el->getText();
        }

        // Hyperlink — use visible text
        if ($el instanceof \PhpOffice\PhpWord\Element\Link) {
            return $el->getText();
        }

        // List item — indent marker for AI structure cues
        if ($el instanceof \PhpOffice\PhpWord\Element\ListItem) {
            $depth  = method_exists($el, 'getDepth') ? (int) $el->getDepth() : 0;
            $prefix = str_repeat('  ', $depth) . '- ';
            $obj    = $el->getTextObject();
            $text   = method_exists($obj, 'getText') ? $obj->getText() : '';
            return $prefix . trim($text);
        }

        // Table — serialize as pipe-delimited rows
        if ($el instanceof \PhpOffice\PhpWord\Element\Table) {
            $rows = [];
            foreach ($el->getRows() as $row) {
                $cells = [];
                foreach ($row->getCells() as $cell) {
                    $cellParts = [];
                    foreach ($cell->getElements() as $cellEl) {
                        $t = $this->extractElementText($cellEl);
                        if ($t !== '') $cellParts[] = $t;
                    }
                    $cells[] = implode(' ', $cellParts);
                }
                $rows[] = implode(' | ', $cells);
            }
            return implode("\n", $rows);
        }

        return '';
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
        $systemPrompt = <<<'PROMPT'
Kamu adalah penulis skrip narasi STEM profesional untuk tunanetra Indonesia.

Tugas: ubah teks dokumen STEM menjadi SKRIP NARASI yang siap dibacakan oleh screen reader atau mesin Braille, tanpa kehilangan satu pun informasi dari dokumen asli.

Output hanya berisi skrip narasi. Jangan tambahkan komentar, catatan, atau penjelasan meta di luar isi narasi.

═══ KONVENSI MATEMATIKA ═══

EKSPONEN:
- x² → "x kuadrat"
- x³ → "x kubik"
- xⁿ → "x pangkat n"
- 10⁻³ → "sepuluh pangkat negatif tiga"
- eˣ → "e pangkat x"
- x^{2} atau x^2 (LaTeX) → "x kuadrat"

AKAR:
- √x → "akar kuadrat dari x"
- √(x+1) → "akar kuadrat dari, x ditambah satu"
- ³√x → "akar pangkat tiga dari x"
- \sqrt{x} (LaTeX) → "akar kuadrat dari x"
- \sqrt[n]{x} (LaTeX) → "akar pangkat n dari x"

PECAHAN:
- a/b (sederhana) → "a per b"
- (a+b)/(c-d) → "a tambah b, per, c kurang d"
- \frac{a}{b} (LaTeX) → "a per b"
- \frac{df}{dx} → "d f per d x"

OPERASI DASAR:
- + → "ditambah"
- − → "dikurangi"
- × atau · → "dikali"
- ÷ → "dibagi"
- ± → "plus atau minus"
- = → "sama dengan"
- ≠ → "tidak sama dengan"
- < → "kurang dari"
- > → "lebih dari"
- ≤ → "kurang dari atau sama dengan"
- ≥ → "lebih dari atau sama dengan"
- ≈ → "kurang lebih sama dengan"
- ∝ → "sebanding dengan"
- % → "persen"

KALKULUS:
- ∑ atau \sum_{i=1}^{n} → "sigma, i dari satu sampai n, dari"
- ∫ atau \int_{a}^{b} → "integral dari a sampai b, dari [fungsi] d[variabel]"
- lim atau \lim_{x \to a} → "limit x mendekati a, dari"
- d/dx atau \frac{d}{dx} → "turunan terhadap x dari"
- ∂/∂x atau \frac{\partial}{\partial x} → "turunan parsial terhadap x dari"
- f'(x) → "f aksen dari x"
- f''(x) → "f aksen dua dari x"
- \nabla → "nabla"

HIMPUNAN DAN LOGIKA:
- ∞ → "tak hingga"
- ∈ → "anggota"
- ∉ → "bukan anggota"
- ⊂ → "himpunan bagian dari"
- ∪ → "gabungan"
- ∩ → "irisan"
- ∀ → "untuk semua"
- ∃ → "terdapat"
- → (logika) → "maka"
- ⇒ → "mengakibatkan"
- ⟺ → "jika dan hanya jika"
- ¬ → "bukan"

HURUF YUNANI:
- α→alfa, β→beta, γ→gamma, δ→delta, ε→epsilon, ζ→zeta, η→eta
- θ→theta, λ→lambda, μ→mu, ν→nu, ξ→xi, π→pi, ρ→rho
- σ→sigma, τ→tau, φ→phi, χ→chi, ψ→psi, ω→omega
- Δ→Delta besar, Σ→Sigma besar, Π→Pi besar, Ω→Omega besar

INDEKS DAN SUBSKRIP:
- x_i atau xᵢ → "x sub i"
- a_0 atau a₀ → "a sub nol"
- v_{max} → "v sub maks"
- T_{1/2} → "T sub setengah"

VEKTOR DAN MATRIKS:
- **v** atau v⃗ → "vektor v"
- |v| → "besar vektor v"
- v · w → "vektor v titik vektor w"
- v × w → "vektor v silang vektor w"
- Matriks A → "matriks A"
- A_{m×n} → "matriks A berukuran m kali n"
- det(A) → "determinan matriks A"
- Aᵀ → "transpose matriks A"

NILAI MUTLAK DAN NORMA:
- |x| → "nilai mutlak dari x"
- ||v|| → "norma dari vektor v"

NOTASI LAINNYA:
- n! → "n faktorial"
- \binom{n}{k} → "n pilih k"
- P(A) → "peluang kejadian A"
- P(A|B) → "peluang A diketahui B"
- \left( ... \right) → "kurung buka ... kurung tutup"
- \left[ ... \right] → "kurung siku buka ... kurung siku tutup"
- \left\{ ... \right\} → "kurung kurawal buka ... kurung kurawal tutup"

═══ STRUKTUR DOKUMEN ═══

- Heading "# Bab 1" atau "# BAB I" → "BAB SATU."  (tulis angka dengan kata)
- Heading "## Sub-bagian" → "Sub-bagian: [judul]."
- Heading "### ..." → "Bagian: [judul]."
- Gambar → "Gambar [nomor]: [deskripsikan dari caption atau konteks sekitar]."
- Tabel (baris dengan |) → "Tabel [nomor] memuat kolom [daftar nama kolom]. [Baca baris data satu per satu dengan format: baris satu, nilai kolom pertama adalah ..., nilai kolom kedua adalah ..., dan seterusnya.]"
- Contoh soal → "Contoh Soal [nomor]:"
- Penyelesaian/jawaban → "Penyelesaian:"
- Definisi → "Definisi:"
- Teorema → "Teorema [nomor atau nama]:"
- Lemma/Korolari → baca sesuai labelnya
- Bukti → "Bukti:"
- Catatan kaki → "Catatan: [isi]."
- Numbered list → "Pertama,", "Kedua,", "Ketiga,", dst.
- Bullet list (prefix -) → "Pertama,", "Kedua,", "Ketiga,", dst.

═══ ATURAN PENULISAN SKRIP ═══

1. Pertahankan SEMUA informasi—jangan ringkas, jangan hilangkan konten apapun.
2. Tambahkan koma (,) pada jeda alami saat membaca persamaan atau ekspresi panjang.
3. Setiap persamaan yang berdiri sendiri diakhiri tanda titik (.).
4. Gunakan kata transisi antar paragraf: "Selanjutnya,", "Perhatikan bahwa,", "Diketahui bahwa,", "Dengan demikian,", "Oleh karena itu,".
5. Tidak ada simbol, karakter khusus, LaTeX, atau markup apapun dalam output—semua harus tertulis dalam huruf dan kata.
6. Bahasa Indonesia yang mengalir natural—tidak kaku, tidak robotik.
7. Angka di luar ekspresi matematika boleh ditulis sebagai digit (1, 2, 3).
8. Singkatan umum dieja penuh: "yaitu" bukan "i.e.", "misalnya" bukan "e.g.", "dan lain-lain" bukan "dll." atau "etc.".
9. Satuan fisika dibaca lengkap: "meter per detik kuadrat" bukan "m/s²".
PROMPT;

        // ── Fallback ke simulasi jika tidak ada API key ────────────
        if (!config('services.openai.api_key')) {
            return $this->simulateRemediation($sanitized);
        }

        // ── Potong teks menjadi segmen 4000 karakter ───────────────
        $segments = $this->splitIntoSegments($sanitized, 4000);
        $results  = [];

        foreach ($segments as $segment) {
            try {
                $response = Http::timeout(60)
                    ->withToken(config('services.openai.api_key'))
                    ->post(config('services.openai.endpoint', 'https://api.openai.com/v1/chat/completions'), [
                        'model'       => 'gpt-4o-mini',
                        'temperature' => 0.2,
                        'max_tokens'  => 4096,
                        'messages'    => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user',   'content' => $segment],
                        ],
                    ]);

                if ($response->successful()) {
                    $results[] = trim($response->json('choices.0.message.content', ''));
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
            // Soal numbers
            '/(\d+)\./'            => 'Soal nomor $1.',
            '/^(\d+)\s/'          => 'Soal nomor $1 ',
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

        return trim($result);
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
            // Ensure chunks are JSON-safe
            $safeChunks = array_map(function ($chunk) {
                return [
                    'text'    => mb_convert_encoding($chunk['text'], 'UTF-8', 'UTF-8'),
                    'braille' => mb_convert_encoding($chunk['braille'], 'UTF-8', 'UTF-8'),
                ];
            }, $chunks);
            
            // Use JSON_UNESCAPED_UNICODE to prevent encoding issues
            $jsonData = json_encode([
                'device_id' => config('services.edubraille.device_id', 'DEFAULT'),
                'chunks'    => $safeChunks,
            ], JSON_UNESCAPED_UNICODE);
            
            // Try with longer timeout and retry logic
            $response = Http::timeout(30)
                ->withToken(config('services.edubraille.token', ''))
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($edubrailleUrl . '/receive', $jsonData);

            if (!$response->successful()) {
                Log::error('EduBraille API error: ' . $response->status());
                Log::error('Response body: ' . $response->body());
            } else {
                Log::info('EduBraille: Berhasil mengirim ' . count($safeChunks) . ' chunks');
            }
        } catch (\Exception $e) {
            Log::error('EduBraille koneksi gagal: ' . $e->getMessage());
            
            // Check if device is reachable
            if (str_contains($e->getMessage(), 'timeout') || str_contains($e->getMessage(), 'Connection')) {
                Log::error('EduBraille device tidak dapat dijangkau di: ' . $edubrailleUrl);
                Log::error('Pastikan device EduBraille aktif dan terhubung ke jaringan');
            }
        }
    }
}