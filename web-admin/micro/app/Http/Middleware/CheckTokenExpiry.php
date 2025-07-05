<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class CheckTokenExpiry
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
        {
            $expiry = Session::get('token_expires_at');

            if (!$expiry || Carbon::now()->gt($expiry)) {
                Session::flush();
                return redirect()
                    ->route('login')
                    ->with('error', 'Sesi telah habis, silakan login ulang.');
            }

            return $next($request);
        }
}
// check bootstrap/app.php
