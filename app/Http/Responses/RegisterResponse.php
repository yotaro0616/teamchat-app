<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegisterResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        // Fortify は登録した利用者をそのままログインさせるが、
        // permissions-api.md 2章は「登録成功 → /login、自動ログインにしない」と決めている
        // （spec §3-1「登録し、そのあとはメールアドレスとパスワードでログインできれば十分」が根拠）。
        // 作成そのものは済んでいるので、ここでログイン状態だけ解く。
        Auth::guard(config('fortify.guard'))->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
