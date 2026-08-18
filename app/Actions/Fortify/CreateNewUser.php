<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        // ルールは screens.md 4章、上限値は spec §5-1。
        // エラー文言は lang/ja/validation.php にまとめてある。
        // パスワードの一致は password の confirmed ではなく password_confirmation の same にしている。
        // screens.md 4章が不一致の文言を「パスワード（確認用）」の行に置いていて、
        // エラーを確認用の欄の下に出す必要があるため。
        Validator::make($input, [
            'name' => ['required', 'string', 'max:30'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => ['required', 'string', 'min:8'],
            'password_confirmation' => ['required', 'string', 'same:password'],
        ])->validate();

        // User の 'password' => 'hashed' キャストがハッシュ化する。
        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);
    }
}
