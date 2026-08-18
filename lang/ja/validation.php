<?php

/*
 * 画面に出すエラー文は screens.md 4章で決まっている。文言をここに集めて、
 * Fortify が内部で持っているフォームリクエスト（ログインの必須チェック）にも同じ文言を使わせる。
 *
 * design-guide.md §4 のとおり、エラー文は1文・句点なし。
 * ここに無いキーは fallback_locale（en）に落ちる。
 */
return [

    'required' => ':attributeを入力してください',
    'email' => ':attributeの形式が正しくありません',
    'min' => [
        'string' => ':attributeは:min文字以上で入力してください',
    ],
    'max' => [
        'string' => ':attributeは:max文字以内で入力してください',
    ],

    'custom' => [
        'email' => [
            'unique' => 'このメールアドレスはすでに登録されています',
        ],
        'password_confirmation' => [
            'same' => 'パスワードが一致しません',
        ],
    ],

    'attributes' => [
        'name' => '表示名',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード（確認用）',
    ],

];
