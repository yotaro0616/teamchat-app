<?php

namespace App\Actions\Fortify;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\AttemptToAuthenticate as FortifyAttemptToAuthenticate;

class AttemptToAuthenticate extends FortifyAttemptToAuthenticate
{
    /**
     * Throw a failed authentication validation exception.
     *
     * @param  Request  $request
     * @return void
     *
     * @throws ValidationException
     */
    protected function throwFailedAuthenticationException($request)
    {
        $this->limiter->increment($request);

        // Fortify 既定は 'email' キーに付けるが、screens.md 3-1 が
        // 「パスワード欄を赤枠にして」と決めているので 'password' キーで返す。
        // メールとパスワードのどちらが誤りかは教えない（文言も同節のとおり）。
        throw ValidationException::withMessages([
            'password' => [trans('auth.failed')],
        ]);
    }
}
