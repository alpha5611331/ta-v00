<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TanyaController extends Controller
{
    public function index()
    {
        // Tanpa doc_id: tampilkan tanya bot umum
        return view('tanya.index', ['document' => null]);
    }

    public function show(int $id)
    {
        $document = Document::where('user_id', auth()->id())->findOrFail($id);

        return view('tanya.index', ['document' => $document]);
    }

    public function ask(Request $request)
    {
        $request->validate([
            'question'    => ['required', 'string', 'max:1000'],
            'doc_context' => ['nullable', 'string', 'max:50000'],
            'document_id' => ['nullable', 'integer', 'exists:documents,id'],
        ]);

        $question = $request->input('question');
        $context  = $request->input('doc_context', '');
        $document = null;

        if ($request->filled('document_id')) {
            $document = Document::where('user_id', auth()->id())
                ->findOrFail((int) $request->input('document_id'));
        }

        $systemPrompt = <<<PROMPT
Kamu adalah asisten VOXORA yang membantu tunanetra memahami dokumen STEM.
Jawab pertanyaan berdasarkan konteks dokumen yang diberikan.
Gunakan bahasa Indonesia yang natural, jelas, dan ramah screen reader.
Hindari simbol matematika — tulis sebagai kalimat natural.
Jawab singkat, padat, dan struktural. Awali jawaban langsung tanpa basa-basi.
PROMPT;

        $messages = [];
        if ($context) {
            $messages[] = ['role' => 'user', 'content' => "Konteks dokumen:\n\n$context\n\nPertanyaan: $question"];
        } else {
            $messages[] = ['role' => 'user', 'content' => $question];
        }

        // Simulasi jika tidak ada API
        if (!config('services.openai.api_key')) {
            $answer = $this->simulateAnswer($question, $context);
            $this->recordQuestion($document, $question, $answer, true);

            return response()->json(['answer' => $answer, 'simulated' => true]);
        }

        try {
            $response = Http::timeout(30)
                ->withToken(config('services.openai.api_key'))
                ->post(config('services.openai.endpoint', 'https://api.openai.com/v1/chat/completions'), [
                    'model'       => config('services.openai.model_qa', 'gpt-4o-mini'),
                    'temperature' => 0.4,
                    'max_tokens'  => 600,
                    'messages'    => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ...$messages,
                    ],
                ]);

            if ($response->successful()) {
                $answer = $response->json('choices.0.message.content', '');
                $this->recordQuestion($document, $question, $answer, false, config('services.openai.model_qa', 'gpt-4o-mini'));

                return response()->json(['answer' => $answer]);
            }
        } catch (\Exception $e) {
            Log::error('TanyaBot error: ' . $e->getMessage());
        }

        $answer = $this->simulateAnswer($question, $context);
        $this->recordQuestion($document, $question, $answer, true);

        return response()->json(['answer' => $answer, 'simulated' => true]);
    }

    private function recordQuestion(?Document $document, string $question, string $answer, bool $simulated, ?string $model = null): void
    {
        DocumentQuestion::create([
            'user_id'     => auth()->id(),
            'document_id' => $document?->id,
            'question'    => $question,
            'answer'      => $answer,
            'simulated'   => $simulated,
            'model'       => $model,
            'answered_at' => now(),
        ]);
    }

    private function simulateAnswer(string $question, string $context): string
    {
        $q = mb_strtolower($question);
        if (str_contains($q, 'apa') && str_contains($q, 'integral')) {
            return 'Integral adalah operasi matematika kebalikan dari turunan. Integral tak tentu dari f dari x ditulis sebagai integral f x dx, dan hasilnya selalu ditambah konstanta C.';
        }
        if (str_contains($q, 'rumus') || str_contains($q, 'formula')) {
            return 'Berdasarkan dokumen ini, rumus yang relevan telah dikonversi ke kalimat natural agar dapat dibaca oleh screen reader. Silakan baca bagian hasil remediasi untuk detail lengkapnya.';
        }
        if (str_contains($q, 'halo') || str_contains($q, 'hi') || str_contains($q, 'selamat')) {
            return 'Halo! Saya asisten VOXORA. Saya siap membantu Anda memahami isi dokumen ini. Silakan ajukan pertanyaan tentang materi yang ingin Anda pelajari.';
        }
        return 'Pertanyaan Anda telah diterima. Berdasarkan dokumen yang tersedia, saya akan membantu menjelaskan konsep tersebut. Untuk hasil terbaik, sambungkan platform ini dengan layanan AI melalui pengaturan API.';
    }
}
