<?php

namespace App\Http\Controllers;

use App\Models\BrailleDelivery;
use App\Models\Document;
use App\Models\EduBrailleDevice;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

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

        $stats = [
            'total_users'  => User::count(),
            'total_docs'   => Document::count(),
            'total_chunks' => BrailleDelivery::sum('chunk_count'),
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

    public function edubraille()
    {
        $config = [
            'endpoint'  => config('services.edubraille.endpoint', ''),
            'token'     => config('services.edubraille.token', ''),
            'device_id' => config('services.edubraille.device_id', 'DEFAULT'),
        ];

        $devices = EduBrailleDevice::query()
            ->orderByDesc('is_active')
            ->orderBy('device_id')
            ->get();

        $deliveries = BrailleDelivery::with(['user', 'document'])
            ->latest('sent_at')
            ->latest()
            ->take(10)
            ->get();

        $logs = $deliveries->map(fn (BrailleDelivery $delivery) => (object) [
            'user'       => $delivery->user?->name ?? '-',
            'doc'        => $delivery->document?->original_filename ?? 'Teks langsung',
            'chunks'     => $delivery->chunk_count,
            'chunk_size' => $delivery->chunk_size,
            'status'     => $delivery->status === 'sent' ? 'success' : $delivery->status,
            'sent_at'    => $delivery->sent_at ?? $delivery->created_at,
        ]);

        $stats = [
            'total_sent'     => BrailleDelivery::where('status', 'sent')->sum('chunk_count'),
            'total_failed'   => BrailleDelivery::where('status', 'failed')->count(),
            'total_sessions' => BrailleDelivery::count(),
            'last_activity'  => BrailleDelivery::latest('sent_at')->value('sent_at'),
        ];

        return view('admin.edubraille', compact('config', 'devices', 'logs', 'stats'));
    }

    public function saveEdubraille(Request $request)
    {
        $request->validate([
            'endpoint'  => ['required', 'url'],
            'token'     => ['nullable', 'string', 'max:255'],
            'device_id' => ['required', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'endpoint.required'  => 'URL endpoint wajib diisi.',
            'endpoint.url'       => 'Format URL tidak valid.',
            'device_id.required' => 'Device ID wajib diisi.',
        ]);

        EduBrailleDevice::query()->updateOrCreate(
            ['device_id' => $request->device_id],
            [
                'endpoint'  => $request->endpoint,
                'token'     => $request->token ?: null,
                'is_active' => $request->boolean('is_active', true),
            ]
        );

        return back()->with('success', 'Perangkat EduBraille berhasil disimpan.');
    }

    public function testConnection(Request $request)
    {
        $deviceId = (string) $request->input('device_id', '');
        $device = $deviceId !== ''
            ? EduBrailleDevice::where('device_id', $deviceId)->first()
            : EduBrailleDevice::where('is_active', true)->orderBy('device_id')->first();

        $endpoint = $device?->endpoint ?: config('services.edubraille.endpoint');

        if (! $endpoint) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Endpoint belum dikonfigurasi. Simpan konfigurasi terlebih dahulu.',
            ]);
        }

        try {
            $token = $device?->token ?: config('services.edubraille.token', '');
            $payload = [
                'device_id' => $device?->device_id ?: config('services.edubraille.device_id', 'DEFAULT'),
                'chunks'    => [['text' => 'TEST', 'braille' => 'test']],
                'test'      => true,
            ];

            $http = Http::timeout(8);
            if ($token) {
                $http = $http->withToken($token);
            }

            $response = $http->post($endpoint, $payload);

            return $response->successful()
                ? response()->json(['status' => 'success', 'message' => 'Koneksi berhasil! Perangkat merespons HTTP '.$response->status().'.'])
                : response()->json(['status' => 'error', 'message' => 'Perangkat merespons dengan error HTTP '.$response->status().'.']);
        } catch (ConnectionException $e) {
            return response()->json(['status' => 'error', 'message' => 'Tidak dapat terhubung. Pastikan perangkat menyala dan endpoint benar.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Kesalahan: '.$e->getMessage()]);
        }
    }

    public function deleteEdubrailleDevice(Request $request)
    {
        $request->validate([
            'device_id' => ['required', 'string', 'max:50'],
        ]);

        EduBrailleDevice::where('device_id', $request->device_id)->delete();

        return back()->with('success', 'Perangkat EduBraille dihapus.');
    }

    public function setActiveEdubrailleDevice(Request $request)
    {
        $request->validate([
            'device_id' => ['required', 'string', 'max:50'],
        ]);

        EduBrailleDevice::query()->update(['is_active' => false]);
        EduBrailleDevice::where('device_id', $request->device_id)->update(['is_active' => true]);

        return back()->with('success', 'Perangkat aktif diperbarui.');
    }

    public function sendChunk(Request $request)
    {
        $request->validate([
            'text'       => ['required', 'string', 'max:5000'],
            'chunk_size' => ['required', 'integer', 'in:5,10,20,40'],
        ]);

        $text   = preg_replace('/\s+/', ' ', trim($request->text));
        $size   = (int) $request->chunk_size;
        $chunks = array_values(array_filter(str_split($text, $size), fn ($chunk) => trim($chunk) !== ''));

        BrailleDelivery::create([
            'user_id'     => auth()->id(),
            'document_id' => null,
            'chunk_size'  => $size,
            'chunk_count' => count($chunks),
            'char_count'  => mb_strlen($text),
            'status'      => 'sent',
            'target'      => 'edubraille-admin',
            'sent_at'     => now(),
        ]);

        return response()->json([
            'status'       => 'success',
            'chunks_count' => count($chunks),
            'simulated'    => ! config('services.edubraille.endpoint'),
            'message'      => count($chunks).' chunk disiapkan untuk EduBraille.',
        ]);
    }

    private function updateEnv(array $data): void
    {
        $path = base_path('.env');
        $content = file_get_contents($path);

        foreach ($data as $key => $value) {
            $value = str_contains($value, ' ') ? '"'.$value.'"' : $value;
            $pattern = "/^{$key}=.*/m";
            $replace = "{$key}={$value}";

            $content = preg_match($pattern, $content)
                ? preg_replace($pattern, $replace, $content)
                : $content.PHP_EOL.$replace;
        }

        file_put_contents($path, $content);
    }
}
