<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    private const PER_PAGE_USERS = 10;
    private const PER_PAGE_DOCS  = 10;

    public function index()
    {
        $recentUsers = User::withCount('documents')
            ->latest()
            ->take(5)
            ->get();

        $recentDocs = Document::with('user')
            ->latest()
            ->take(5)
            ->get();

        $sentDocs = Document::whereNotNull('braille_sent_at')->get();

        $stats = [
            'total_users'  => User::count(),
            'total_docs'   => Document::count(),
            'total_chunks' => $sentDocs->sum(fn (Document $doc) => (int) ceil(($doc->char_count ?? 0) / 20)),
            'active_users' => User::where('is_active', true)->count(),
        ];

        return view('admin.index', compact('stats', 'recentUsers', 'recentDocs'));
    }

    public function users(Request $request)
    {
        $query = trim($request->input('q', ''));
        $sort  = $request->input('sort', 'newest');

        $usersQuery = User::withCount('documents')
            ->when($query !== '', function ($users) use ($query) {
                $users->where(function ($search) use ($query) {
                    $search->where('name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                });
            });

        match ($sort) {
            'oldest' => $usersQuery->oldest(),
            'name'   => $usersQuery->orderBy('name'),
            'docs'   => $usersQuery->orderByDesc('documents_count'),
            default  => $usersQuery->latest(),
        };

        $users = $usersQuery
            ->paginate(self::PER_PAGE_USERS)
            ->withQueryString();

        $totalActive   = User::where('is_active', true)->count();
        $totalInactive = User::where('is_active', false)->count();

        return view('admin.users', compact('users', 'totalActive', 'totalInactive'));
    }

    public function docs(Request $request)
    {
        $query      = trim($request->input('q', ''));
        $typeFilter = $request->input('type', 'all');
        $sort       = $request->input('sort', 'newest');

        $docsQuery = Document::with('user')
            ->when($typeFilter !== 'all', fn ($docs) => $docs->where('file_type', $typeFilter))
            ->when($query !== '', function ($docs) use ($query) {
                $docs->where(function ($search) use ($query) {
                    $search->where('original_filename', 'like', "%{$query}%")
                        ->orWhereHas('user', function ($users) use ($query) {
                            $users->where('name', 'like', "%{$query}%")
                                ->orWhere('email', 'like', "%{$query}%");
                        });
                });
            });

        match ($sort) {
            'oldest'    => $docsQuery->oldest(),
            'name'      => $docsQuery->orderBy('original_filename'),
            'size_asc'  => $docsQuery->orderBy('char_count'),
            'size_desc' => $docsQuery->orderByDesc('char_count'),
            default     => $docsQuery->latest(),
        };

        $docs = $docsQuery
            ->paginate(self::PER_PAGE_DOCS)
            ->withQueryString();

        $statDocs = [
            'total'        => Document::count(),
            'pdf'          => Document::where('file_type', 'pdf')->count(),
            'docx'         => Document::where('file_type', 'docx')->count(),
            'total_chars'  => Document::sum('char_count'),
            'braille_sent' => Document::whereNotNull('braille_sent_at')->count(),
        ];

        return view('admin.docs', compact('docs', 'statDocs'));
    }

    public function deleteUser(int $id)
    {
        abort_if(auth()->id() === $id, 403, 'Akun yang sedang digunakan tidak dapat dihapus.');

        $user = User::findOrFail($id);
        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.users')
            ->with('success', "Pengguna {$name} berhasil dihapus.");
    }
}
