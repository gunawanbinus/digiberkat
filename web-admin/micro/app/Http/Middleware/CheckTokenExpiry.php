<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Session;

class CheckTokenExpiry
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if (LoginController::isTokenExpired()) {
            return redirect()->route('login')->withErrors('Sesi Anda telah habis, silakan login ulang.');
        }

        return $next($request);
    }
}
