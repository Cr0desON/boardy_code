<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RefreshTokenCookie {
    public function handle(Request $request, Closure $next) {
        $response = $next($request);

        if ($request->is('oauth/token') && $response->isOk()) {
            $data = json_decode($response->getContent(), true);
            if (isset($data['refresh_token'])) {
                $refresh = $data['refresh_token'];
                unset($data['refresh_token']);
                $response->setContent(json_encode($data));

                $response->headers->setCookie(cookie(
                    'refresh_token', $refresh,
                    60 * 24 * 30,        // 30 дней в минутах
                    '/', null,
                    true,                // Secure
                    true,                // HttpOnly
                    false,
                    false,
                    'Strict'             // SameSite
                ));
            }
        }
        return $response;
    }
}

