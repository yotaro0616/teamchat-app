{{-- ログイン後の画面の枠（screens.md 3章「共通レイアウト（ヘッダー・サイドバー）」）。
     見た目は mockup/channels.html ほか。検索窓は実装単位(6)で実フォームに差し替えた
     （screens.md 3-9 実装時追記）。
     サイドバーに出すチャンネルは AppServiceProvider の View Composer が渡す（$sidebarChannels）。 --}}
@php
    // いま開いているチャンネル（あれば）。サイドバーの選択中表示に使う。
    $currentChannel = request()->route('channel');
@endphp
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
<div class="app">
    <header class="topbar">
        <div class="topbar__brand"><span class="brandmark">チ</span>チームチャットアプリ</div>
        {{-- 上部バーは3列のグリッド。GET /search へのフォーム（screens.md 3-9 実装時追記）。
             q が空のまま送信すると「まだ検索していない」状態の画面に戻る。 --}}
        <form class="topbar__search search-input" method="GET" action="{{ route('search.index') }}">
            <svg class="ic" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="7" cy="7" r="4.5"/><path d="M10.5 10.5 14 14" stroke-linecap="round"/></svg>
            <input type="text" name="q" placeholder="メッセージを検索">
        </form>
        <div class="topbar__me">
            <span class="nm">{{ auth()->user()->name }}</span>
            {{-- ログアウトは POST /logout（permissions-api.md 2章）なのでリンクではなくフォームで送る --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">ログアウト</button>
            </form>
        </div>
    </header>
    {{-- 3列（サイドバー・本体・スレッドパネル）に切り替える画面だけが body-class を上書きする。
         既定は2列の app__body（mockup/channel-show.html）、SC-08 は app__body--thread を足す --}}
    <div class="@yield('body-class', 'app__body')">
        <nav class="sidebar">
            <div class="side-group">チャンネル</div>
            @foreach ($sidebarChannels as $sidebarChannel)
                <a class="side-item @if ($currentChannel && $currentChannel->id === $sidebarChannel->id) is-selected @endif"
                   href="{{ route('channels.show', $sidebarChannel) }}">
                    @include('channels.partials.sig', ['channel' => $sidebarChannel])
                    <span class="nm">{{ $sidebarChannel->name }}</span>
                </a>
            @endforeach
            <div class="side-foot"><a href="{{ route('channels.index') }}">チャンネル一覧を見る</a></div>
        </nav>
        <main class="@yield('main-class', 'main main--scroll')">
            @yield('content')
        </main>
        {{-- スレッド（SC-08）の3列目。パネルを出す画面だけが @push('aside') する（screens.md 3-8） --}}
        @stack('aside')
    </div>
</div>
</body>
</html>
