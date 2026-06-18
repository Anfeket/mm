<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyDiscordSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('X-Signature-Ed25519');
        $timestamp = $request->header('X-Signature-Timestamp');
        $publicKey = config('services.discord.public_key');

        if (!$signature || !$timestamp) {
            abort(401, 'Missing signature or timestamp');
        }

        try {
            $valid = sodium_crypto_sign_verify_detached(
                hex2bin($signature),
                $timestamp . $request->getContent(),
                hex2bin($publicKey)
            );
        } catch (\Exception $e) {
            abort(401, 'Invalid signature format');
        }

        abort_unless($valid, 401, 'Invalid signature');

        return $next($request);
    }
}
