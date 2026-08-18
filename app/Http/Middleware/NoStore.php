<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NoStore
{
    /**
     * ログインが必要な画面をブラウザに保存させない。
     *
     * behavior.md 3章。no-store が無いとブラウザの「戻る」が
     * 認証ミドルウェアを通さずにページを復元してしまい、
     * ログアウト後もチャンネル画面が見えてしまう（acceptance.md TP-1-10）。
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');

        return $response;
    }
}
