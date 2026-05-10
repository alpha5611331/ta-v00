<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PustakaController extends Controller
{
    private const PER_PAGE = 10;

    public function index(Request $request)
    {
        $query = trim($request->input('q', ''));

        $documents = Document::where('user_id', auth()->id())
            ->when($query !== '', function ($docs) use ($query) {
                $docs->where(function ($search) use ($query) {
                    $search->where('original_filename', 'like', "%{$query}%")
                        ->orWhere('remediated_text', 'like', "%{$query}%");
                });
            })
            ->latest()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pustaka', compact('documents'));
    }

    public function show(int $id)
    {
        $document = Document::where('user_id', auth()->id())->findOrFail($id);

        return view('pustaka.show', ['document' => $document]);
    }

    public function destroy(int $id)
    {
        $document = Document::where('user_id', auth()->id())->findOrFail($id);

        Storage::disk('local')->delete($document->storage_path);
        $document->delete();

        return redirect()->route('pustaka.index')
            ->with('success', 'Dokumen berhasil dihapus dari pustaka.');
    }
}
