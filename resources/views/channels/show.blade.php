{{-- SC-05 チャンネル（F-06・F-12〜F-14）。項目は screens.md 3-5、見た目は mockup/channel-show.html。
     $thread が渡されたときは SC-08 スレッドとして、右に3列目のパネルを足して描く
     （screens.md 3-8「画面の作り方について」）。SC-08 専用のビューは作らない。 --}}
@extends('layouts.app')

@section('title', $channel->name)

{{-- メッセージ一覧を持つ画面はスクロールを main--scroll に任せない（mockup/channel-show.html） --}}
@section('main-class', 'main')

{{-- スレッドを開いているあいだだけ3列にする（mockup/thread.html の app__body--thread） --}}
@section('body-class', isset($thread) ? 'app__body app__body--thread' : 'app__body')

@if (isset($thread))
    @push('aside')
        @include('messages.partials.thread', ['channel' => $channel, 'thread' => $thread, 'replies' => $replies])
    @endpush
@endif

@push('scripts')
    <script src="{{ asset('js/app.js') }}" defer></script>
@endpush

@section('content')
    <div class="ch-head">
        <div class="ch-head__main">
            <div class="ch-head__name">@include('channels.partials.sig', ['channel' => $channel]){{ $channel->name }}</div>
            @include('channels.partials.badge', ['channel' => $channel])
            <span class="ch-head__desc">{{ $channel->description }}</span>
        </div>
        {{-- 「編集」「削除」は作成者にだけ（screens.md 3-5）。どちらも SC-06 へ送る。
             削除の確認は SC-06 の中にある（permissions-api.md 2章） --}}
        @if ($channel->isCreatedBy(auth()->user()))
            <div class="ch-head__acts">
                <a class="btn btn--sm" href="{{ route('channels.edit', $channel) }}">
                    <svg class="ic" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"><path d="M11 2.6 13.4 5 5.4 13H3v-2.4z"/></svg>編集
                </a>
                <a class="btn btn--sm btn--danger" href="{{ route('channels.edit', $channel) }}#delete-confirm">
                    <svg class="ic" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M3 4.5h10M6.5 4.5V3h3v1.5M4.5 4.5l.6 8.5h5.8l.6-8.5"/></svg>削除
                </a>
            </div>
        @endif
    </div>

    {{-- 並びは古いものが上・新しいものが下。削除済みも枠として残す（data.md 3章 F-06行） --}}
    <div class="msg-list">
        @php
            $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
            $previousDate = null;
        @endphp
        @foreach ($messages as $message)
            @if ($previousDate !== $message->created_at->toDateString())
                {{-- 日付区切り「2026年8月17日（月）」（screens.md 3-5 のモックアップ実例）。
                     曜日は環境の言語設定に左右されないよう配列から引く --}}
                <div class="day-sep"><span>{{ $message->created_at->format('Y年n月j日') }}（{{ $weekdays[$message->created_at->dayOfWeek] }}）</span></div>
                @php $previousDate = $message->created_at->toDateString(); @endphp
            @endif
            @include('messages.partials.message', ['message' => $message, 'channel' => $channel])
        @endforeach
    </div>

    {{-- 投稿欄（F-12）。空欄・超過のあいだ「送信」は押せない（screens.md 4章）。
         JSを無効にしていても disabled のままで、サーバ側の入力チェックも同じ上限ではじく --}}
    @php
        $composerBody = old('body', '');
        $composerLength = mb_strlen($composerBody);
    @endphp
    <form class="composer" method="POST" action="{{ route('messages.store', $channel) }}">
        @csrf
        <div class="composer__box">
            <textarea class="composer__ta" name="body" id="composer-body"
                      placeholder="#{{ $channel->name }} にメッセージを送る"
                      data-counter="composer-count" data-counter-max="1000"
                      data-counter-unit=" 文字" data-counter-submit="composer-submit">{{ $composerBody }}</textarea>
            <div class="composer__foot">
                <span class="count" id="composer-count">{{ number_format($composerLength) }} / 1,000 文字</span>
                <button class="btn btn--primary btn--sm" type="submit"
                        id="composer-submit" @disabled($composerLength === 0 || $composerLength > 1000)>送信</button>
            </div>
        </div>
    </form>
@endsection
