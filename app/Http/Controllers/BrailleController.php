<?php

namespace App\Http\Controllers;

use App\Models\BrailleDelivery;
use App\Models\Document;
use Illuminate\Http\Request;

class BrailleController extends Controller
{
    public function index(Request $request)
    {
        $docId    = $request->input('doc_id');
        $document = null;

        if ($docId) {
            $document = Document::where('user_id', auth()->id())->findOrFail((int) $docId);
        }

        return view('braille.index', compact('document'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'result_text' => ['required', 'string'],
            'chunk_size'  => ['required', 'integer', 'in:5,10,20,40'],
            'document_id' => ['nullable', 'integer', 'exists:documents,id'],
        ]);

        $document = null;
        if ($request->filled('document_id')) {
            $document = Document::where('user_id', auth()->id())
                ->findOrFail((int) $request->input('document_id'));
        }

        $text      = $request->input('result_text');
        $chunkSize = (int) $request->input('chunk_size', 20);
        $clean     = preg_replace('/\s+/', ' ', trim($text));
        $chunks    = array_filter(str_split($clean, $chunkSize), fn($c) => trim($c) !== '');

        $brailleChunks = array_map(fn($c) => [
            'text'    => $c,
            'braille' => $this->toBraille($c),
        ], $chunks);

        BrailleDelivery::create([
            'user_id'     => auth()->id(),
            'document_id' => $document?->id,
            'chunk_size'  => $chunkSize,
            'chunk_count' => count($brailleChunks),
            'char_count'  => mb_strlen($clean),
            'status'      => 'sent',
            'target'      => 'edubraille',
            'sent_at'     => now(),
        ]);

        $document?->update(['braille_sent_at' => now()]);

        return view('braille.index', [
            'document'      => $document,
            'brailleChunks' => $brailleChunks,
            'sentAt'        => now()->format('H:i:s'),
            'chunkSize'     => $chunkSize,
        ])->with('success', count($brailleChunks) . ' chunk braille berhasil dikirim ke EduBraille.');
    }

    private function toBraille(string $text): string
    {
        $map = [
            'a'=>'⠁','b'=>'⠃','c'=>'⠉','d'=>'⠙','e'=>'⠑','f'=>'⠋','g'=>'⠛','h'=>'⠓',
            'i'=>'⠊','j'=>'⠚','k'=>'⠅','l'=>'⠇','m'=>'⠍','n'=>'⠝','o'=>'⠕','p'=>'⠏',
            'q'=>'⠟','r'=>'⠗','s'=>'⠎','t'=>'⠞','u'=>'⠥','v'=>'⠧','w'=>'⠺','x'=>'⠭',
            'y'=>'⠽','z'=>'⠵',' '=>'⠀',','=>'⠂','.'=>'⠄','?'=>'⠦','!'=>'⠖',
            '0'=>'⠼⠚','1'=>'⠼⠁','2'=>'⠼⠃','3'=>'⠼⠉','4'=>'⠼⠙','5'=>'⠼⠑',
            '6'=>'⠼⠋','7'=>'⠼⠛','8'=>'⠼⠓','9'=>'⠼⠊',
        ];
        $result = '';
        foreach (preg_split('//u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) as $char) {
            $result .= $map[$char] ?? '⠿';
        }
        return $result;
    }
}
