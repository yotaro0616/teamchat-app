<?php

use App\Actions\Fortify\AttemptToAuthenticate;
use App\Providers\RouteServiceProvider;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Features;

return [

    /*
    |--------------------------------------------------------------------------
    | Fortify Guard
    |--------------------------------------------------------------------------
    |
    | Here you may specify which authentication guard Fortify will use while
    | authenticating users. This value should correspond with one of your
    | guards that is already present in your "auth" configuration file.
    |
    */

    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Fortify Password Broker
    |--------------------------------------------------------------------------
    |
    | Here you may specify which password broker Fortify can use when a user
    | is resetting their password. This configured value should match one
    | of your password brokers setup in your "auth" configuration file.
    |
    */

    'passwords' => 'users',

    /*
    |--------------------------------------------------------------------------
    | Username / Email
    |--------------------------------------------------------------------------
    |
    | This value defines which model attribute should be considered as your
    | application's "username" field. Typically, this might be the email
    | address of the users but you are free to change this value here.
    |
    | Out of the box, Fortify expects forgot password and reset password
    | requests to have a field named 'email'. If the application uses
    | another name for the field you may define it below as needed.
    |
    */

    'username' => 'email',

    'email' => 'email',

    /*
    |--------------------------------------------------------------------------
    | Lowercase Usernames
    |--------------------------------------------------------------------------
    |
    | This value defines whether usernames should be lowercased before saving
    | them in the database, as some database system string fields are case
    | sensitive. You may disable this for your application if necessary.
    |
    */

    'lowercase_usernames' => true,

    /*
    |--------------------------------------------------------------------------
    | Home Path
    |--------------------------------------------------------------------------
    |
    | Here you may configure the path where users will get redirected during
    | authentication or password reset when the operations are successful
    | and the user is authenticated. You are free to change this value.
    |
    */

    'home' => RouteServiceProvider::HOME,

    /*
    |--------------------------------------------------------------------------
    | Fortify Routes Prefix / Subdomain
    |--------------------------------------------------------------------------
    |
    | Here you may specify which prefix Fortify will assign to all the routes
    | that it registers with the application. If necessary, you may change
    | subdomain under which all of the Fortify routes will be available.
    |
    */

    'prefix' => '',

    'domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Fortify Routes Middleware
    |--------------------------------------------------------------------------
    |
    | Here you may specify which middleware Fortify will assign to the routes
    | that it registers with the application. If necessary, you may change
    | these middleware but typically this provided default is preferred.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | By default, Fortify will throttle logins to five requests per minute for
    | every email and IP address combination. However, if you would like to
    | specify a custom rate limiter to call then you may specify it here.
    |
    */

    'limiters' => [
        'login' => 'login',
        'two-factor' => 'two-factor',
    ],

    /*
    |--------------------------------------------------------------------------
    | Register View Routes
    |--------------------------------------------------------------------------
    |
    | Here you may specify if the routes returning views should be disabled as
    | you may not need them when building your own application. This may be
    | especially true if you're writing a custom single-page application.
    |
    */

    'views' => true,

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Some of the Fortify features are optional. You may disable the features
    | by removing them from this array. You're free to only remove some of
    | these features or you can even remove all of these if you need to.
    |
    */

    'features' => [
        // 新規登録（F-01）だけを使う。ログイン（F-02）とログアウト（F-03）は
        // Fortify が features に関係なく登録するので、ここには並ばない。
        //
        // 外したもの:
        //   resetPasswords / emailVerification
        //     … questions.md Q-01 が回答待ちのため、features.md 2-2 の提案（作らない）を暫定採用
        //   updateProfileInformation / updatePasswords
        //     … questions.md Q-02「今回は要りません」で確定
        //   twoFactorAuthentication
        //     … spec §3-1 に記載が無く、data.md 2-1 の users にも対応する列を置いていない
        Features::registration(),
    ],

    /*
    |--------------------------------------------------------------------------
    | Redirects
    |--------------------------------------------------------------------------
    |
    | 遷移先は permissions-api.md 2章で決まっている。login は 'home'（/channels）に
    | 落ちるのでそのまま。logout は Fortify 既定が '/' なので上書きする。
    | register は自動ログインを打ち消す必要があるため、config ではなく
    | App\Http\Responses\RegisterResponse が担当する。
    |
    */

    'redirects' => [
        'logout' => '/login',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pipelines
    |--------------------------------------------------------------------------
    |
    | Fortify 既定のログイン処理は、認証に失敗したとき 'email' キーにエラーを付ける。
    | screens.md 3-1 は「パスワード欄を赤枠にして」と決めているので、
    | AttemptToAuthenticate だけ差し替えて 'password' キーで返す。
    |
    | 既定との違いはそこだけ。EnsureLoginIsNotThrottled が並んでいないのは
    | 上の limiters.login を設定してあるとき Fortify 自身も外すためで
    | （試行回数の制限はルートの throttle ミドルウェアが受け持つ）、
    | RedirectIfTwoFactorAuthenticatable が無いのは二要素認証を使わないため。
    |
    */

    'pipelines' => [
        'login' => [
            CanonicalizeUsername::class,
            AttemptToAuthenticate::class,
            PrepareAuthenticatedSession::class,
        ],
    ],

];
