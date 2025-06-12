<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceInlineAudio
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if ($request->is('sounds/*')) {
            $response->header('Content-Disposition', 'inline');
            $response->header('Content-Type', 'audio/mpeg');
        }
        return $response;
    }
}