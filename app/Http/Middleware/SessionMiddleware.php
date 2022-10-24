<?php

namespace App\Http\Middleware;

use App\Models\Session;
use Closure;
use Illuminate\Support\Facades\Auth;

class SessionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        $cookieName = "cognito-session-id";
        /** cookieから取得したセッションID */
        $sessionId = $request->cookie($cookieName);

        $session = Session::firstOrNew([
            "uuid" => $sessionId,
        ]);

        if (!$session->exists) {
            // セッション情報を更新
            $session
                ->fill([
                    "ip_address" => $request->ip(),
                    "user_agent" => $request->userAgent(),
                ])
                ->save();

            // CookieにID保存
            $addCookie = cookie()->forever($cookieName, $session->uuid, "/");
            cookie()->queue($addCookie);
        }

        return $next($request);
    }
}
