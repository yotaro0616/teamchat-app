{{-- ログイン後の画面の枠。mockup/channels.html の上部バーまで。
     サイドバーはチャンネルの一覧が要るので実装単位(2)、検索窓は実装単位(6)で入れる。 --}}
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=1280">
    <title>@yield('title')｜チームチャットアプリ</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="app">
    <header class="topbar">
        <div class="topbar__brand"><span class="brandmark">チ</span>チームチャットアプリ</div>
        {{-- 上部バーは3列のグリッド。検索窓を入れるまで、列を保つために枠だけ置く --}}
        <div class="topbar__search"></div>
        <div class="topbar__me">
            <span class="nm">{{ auth()->user()->name }}</span>
            {{-- ログアウトは POST /logout（permissions-api.md 2章）なのでリンクではなくフォームで送る --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">ログアウト</button>
            </form>
        </div>
    </header>
    <main class="main main--scroll">
        @yield('content')
    </main>
</div>
</body>
</html>
