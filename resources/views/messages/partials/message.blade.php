{{-- メッセージ1件（SC-05 / F-06）。項目は screens.md 3-5、見た目は mockup/channel-show.html。
     状態は4つ: 通常／編集済み／削除済み／編集中。
     スレッドパネル（SC-08）の中から呼ばれるときは $inThread に true を渡す。 --}}
@php
    $isEditing = isset($editingMessage) && $editingMessage->id === $message->id;
    // 編集・削除は自分の投稿にだけ。ただし削除済みには出さない（screens.md 3-5 / behavior.md 1章）。
    // UIで隠すことはサーバ側の判定の代わりにしない（MessagePolicy で必ず再確認する）。
    $canEdit = $message->isPostedBy(auth()->user()) && ! $message->isDeleted();
    // パネルの中（SC-08）では返信の導線を出さない。返信への返信はできない（questions.md Q-07 /
    // screens.md 3-8「パネルの中のホバー操作について」）。既定はチャンネル画面（SC-05）。
    $inThread = $inThread ?? false;
    // 「返信」アイコンは削除済みを含む全メッセージに出す（screens.md 3-5）。返信が0件の
    // メッセージからスレッドを開ける唯一の導線でもある。
    $canReply = ! $inThread;
    // 「返信N件」リンクは1件以上のときだけ（mockup/channel-show.html に0件の見本は無い）。
    // 件数は messagesForDisplay() の withCount('replies') が入れる（data.md 2-4）。
    $replyCount = $inThread ? 0 : ($message->replies_count ?? 0);
    // 編集中は本文（msg__body）も削除済みの枠（msg__deleted-note）も出ないので、その直後に置く
    // 「返信N件」リンクも出さない（mockup/channel-show.html でリンクはこの2つの直後にしか無い）。
    $showThreadLink = $replyCount > 0 && ! $isEditing;
    // 編集をやめたときの戻り先。返信はスレッド表示（SC-08）へ、元メッセージはチャンネル画面へ
    // （permissions-api.md 2章「上の暫定を解消した」）。
    $cancelUrl = $message->isReply()
        ? route('threads.show', [$channel, $message->parent_message_id])
        : route('channels.show', $channel);
@endphp
<article class="msg @if ($message->isDeleted()) msg--deleted @endif">
    {{-- 出すアイコンが1つも無いときは枠自体を描かない（screens.md 3-8 の※設計判断） --}}
    @if ($canReply || ($canEdit && ! $isEditing))
        <div class="msg__acts">
            @if ($canReply)
                <a class="icon-btn" title="返信" href="{{ route('threads.show', [$channel, $message]) }}">
                    <svg class="ic" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"><path d="M2.5 3.5h11v7h-6l-3 2.5v-2.5h-2z"/></svg>
                </a>
            @endif
            @if ($canEdit && ! $isEditing)
                <a class="icon-btn" title="編集" href="{{ route('messages.edit', [$channel, $message]) }}">
                    <svg class="ic" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"><path d="M11 2.6 13.4 5 5.4 13H3v-2.4z"/></svg>
                </a>
                {{-- 削除は DELETE。素のHTMLフォームは送れないのでメソッド偽装で送る（permissions-api.md 2章） --}}
                <form method="POST" action="{{ route('messages.destroy', [$channel, $message]) }}">
                    @csrf
                    @method('DELETE')
                    <button class="icon-btn" type="submit" title="削除">
                        <svg class="ic" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M3 4.5h10M6.5 4.5V3h3v1.5M4.5 4.5l.6 8.5h5.8l.6-8.5"/></svg>
                    </button>
                </form>
            @endif
        </div>
    @endif

    @include('messages.partials.avatar', ['user' => $message->user])

    <div>
        <div class="msg__head">
            <span class="msg__name">{{ $message->user->name }}</span>
            {{-- 日時の表記は design-guide.md §2 で 2026/08/17 14:32 に固定 --}}
            <span class="msg__time">{{ $message->created_at->format('Y/m/d H:i') }}</span>
        </div>

        @if ($message->isDeleted())
            {{-- 本文は出さず枠だけ残す。投稿者名と日時は残す（design-guide.md §4） --}}
            <div class="msg__deleted-note">このメッセージは削除されました</div>
        @elseif ($isEditing)
            {{-- 編集中（screens.md 3-5 の追記 / design-guide.md §4「編集中（メッセージ）」） --}}
            <form class="msg__edit" method="POST" action="{{ route('messages.update', [$channel, $message]) }}">
                @csrf
                @method('PATCH')
                @php
                    $editBody = old('body', $message->body);
                    $editLength = mb_strlen($editBody);
                @endphp
                <textarea class="textarea" name="body"
                          id="edit-body-{{ $message->id }}"
                          data-counter="edit-count-{{ $message->id }}" data-counter-max="1000"
                          data-counter-unit=" 文字" data-counter-submit="edit-submit-{{ $message->id }}">{{ $editBody }}</textarea>
                <div class="msg__edit-foot">
                    <span class="count" id="edit-count-{{ $message->id }}">{{ number_format($editLength) }} / 1,000 文字</span>
                    <div class="msg__edit-acts">
                        <a class="btn btn--sm" href="{{ $cancelUrl }}">やめる</a>
                        {{-- 空欄・超過のあいだは押せない状態にする（screens.md 4章）。
                             JSを無効にしていてもサーバ側の入力チェックがはじく --}}
                        <button class="btn btn--primary btn--sm" type="submit"
                                id="edit-submit-{{ $message->id }}" @disabled($editLength === 0 || $editLength > 1000)>保存</button>
                    </div>
                </div>
            </form>
        @else
            <div class="msg__body">{{ $message->body }}@if ($message->isEdited())<span class="msg__edited">編集済み</span>@endif</div>
        @endif

        {{-- 「返信N件」でスレッド（SC-08）を開く。本文／削除済みの枠の直後、同じ div の中
             （mockup/channel-show.html）。削除済みメッセージにも出す --}}
        @if ($showThreadLink)
            <a class="thread-link" href="{{ route('threads.show', [$channel, $message]) }}">
                <svg class="ic" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"><path d="M2.5 3.5h11v7h-6l-3 2.5v-2.5h-2z"/></svg>返信{{ $replyCount }}件
            </a>
        @endif
    </div>
</article>
