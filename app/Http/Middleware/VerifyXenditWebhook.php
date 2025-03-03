<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyXenditWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        // Cek apakah request memiliki signature (opsional, tergantung Xendit)
        if (!$request->has('id') || !$request->has('external_id')) {
            return response()->json(['message' => 'Invalid webhook request'], 400);
        }

        return $next($request);
    }
}
