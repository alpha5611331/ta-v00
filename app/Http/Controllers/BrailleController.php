<?php

namespace App\Http\Controllers;

use App\Models\BrailleDelivery;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\EduBrailleDevice;
use Illuminate\Support\Facades\Http;

class BrailleController extends Controller
{
    public function index(Request $request)
    {
        $docId    = $request->input('doc_id');
        $document = null;

        if ($docId) {
            $document = Document::where('user_id', auth()->id())->find((int) $docId);
        }

        // Ambil teks dari session jika diteruskan dari halaman upload
        $prefillText = session()->pull('braille_text');

        $devices = EduBrailleDevice::query()
            ->where('is_active', true)
            ->orderBy('device_id')
            ->get();

        return view('braille.index', compact('document', 'prefillText', 'devices'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'result_text' => ['required', 'string'],
            'chunk_size'  => ['required', 'integer', 'in:5,10,20,40'],
            'device_id'   => ['required', 'string', 'max:50'],
        ]);

        $text      = $request->input('result_text');
        $chunkSize = (int) $request->input('chunk_size', 20);
        $deviceId  = (string) $request->input('device_id');

        $device = EduBrailleDevice::query()
            ->where('is_active', true)
            ->where('device_id', $deviceId)
            ->first();

        if (! $device) {
            return back()
                ->withInput()
                ->with('error', 'Perangkat EduBraille tidak ditemukan atau tidak aktif.');
        }

        $clean     = preg_replace('/\s+/', ' ', trim($text));
        $chunks    = array_filter(str_split($clean, $chunkSize), fn($c) => trim($c) !== '');

        $brailleChunks = array_map(fn($c) => [
            'text'    => $c,
            'braille' => $this->toBraille($c),
        ], $chunks);

        $delivery = BrailleDelivery::create([
            'user_id'     => auth()->id(),
            'document_id' => null,
            'chunk_size'  => $chunkSize,
            'chunk_count' => count($brailleChunks),
            'char_count'  => mb_strlen($text),
            'status'      => 'pending',
            'target'      => 'edubraille:'.$device->device_id,
            'sent_at'     => null,
        ]);

        try {
            $payload = [
                'device_id' => $device->device_id,
                'chunks'    => $brailleChunks,
                'test'      => false,
            ];

            $http = Http::timeout(15);
            if ($device->token) {
                $http = $http->withToken($device->token);
            }

            $response = $http->post($device->endpoint, $payload);

            if (! $response->successful()) {
                $delivery->update([
                    'status'        => 'failed',
                    'error_message' => 'HTTP '.$response->status(),
                    'sent_at'       => now(),
                ]);

                return back()
                    ->withInput()
                    ->with('error', 'Gagal mengirim ke EduBraille. Perangkat merespons HTTP '.$response->status().'.');
            }

            $delivery->update([
                'status'  => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $delivery->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'sent_at'       => now(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Gagal mengirim ke EduBraille: '.$e->getMessage());
        }

        return view('braille.index', [
            'document'      => null,
            'prefillText'   => $text,
            'devices'       => EduBrailleDevice::where('is_active', true)->orderBy('device_id')->get(),
            'brailleChunks' => $brailleChunks,
            'sentAt'        => now()->format('H:i:s'),
            'chunkSize'     => $chunkSize,
        ])->with('success', count($brailleChunks) . ' chunk braille berhasil dikirim ke EduBraille ('.$device->device_id.').');
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