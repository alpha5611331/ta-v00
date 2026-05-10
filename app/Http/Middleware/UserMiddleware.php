<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserMiddleware
{
    /**
     * Blokir admin dari mengakses halaman user.
     * Admin harus pakai akun user terpisah untuk test.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (Auth::user()->is_admin) {
            return redirect()->route('admin.index')
                ->with('error', 'Admin tidak dapat mengakses halaman pengguna. Gunakan akun pengguna untuk mengakses fitur ini.');
        }

        return $next($request);
    }
}