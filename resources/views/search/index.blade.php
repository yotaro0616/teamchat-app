{{-- SC-09 検索結果（F-17）。項目は screens.md 3-9、見た目は mockup/search.html。
     q が空／未指定のときはフォームだけを表示する「まだ検索していない」状態にする
     （screens.md 3-9 実装時追記）。 --}}
@extends('layouts.app')

@section('title', '検索結果')

@section('content')
    <div class="page">
        <h1 class="h-title">メッセージを検索</h1>

        <form method="GET" action="{{ route('search.index') }}">
            <div class="field" style="margin:var(--sp-5) 0 var(--sp-6);max-width:560px">
                <div class="inline-2">
                    <input type="text" name="q" class="input" value="{{ $keyword }}"
                           id="search-q" data-counter-submit="search-submit">
                    {{-- 空欄のあいだは押せない状態にする（screens.md 4章）。JSを無効にしていても
                         サーバ側の初期値で塞がる（blade.md「JSを無効にされても壊れないようにする」） --}}
                    <button type="submit" class="btn btn--primary" id="search-submit" @disabled($keyword === '')>検索</button>
                </div>
            </div>
        </form>

        @if ($keyword !== '')
            <p class="t-caption" style="margin-bottom:var(--sp-3)">「{{ $keyword }}」の検索結果　{{ $results->count() }}件</p>

            @if ($results->isEmpty())
                <div class="empty">
                    <div class="empty__title">「{{ $keyword }}」に一致するメッセージはありません</div>
                    <div class="empty__text">別のキーワードで試すか、キーワードを短くしてください。</div>
                </div>
            @else
                <div class="rows">
                    @foreach ($results as $message)
                        <div class="row">
                            <div class="row__main">
                                <div class="result__meta">
                                    {{-- sig パーシャルは使わない。search.html の実物どおり、記号と
                                         チャンネル名を1つの span にまとめて書く（screens.md 3-9 実装時追記） --}}
                                    <span class="result__ch">
                                        @if ($message->channel->isPublic())
                                            # {{ $message->channel->name }}
                                        @else
                                            <svg class="ic" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3.25" y="7" width="9.5" height="6.5" rx="1.5"/><path d="M5.5 7V5a2.5 2.5 0 0 1 5 0v2"/></svg> {{ $message->channel->name }}
                                        @endif
                                    </span>
                                    <span>·</span>
                                    <span>{{ $message->user->name }}</span>
                                    <span>·</span>
                                    {{-- 日時の表記は design-guide.md §2 で 2026/08/17 14:32 に固定 --}}
                                    <span>{{ $message->created_at->format('Y/m/d H:i') }}</span>
                                </div>
                                {{-- 一致箇所を mark で強調表示する（screens.md 3-9） --}}
                                <div class="t-body">{!! $message->highlightedBody($keyword) !!}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
@endsection
