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

        // ── E. Remediasi ───────────────────────────────────────────
        // For PDFs with sparse text (equations as images), try vision first.
        $storedPath = \Illuminate\Support\Facades\Storage::disk('local')->path($path);
        $isPdf      = strtolower($file->getClientOriginalExtension()) === 'pdf';

        $remediationResult = ($isPdf ? $this->tryNarrateWithVision($storedPath, $sanitized) : null)
            ?? $this->remediateWithAI($sanitized);

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
            return back()->with(
                'error',
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
        // pdftotext (Xpdf/Poppler) handles Unicode and multi-column layouts far
        // better than smalot, and preserves more characters from math fonts.
        $text = $this->extractPdfWithPdftotext($path);
        if ($text !== null) {
            return $text;
        }

        // Fallback: smalot/pdfparser
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf    = $parser->parseFile($path);
            $text   = $pdf->getText();
            return trim($text) !== '' ? $text
                : '[Tidak ada teks yang dapat diekstrak dari PDF ini. '
                . 'Kemungkinan persamaan disimpan sebagai gambar (PDF dari Word) '
                . 'atau menggunakan font matematika tanpa peta Unicode (PDF dari LaTeX).]';
        } catch (\Exception $e) {
            Log::error('PDF parsing gagal: ' . $e->getMessage());
            return '[Gagal mengekstrak teks PDF. Silakan coba file lain.]';
        }
    }

    /**
     * Ekstrak teks PDF via pdftotext jika tersedia di PATH.
     * Lebih baik dari smalot untuk font Unicode dan tata letak multi-kolom.
     * Catatan: PDF yang berasal dari Word menyimpan persamaan sebagai gambar —
     * tidak ada alat teks yang dapat mengekstraknya tanpa OCR.
     */
    private function extractPdfWithPdftotext(string $path): ?string
    {
        // Locate binary without shell expansion (security: no user input in path)
        $binary = trim((string) shell_exec('where pdftotext 2>nul'))
            ?: trim((string) shell_exec('which pdftotext 2>/dev/null'));
        $binary = explode("\n", $binary)[0]; // take first result if multiple

        if (!$binary || !is_executable($binary)) {
            return null;
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'voxora_') . '.txt';

        // proc_open avoids shell string interpolation — args are passed as array
        $descriptor = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open(
            [$binary, '-layout', '-enc', 'UTF-8', $path, $tmpFile],
            $descriptor,
            $pipes
        );

        if (!is_resource($proc)) {
            return null;
        }

        fclose($pipes[1]);
        $stderr   = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($proc);

        if ($exitCode !== 0) {
            Log::warning("pdftotext exited {$exitCode}: {$stderr}");
            @unlink($tmpFile);
            return null;
        }

        $text = @file_get_contents($tmpFile);
        @unlink($tmpFile);

        if ($text === false || trim($text) === '') {
            return null;
        }

        return $text;
    }

    private function extractDocxText(string $path): string
    {
        // Primary: parse DOCX XML directly — the only way to capture OMML equations.
        // phpoffice/phpword silently discards <m:oMath> blocks, so we bypass it for
        // the initial extraction and fall back to it only if the ZIP cannot be opened.
        try {
            return $this->extractDocxFromXml($path);
        } catch (\Exception $e) {
            Log::warning('DOCX XML extraction failed, trying phpoffice/phpword: ' . $e->getMessage());
        }

        // Fallback: phpoffice/phpword (no equations, but better for complex layouts)
        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($path);
            $lines   = [];
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $el) {
                    $line = $this->extractPhpWordElement($el);
                    if ($line !== '') $lines[] = $line;
                }
            }
            return implode("\n", $lines);
        } catch (\Exception $e) {
            Log::error('DOCX parsing gagal: ' . $e->getMessage());
            return '[Gagal mengekstrak teks DOCX. Silakan coba file lain.]';
        }
    }

    /**
     * Parse word/document.xml directly from the DOCX ZIP.
     * This is the only reliable way to extract Office Math (OMML) equations.
     * Equations are wrapped in [PERSAMAAN: ...] so the AI knows to narrate them.
     */
    private function extractDocxFromXml(string $path): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Cannot open DOCX as ZIP archive');
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new \RuntimeException('word/document.xml not found in DOCX');
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadXML($xml);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $xpath->registerNamespace('m', 'http://schemas.openxmlformats.org/officeDocument/2006/math');

        $body = $dom->getElementsByTagNameNS(
            'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
            'body'
        )->item(0);

        if (!$body) {
            throw new \RuntimeException('Document body not found in word/document.xml');
        }

        $lines = [];
        foreach ($body->childNodes as $node) {
            $text = trim($this->extractXmlNode($node, $xpath));
            if ($text !== '') $lines[] = $text;
        }

        return implode("\n", $lines);
    }

    private function extractXmlNode(\DOMNode $node, \DOMXPath $xpath): string
    {
        $name = $node->localName ?? '';

        switch ($name) {
            case 'p':
                // Determine heading level from paragraph style
                $styleAttr = $xpath->query('.//w:pStyle/@w:val', $node)->item(0);
                $style     = $styleAttr ? strtolower($styleAttr->nodeValue) : '';

                $text = '';
                foreach ($node->childNodes as $child) {
                    $text .= $this->extractXmlNode($child, $xpath);
                }
                $text = trim($text);
                if ($text === '') return '';

                if (preg_match('/heading(\d)/i', $style, $m)) {
                    return str_repeat('#', (int) $m[1]) . ' ' . $text;
                }
                if (in_array($style, ['title', 'subtitle'])) {
                    return '# ' . $text;
                }
                return $text;

            case 'r':   // text run — recurse
                $out = '';
                foreach ($node->childNodes as $child) {
                    $out .= $this->extractXmlNode($child, $xpath);
                }
                return $out;

            case 't':   // plain text leaf
                return $node->textContent;

            case 'br':
                return "\n";
            case 'tab':
                return "\t";

            case 'oMath':      // inline equation block
            case 'oMathPara':  // display equation block
                $chars = $xpath->query('.//m:t', $node);
                $eq    = '';
                foreach ($chars as $t) {
                    $eq .= $t->textContent;
                }
                // Clean Word display artifacts before wrapping
                $eq = strtr($eq, ['▒' => '', '〖' => '(', '〗' => ')']);
                $eq = trim($eq);
                return $eq !== '' ? '[PERSAMAAN: ' . $eq . ']' : '';

            case 'tbl':  // table
                $rows = [];
                foreach ($xpath->query('w:tr', $node) as $row) {
                    $cells = [];
                    foreach ($xpath->query('w:tc', $row) as $cell) {
                        $cellText = '';
                        foreach ($cell->childNodes as $child) {
                            $cellText .= $this->extractXmlNode($child, $xpath);
                        }
                        $cells[] = trim($cellText);
                    }
                    $rows[] = implode(' | ', $cells);
                }
                return implode("\n", $rows);

                // Skip formatting/metadata elements
            case 'pPr':
            case 'rPr':
            case 'sectPr':
            case 'bookmarkStart':
            case 'bookmarkEnd':
            case 'proofErr':
            case 'lastRenderedPageBreak':
            case 'instrText':
            case 'fldChar':
                return '';

            default:
                $out = '';
                foreach ($node->childNodes as $child) {
                    $out .= $this->extractXmlNode($child, $xpath);
                }
                return $out;
        }
    }

    // phpoffice/phpword fallback — used when ZIP parsing fails
    private function extractPhpWordElement(object $el): string
    {
        if ($el instanceof \PhpOffice\PhpWord\Element\Title) {
            $depth = method_exists($el, 'getDepth') ? (int) $el->getDepth() : 1;
            $inner = $el->getText();
            $text  = is_string($inner) ? $inner : $this->extractPhpWordElement($inner);
            return str_repeat('#', max(1, $depth)) . ' ' . trim($text);
        }
        if (
            $el instanceof \PhpOffice\PhpWord\Element\Paragraph ||
            $el instanceof \PhpOffice\PhpWord\Element\TextRun
        ) {
            $parts = [];
            foreach ($el->getElements() as $child) {
                $t = $this->extractPhpWordElement($child);
                if ($t !== '') $parts[] = $t;
            }
            return implode('', $parts);
        }
        if ($el instanceof \PhpOffice\PhpWord\Element\Text) {
            return $el->getText();
        }
        if ($el instanceof \PhpOffice\PhpWord\Element\Link) {
            return $el->getText();
        }
        if ($el instanceof \PhpOffice\PhpWord\Element\ListItem) {
            $obj  = $el->getTextObject();
            return '- ' . (method_exists($obj, 'getText') ? $obj->getText() : '');
        }
        if ($el instanceof \PhpOffice\PhpWord\Element\Table) {
            $rows = [];
            foreach ($el->getRows() as $row) {
                $cells = [];
                foreach ($row->getCells() as $cell) {
                    $parts = [];
                    foreach ($cell->getElements() as $cellEl) {
                        $t = $this->extractPhpWordElement($cellEl);
                        if ($t !== '') $parts[] = $t;
                    }
                    $cells[] = implode(' ', $parts);
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

    // ──────────────────────────────────────────────────────────────
    //  VISION-BASED NARRATION (PDF pages as images → GPT-4o vision)
    // ──────────────────────────────────────────────────────────────

    /**
     * Try to narrate a PDF using page images sent to GPT-4o vision.
     * Used when text extraction is sparse (equations stored as raster images).
     * Returns null if vision is unavailable or not needed.
     */
    private function tryNarrateWithVision(string $pdfPath, string $extractedText): ?string
    {
        if (!config('services.openai.api_key')) return null;

        // Skip vision if text extraction already yielded substantial content
        if (str_word_count($extractedText) > 300) return null;

        $gs = $this->findGhostscript();
        if (!$gs) {
            Log::info('Vision PDF skipped: Ghostscript not found. Install from ghostscript.com to enable.');
            return null;
        }

        try {
            return $this->narratePdfPagesWithVision($pdfPath, $gs);
        } catch (\Exception $e) {
            Log::error('Vision PDF narration failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Find the Ghostscript binary on the current system.
     */
    private function findGhostscript(): ?string
    {
        $candidates = PHP_OS_FAMILY === 'Windows'
            ? ['gswin64c', 'gswin32c', 'gs']
            : ['gs'];

        foreach ($candidates as $bin) {
            $cmd  = PHP_OS_FAMILY === 'Windows' ? "where {$bin} 2>nul" : "which {$bin} 2>/dev/null";
            $path = trim((string) shell_exec($cmd));
            $path = explode("\n", $path)[0]; // first result only
            if ($path && is_executable($path)) {
                return $path;
            }
        }
        return null;
    }

    /**
     * Render each PDF page to a PNG via Ghostscript, then narrate each with vision.
     */
    private function narratePdfPagesWithVision(string $pdfPath, string $gs): ?string
    {
        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voxora_' . uniqid();
        mkdir($tmpDir, 0700, true);

        try {
            $outPattern = $tmpDir . DIRECTORY_SEPARATOR . 'page_%d.png';

            $proc = proc_open(
                [
                    $gs,
                    '-dNOPAUSE',
                    '-dBATCH',
                    '-dQUIET',
                    '-sDEVICE=png16m',
                    '-r150',
                    '-sOutputFile=' . $outPattern,
                    $pdfPath
                ],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes
            );

            if (!is_resource($proc)) {
                throw new \RuntimeException('Failed to start Ghostscript');
            }
            fclose($pipes[1]);
            $stderr   = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            $exitCode = proc_close($proc);

            if ($exitCode !== 0) {
                throw new \RuntimeException("Ghostscript exited {$exitCode}: {$stderr}");
            }

            $pages = glob($tmpDir . DIRECTORY_SEPARATOR . 'page_*.png');
            natsort($pages);
            $pages = array_values(array_slice($pages, 0, 10)); // cap at 10 pages

            if (empty($pages)) {
                throw new \RuntimeException('Ghostscript produced no page images');
            }

            $narrations = [];
            $total      = count($pages);
            foreach ($pages as $i => $pageFile) {
                $b64       = base64_encode(file_get_contents($pageFile));
                $narration = $this->narratePageWithVision($b64, $i + 1, $total);
                if ($narration !== null) {
                    $narrations[] = $narration;
                }
            }

            return $narrations ? implode("\n\n", $narrations) : null;
        } finally {
            array_map('unlink', glob($tmpDir . DIRECTORY_SEPARATOR . '*'));
            @rmdir($tmpDir);
        }
    }

    /**
     * Send one PDF page image to GPT-4o vision and return the narration.
     */
    private function narratePageWithVision(string $base64Png, int $pageNum, int $totalPages): ?string
    {
        try {
            $response = Http::timeout(90)
                ->withToken(config('services.openai.api_key'))
                ->post(config('services.openai.endpoint', 'https://api.openai.com/v1/chat/completions'), [
                    'model'       => 'gpt-4o',
                    'temperature' => 0.2,
                    'max_tokens'  => 4096,
                    'messages'    => [
                        ['role' => 'system', 'content' => $this->narrationSystemPrompt()],
                        [
                            'role'    => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => "Ini adalah halaman {$pageNum} dari {$totalPages} halaman dokumen STEM. "
                                        . "Buat skrip narasi lengkap dari semua konten yang terlihat di halaman ini, "
                                        . "termasuk setiap rumus, persamaan, tabel, dan gambar.",
                                ],
                                [
                                    'type'      => 'image_url',
                                    'image_url' => [
                                        'url'    => 'data:image/png;base64,' . $base64Png,
                                        'detail' => 'high',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]);

            if ($response->successful()) {
                return trim($response->json('choices.0.message.content', '')) ?: null;
            }

            Log::warning("Vision API page {$pageNum} error: " . $response->status() . ' ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("Vision narration page {$pageNum} failed: " . $e->getMessage());
            return null;
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  TEXT-BASED NARRATION
    // ──────────────────────────────────────────────────────────────

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
        $systemPrompt = $this->narrationSystemPrompt();

        // ── Fallback ke simulasi jika tidak ada API key ────────────
        if (!config('services.openai.api_key')) {
            return $this->simulateRemediation($sanitized);
        }

        // ── Bersihkan artefak tampilan Word sebelum dikirim ke AI ──
        $sanitized = $this->cleanWordMathArtifacts($sanitized);

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
     * Shared narration system prompt used by both text and vision paths.
     */
    private function narrationSystemPrompt(): string
    {
        return <<<'PROMPT'
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
- √(2π) → "akar kuadrat dari dua pi"
- ³√x → "akar pangkat tiga dari x"
- \sqrt{x} (LaTeX) → "akar kuadrat dari x"
- \sqrt[n]{x} (LaTeX) → "akar pangkat n dari x"

PECAHAN:
- a/b (sederhana) → "a per b"
- (a+b)/(c-d) → "a tambah b, per, c kurang d"
- 1/(√(2π) σ) → "satu per, akar kuadrat dari dua pi, dikali sigma"
- \frac{a}{b} (LaTeX) → "a per b"
- \frac{df}{dx} → "d f per d x"

OPERASI DASAR:
- + → "ditambah"
- − → "dikurangi"
- × atau · → "dikali"
- * (konvolusi antara dua fungsi/sinyal/gambar) → "dikonvolusi dengan"
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
- … atau ... → "dan seterusnya"
- ~ → "kira-kira"

NOTASI FUNGSI:
- f(x) → "f dari x"
- f(x, y) → "f dari x koma y"
- G(x, y) → "G dari x koma y"
- I(x-i, y-j) → "I dari x dikurangi i koma y dikurangi j"
PENTING: Koma di dalam argumen fungsi SELALU dibaca "koma" — JANGAN pernah dibaca "titik".
Tanda kurung argumen fungsi BUKAN tanda kurung biasa; baca sebagai "f dari [argumen]".

KALKULUS:
- ∑ atau \sum_{i=1}^{n} → "jumlah, i dari satu sampai n, dari"
  PENTING: ∑ (operator penjumlahan) dibaca "jumlah" — JANGAN "sigma"!
  σ (huruf Yunani sigma kecil) dibaca "sigma". Keduanya adalah simbol yang berbeda.
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
Catatan: Σ sebagai nama matriks/himpunan dibaca "Sigma besar". Sebagai operator ∑ dengan batas atas-bawah, dibaca "jumlah".

INDEKS DAN SUBSKRIP:
- x_i atau xᵢ → "x sub i"
- a_0 atau a₀ → "a sub nol"
- v_{max} → "v sub maks"
- T_{1/2} → "T sub setengah"
- I_blurred → "I sub blur"

VEKTOR DAN MATRIKS:
- **v** atau v⃗ → "vektor v"
- |v| → "besar vektor v"
- v · w → "vektor v titik vektor w"
- v × w → "vektor v silang vektor w"
- Matriks A → "matriks A"
- A_{m×n} → "matriks A berukuran m kali n"
- det(A) → "determinan matriks A"
- Aᵀ → "transpose matriks A"
- Matriks angka (grid/tabel nilai) → baca baris per baris:
  "Baris pertama: [nilai], [nilai], [nilai]. Baris kedua: [nilai], [nilai], [nilai]. Dan seterusnya."
  Contoh kernel 3×3 dengan nilai 1 2 1 / 2 4 2 / 1 2 1:
  "Baris pertama: satu, dua, satu. Baris kedua: dua, empat, dua. Baris ketiga: satu, dua, satu."

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
- ⌈x⌉ → "x dibulatkan ke atas" (fungsi ceiling/langit-langit)
- ⌊x⌋ → "x dibulatkan ke bawah" (fungsi floor/lantai)
- ⌈3σ⌉ → "tiga sigma dibulatkan ke atas"
- O(f(n)) → "O besar dari f dari n" (notasi kompleksitas Big-O)
- O(k²) → "O besar dari k kuadrat"
- O(2k) → "O besar dari dua k"
- Θ(f(n)) → "Theta dari f dari n"

ANGKA DESIMAL:
Titik desimal dalam angka SELALU dibaca "koma":
- 2.71828 → "dua koma tujuh satu delapan dua delapan"
- 0.85 → "nol koma delapan lima"
- 99.7% → "sembilan puluh sembilan koma tujuh persen"

═══ STRUKTUR DOKUMEN ═══

- Heading "# Bab 1" atau "# BAB I" → "BAB SATU."  (tulis angka dengan kata)
- Heading "## Sub-bagian" → "Sub-bagian: [judul]."
- Heading "### ..." → "Bagian: [judul]."
- Penomoran bagian "1.", "2.", "3." → "Bagian satu.", "Bagian dua.", "Bagian tiga."
- Penomoran sub-bagian "1.1.", "2.3." → "Sub-bagian satu titik satu.", "Sub-bagian dua titik tiga."
- Gambar → "Gambar [nomor]: [deskripsikan dari caption atau konteks sekitar]."
- Tabel (baris dengan |) → "Tabel [nomor] memuat kolom [daftar nama kolom]. Baca baris data satu per satu: baris satu, nilai kolom pertama adalah ..., nilai kolom kedua adalah ..., dan seterusnya."
- Tabel parameter (Simbol | Nama | Keterangan) → baca setiap baris: "Simbol [simbol], nama [nama], keterangan: [keterangan]."
- Contoh soal → "Contoh Soal [nomor]:"
- Penyelesaian/jawaban → "Penyelesaian:"
- Definisi → "Definisi:"
- Teorema → "Teorema [nomor atau nama]:"
- Lemma/Korolari → baca sesuai labelnya
- Bukti → "Bukti:"
- Catatan kaki → "Catatan: [isi]."
- Numbered list → "Pertama,", "Kedua,", "Ketiga,", dst.
- Bullet list (prefix - atau tab) → "Pertama,", "Kedua,", "Ketiga,", dst.

═══ ATURAN PENULISAN SKRIP ═══

1. Pertahankan SEMUA informasi—jangan ringkas, jangan hilangkan konten apapun.
2. Tambahkan koma (,) pada jeda alami saat membaca persamaan atau ekspresi panjang.
3. Setiap persamaan yang berdiri sendiri diakhiri tanda titik (.).
4. Gunakan kata transisi antar paragraf: "Selanjutnya,", "Perhatikan bahwa,", "Diketahui bahwa,", "Dengan demikian,", "Oleh karena itu,".
5. Tidak ada simbol, karakter khusus, LaTeX, atau markup apapun dalam output—semua harus tertulis dalam huruf dan kata.
6. Bahasa Indonesia yang mengalir natural—tidak kaku, tidak robotik.
7. Jika dokumen input berbahasa Inggris, narasi TETAP ditulis sepenuhnya dalam Bahasa Indonesia. Terjemahkan semua teks deskriptif, judul, label, dan keterangan ke Bahasa Indonesia.
8. Angka di luar ekspresi matematika boleh ditulis sebagai digit (1, 2, 3).
9. Singkatan umum dieja penuh: "yaitu" bukan "i.e.", "misalnya" bukan "e.g.", "dan lain-lain" bukan "dll." atau "etc.".
10. Satuan fisika dibaca lengkap: "meter per detik kuadrat" bukan "m/s²".
11. Angka desimal dibaca dengan "koma": 2.71 → "dua koma tujuh satu", 0.85 → "nol koma delapan lima".
12. Notasi perkiraan "approx." dibaca "kurang lebih"; "≈" dibaca "kurang lebih sama dengan"; "~" dibaca "kira-kira".

═══ TANDA [PERSAMAAN: ...] ═══

Teks dari dokumen DOCX mungkin mengandung blok [PERSAMAAN: ...]. Blok ini berisi
persamaan matematika yang diekstrak dari format OMML Microsoft Word. Isi di dalamnya
adalah karakter-karakter mentah dari persamaan tersebut. Kamu WAJIB membaca dan
menerjemahkan SETIAP blok [PERSAMAAN: ...] menjadi narasi lengkap sesuai konvensi
di atas. JANGAN lewati atau abaikan satu pun blok [PERSAMAAN: ...].

═══ ATURAN WAJIB TENTANG PERSAMAAN ═══

WAJIB: Setiap persamaan, rumus, atau ekspresi matematika yang ada di input HARUS
muncul dalam bentuk narasi di output. Dilarang keras melewati, menghilangkan, atau
meringkas persamaan. Jika sebuah paragraf di input diawali atau diakhiri dengan
persamaan, persamaan itu HARUS dinarasikan secara lengkap dalam output.

Persamaan yang hanya disebut dengan frase seperti "seperti berikut" atau "sebagai berikut"
tanpa lanjutan narasi persamaannya adalah KESALAHAN. Selalu baca persamaannya.

═══ CONTOH NARASI PERSAMAAN ═══

Input: G(x, y)=1/(2πσ^2) e^(-(x^2+y^2)/(2σ^2))
Output: G dari x koma y sama dengan, satu per dua kali pi kali sigma kuadrat, dikali e pangkat negatif, x kuadrat ditambah y kuadrat, per dua sigma kuadrat.

Input: [PERSAMAAN: G(x,y)=1/(2πσ2)e-(x2+y2)/(2σ2)]
Output: G dari x koma y sama dengan, satu per dua kali pi kali sigma kuadrat, dikali e pangkat negatif, x kuadrat ditambah y kuadrat, per dua sigma kuadrat.

Input: I_blurred(x,y) = ∑_(i=-k)^k ∑_(j=-k)^k G(i,j)·I(x-i,y-j)
Output: I sub blur dari x koma y sama dengan, jumlah dari i sama dengan negatif k sampai k, dari jumlah j sama dengan negatif k sampai k, dari G dari i koma j, dikali I dari x dikurangi i koma y dikurangi j.

Input: I_blurred(x, y) = G(x, y) * I(x, y)
Output: I sub blur dari x koma y sama dengan, G dari x koma y, dikonvolusi dengan I dari x koma y.

Input: G(x) = 1/(√(2π) σ) e^(x^2/(2σ^2))
Output: G dari x sama dengan, satu per, akar kuadrat dari dua pi, dikali sigma, dikali e pangkat, x kuadrat per dua sigma kuadrat.

Input: k = ⌈3σ⌉
Output: k sama dengan tiga sigma dibulatkan ke atas.

Input: kernel 3×3 — 1 2 1 / 2 4 2 / 1 2 1
Output: Baris pertama: satu, dua, satu. Baris kedua: dua, empat, dua. Baris ketiga: satu, dua, satu.

Input: O(k²) to O(2k)
Output: O besar dari k kuadrat menjadi O besar dari dua k.
PROMPT;
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
            '/(\w+)\s*=\s*(\w+)/' => '$1 sama dengan $2',
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
     * Bersihkan artefak tampilan dari format persamaan Word (Linear Format / OMML).
     * Karakter-karakter ini adalah sisa render Word yang tidak bermakna sebagai teks.
     */
    private function cleanWordMathArtifacts(string $text): string
    {
        return strtr($text, [
            '▒' => '',    // U+2592 — pemisah visual antar elemen persamaan Word
            '〖' => '(',  // U+3016 — bracket kiri persamaan Word
            '〗' => ')',  // U+3017 — bracket kanan persamaan Word
            '⁢'  => '',   // U+2062 — invisible times (Unicode math)
            '⁣'  => '',   // U+2063 — invisible separator (Unicode math)
        ]);
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
            'a' => '⠁',
            'b' => '⠃',
            'c' => '⠉',
            'd' => '⠙',
            'e' => '⠑',
            'f' => '⠋',
            'g' => '⠛',
            'h' => '⠓',
            'i' => '⠊',
            'j' => '⠚',
            'k' => '⠅',
            'l' => '⠇',
            'm' => '⠍',
            'n' => '⠝',
            'o' => '⠕',
            'p' => '⠏',
            'q' => '⠟',
            'r' => '⠗',
            's' => '⠎',
            't' => '⠞',
            'u' => '⠥',
            'v' => '⠧',
            'w' => '⠺',
            'x' => '⠭',
            'y' => '⠽',
            'z' => '⠵',
            // Angka (awali dengan ⠼)
            '0' => '⠼⠚',
            '1' => '⠼⠁',
            '2' => '⠼⠃',
            '3' => '⠼⠉',
            '4' => '⠼⠙',
            '5' => '⠼⠑',
            '6' => '⠼⠋',
            '7' => '⠼⠛',
            '8' => '⠼⠓',
            '9' => '⠼⠊',
            // Tanda baca
            ' ' => '⠀',
            ',' => '⠂',
            '.' => '⠄',
            '?' => '⠦',
            '!' => '⠖',
            ':' => '⠒',
            ';' => '⠆',
            '-' => '⠤',
            '(' => '⠦',
            ')' => '⠴',
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
