{{-- SC-03 チャンネル一覧（F-04）。項目は screens.md 3-3、見た目は mockup/channels.html。 --}}
@extends('layouts.app')

@section('title', 'チャンネル一覧')

@section('content')
    <div class="page">
        <div class="page__head">
            <div>
                <h1 class="h-title">チャンネル一覧</h1>
                <p class="t-caption" style="margin-top:4px">公開チャンネルは、ログインしている社員なら誰でも読み書きできます。</p>
            </div>
            <a class="btn btn--primary" href="{{ route('channels.create') }}">
                <svg class="ic" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M8 3.5v9M3.5 8h9"/></svg>チャンネルを作る
            </a>
        </div>

        @if ($channels->isEmpty())
            {{-- 0件の表示は design-guide.md §4 の空状態の型（screens.md 3-3） --}}
            <div class="empty">
                <div class="empty__title">見られるチャンネルがありません</div>
                <div class="empty__text">「チャンネルを作る」から、最初のチャンネルを作ってください。</div>
            </div>
        @else
            <div class="rows">
                @foreach ($channels as $channel)
                    <div class="row">
                        <div class="row__main">
                            <div class="row__title">
                                @include('channels.partials.sig', ['channel' => $channel]){{ $channel->name }}
                                @include('channels.partials.badge', ['channel' => $channel])
                            </div>
                            <div class="row__sub">{{ $channel->description }}　·　作成者：{{ $channel->creator->name }}</div>
                        </div>
                        <div class="row__acts">
                            {{-- 「メンバー」はプライベートチャンネルの作成者にだけ（screens.md 3-3）。
                                 公開チャンネルにはメンバー管理そのものが無い（data.md 2-3） --}}
                            @if (! $channel->isPublic() && $channel->isCreatedBy(auth()->user()))
                                <a class="btn btn--sm" href="{{ route('members.index', $channel) }}">メンバー</a>
                            @endif
                            {{-- 「編集」は作成者にだけ（screens.md 3-3）。サーバ側でも作成者を再確認する --}}
                            @if ($channel->isCreatedBy(auth()->user()))
                                <a class="btn btn--sm" href="{{ route('channels.edit', $channel) }}">編集</a>
                            @endif
                            <a class="btn btn--sm" href="{{ route('channels.show', $channel) }}">開く</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
