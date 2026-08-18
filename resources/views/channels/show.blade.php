{{-- SC-05 チャンネル（F-06）。項目は screens.md 3-5、見た目は mockup/channel-show.html。
     このイシューではチャンネルの見出しと編集・削除への導線まで。
     メッセージの一覧・投稿欄（F-12〜F-14）は実装単位(4)、返信リンクは実装単位(5)で入れる。 --}}
@extends('layouts.app')

@section('title', $channel->name)

{{-- メッセージ一覧を持つ画面はスクロールを main--scroll に任せない（mockup/channel-show.html） --}}
@section('main-class', 'main')

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
@endsection
