<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IncreaseExecutionTime
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        set_time_limit(120);  // Menambah waktu eksekusi menjadi 120 detik
        return $next($request);
    }
}