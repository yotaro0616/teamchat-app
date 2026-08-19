{{-- SC-07 メンバー管理（F-09〜F-11）。項目は screens.md 3-7、見た目は mockup/members.html。
     この画面が開けるのはプライベートチャンネルの作成者だけ（permissions-api.md 1章）。
     JSは使わない（文字数上限のある入力欄も、押せない状態にする送信ボタンも無い。screens.md 4章）。 --}}
@extends('layouts.app')

@section('title', 'メンバー管理')

@section('content')
    <div class="page">
        <div class="page__head">
            <div>
                {{-- 見出しの鍵アイコンは mockup/members.html の h1 の形をそのまま使う
                     （行やサイドバーの記号を包む channels.partials.sig とは別の置き方） --}}
                <h1 class="h-title" style="display:flex;align-items:center;gap:8px">
                    <svg class="ic" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3.25" y="7" width="9.5" height="6.5" rx="1.5"/><path d="M5.5 7V5a2.5 2.5 0 0 1 5 0v2"/></svg>{{ $channel->name }}
                    @include('channels.partials.badge', ['channel' => $channel])
                </h1>
                <p class="t-caption" style="margin-top:4px">{{ $channel->description }}　·　追加された人だけが読み書きできます。</p>
            </div>
            <a class="btn btn--sm" href="{{ route('channels.show', $channel) }}">チャンネルを開く</a>
        </div>

        <div class="card" style="margin-bottom:var(--sp-6)"><div class="card__body">
            <h2 class="h-heading" style="margin-bottom:var(--sp-3)">メンバーを追加する</h2>
            <form method="POST" action="{{ route('members.store', $channel) }}">
                @csrf
                {{-- エラー時の見せ方はモックアップに実例が無いので、他のフォーム（SC-04）と同じ
                     field + err の型に合わせる（screens.md 4章のエラー文をそのまま出す） --}}
                <div class="field @error('key') is-error @enderror" style="margin-bottom:0">
                    <div class="inline-2" style="max-width:520px">
                        <input class="input" type="text" name="key" value="{{ old('key') }}"
                               placeholder="表示名またはメールアドレスで探す">
                        <button class="btn btn--primary" type="submit">
                            <svg class="ic" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M8 3.5v9M3.5 8h9"/></svg>追加
                        </button>
                    </div>
                    @error('key')
                        <span class="err">{{ $message }}</span>
                    @enderror
                </div>
            </form>
        </div></div>

        <h2 class="h-heading" style="margin-bottom:var(--sp-3)">メンバー　{{ $members->count() }}人</h2>

        <div class="rows">
            @foreach ($members as $member)
                <div class="row">
                    @include('messages.partials.avatar', ['user' => $member])
                    <div class="row__main">
                        <div class="row__title">
                            {{ $member->name }}
                            @if ($channel->isCreatedBy($member))
                                <span class="badge badge--owner">作成者</span>
                            @endif
                        </div>
                        <div class="row__sub">{{ $member->email }}</div>
                    </div>
                    <div class="row__acts">
                        @if ($channel->isCreatedBy($member))
                            {{-- 作成者はメンバーから外れられない（questions.md）。
                                 押せないボタンには危険色を付けない（mockup/members.html）。
                                 サーバ側でも同じ判定を再確認する（behavior.md 3章） --}}
                            <span class="t-caption">作成者は外せません</span>
                            <button class="btn btn--sm" type="button" disabled>外す</button>
                        @else
                            <form method="POST" action="{{ route('members.destroy', [$channel, $member]) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn--sm btn--danger" type="submit">外す</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
