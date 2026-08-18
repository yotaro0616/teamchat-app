{{-- 未ログイン専用の画面（SC-01 ログイン / SC-02 新規登録）の枠。mockup/login.html・register.html --}}
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=1280">
    <title>@yield('title')｜チームチャットアプリ</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('scripts')
</head>
<body>
<div class="auth">
    <div class="auth__card">
        <div class="auth__brand"><span class="brandmark">チ</span>チームチャットアプリ</div>
        @yield('content')
    </div>
</div>
</body>
</html>
