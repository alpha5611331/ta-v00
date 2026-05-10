<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\PustakaController;
use App\Http\Controllers\TanyaController;
use App\Http\Controllers\BrailleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;

/*
|--------------------------------------------------------------------------
| VOXORA – Web Routes
|--------------------------------------------------------------------------
*/

/* ══════════════════════════════════════════════════
   PUBLIK
══════════════════════════════════════════════════ */

Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->is_admin
            ? redirect()->route('admin.index')
            : redirect()->route('upload.index');
    }
    return view('welcome');
})->name('home');

Route::get('/login', function () {
    if (auth()->check()) {
        return auth()->user()->is_admin
            ? redirect()->route('admin.index')
            : redirect()->route('upload.index');
    }
    return view('auth.login');
})->name('login');

Route::post('/login', function (\Illuminate\Http\Request $request) {
    $creds = $request->validate([
        'email'    => ['required', 'email'],
        'password' => ['required'],
    ], [
        'email.required'    => 'Email wajib diisi.',
        'email.email'       => 'Format email tidak valid.',
        'password.required' => 'Kata sandi wajib diisi.',
    ]);

    if (\Illuminate\Support\Facades\Auth::attempt($creds, $request->boolean('remember'))) {
        $request->session()->regenerate();
        // ── Redirect berbeda: admin ke /admin, user ke /upload ──
        return auth()->user()->is_admin
            ? redirect()->route('admin.index')
            : redirect()->route('upload.index');
    }

    return back()->withErrors(['email' => 'Email atau kata sandi tidak sesuai.'])
                 ->onlyInput('email');
})->name('login.post');

Route::get('/register', function () {
    if (auth()->check()) {
        return auth()->user()->is_admin
            ? redirect()->route('admin.index')
            : redirect()->route('upload.index');
    }
    return view('auth.register');
})->name('register');

Route::post('/register', function (\Illuminate\Http\Request $request) {
    $data = $request->validate([
        'name'                  => ['required', 'string', 'max:255'],
        'email'                 => ['required', 'email', 'unique:users,email'],
        'password'              => ['required', 'min:8', 'confirmed'],
        'password_confirmation' => ['required'],
    ]);
    $user = \App\Models\User::create([
        'name'     => $data['name'],
        'email'    => $data['email'],
        'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
        'is_admin' => false,
    ]);
    \Illuminate\Support\Facades\Auth::login($user);
    return redirect()->route('upload.index')
        ->with('success', 'Akun berhasil dibuat. Selamat datang di VOXORA, ' . $user->name . '!');
})->name('register.post');

Route::post('/logout', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('home');
})->name('logout')->middleware('auth');


/* ══════════════════════════════════════════════════
   USER BIASA  (login + bukan admin)
══════════════════════════════════════════════════ */

Route::middleware(['auth', 'app.user'])->group(function () {

    Route::get('/dashboard', fn() => redirect()->route('upload.index'))
         ->name('dashboard');

    /* Upload */
    Route::get('/upload',          [UploadController::class, 'index'])->name('upload.index');
    Route::post('/upload',         [UploadController::class, 'store'])->name('upload.store');
    Route::post('/upload/export',    [UploadController::class, 'export'])->name('upload.export');
    Route::post('/upload/tobraille', [UploadController::class, 'toBraille'])->name('upload.tobraille');

    /* Pustaka */
    Route::get('/pustaka',         [PustakaController::class, 'index'])->name('pustaka.index');
    Route::get('/pustaka/{id}',    [PustakaController::class, 'show'])->name('pustaka.show')->where('id', '[0-9]+');
    Route::delete('/pustaka/{id}', [PustakaController::class, 'destroy'])->name('pustaka.destroy')->where('id', '[0-9]+');

    /* Tanya Bot */
    Route::get('/tanya',           [TanyaController::class, 'index'])->name('tanya.index');
    Route::get('/tanya/{id}',      [TanyaController::class, 'show'])->name('tanya.show')->where('id', '[0-9]+');
    Route::post('/tanya/ask',      [TanyaController::class, 'ask'])->name('tanya.ask');

    /* EduBraille (user) */
    Route::get('/braille',         [BrailleController::class, 'index'])->name('braille.index');
    Route::post('/braille/send',   [BrailleController::class, 'send'])->name('braille.send');

    /* Profil */
    Route::get('/profile',          [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile',          [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});


/* ══════════════════════════════════════════════════
   ADMIN  (login + is_admin = true)
══════════════════════════════════════════════════ */

Route::middleware(['auth', 'app.admin'])
     ->prefix('admin')
     ->name('admin.')
     ->group(function () {

    /* Dashboard */
    Route::get('/',                    [AdminController::class, 'index'])->name('index');

    /* Kelola Pengguna */
    Route::get('/users',               [AdminController::class, 'users'])->name('users');
    Route::delete('/users/{id}',       [AdminController::class, 'deleteUser'])->name('users.delete')->where('id', '[0-9]+');

    /* Kelola Dokumen */
    Route::get('/docs',                [AdminController::class, 'docs'])->name('docs');

    /* Manajemen EduBraille */
    Route::get('/edubraille',          [AdminController::class, 'edubraille'])->name('edubraille');
    Route::post('/edubraille/save',    [AdminController::class, 'saveEdubraille'])->name('edubraille.save');
    Route::post('/edubraille/test',    [AdminController::class, 'testConnection'])->name('edubraille.test');
    Route::post('/edubraille/send',    [AdminController::class, 'sendChunk'])->name('edubraille.send');
    Route::post('/edubraille/delete',  [AdminController::class, 'deleteEdubrailleDevice'])->name('edubraille.delete');
    Route::post('/edubraille/active',  [AdminController::class, 'setActiveEdubrailleDevice'])->name('edubraille.active');

    /* Profil admin — view & route berbeda dari profil user */
    Route::get('/profile', function () {
        return view('admin.profile', ['user' => auth()->user()]);
    })->name('profile');
    Route::put('/profile',          [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.admin');
});