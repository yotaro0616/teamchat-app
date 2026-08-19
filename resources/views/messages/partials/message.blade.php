{{-- メッセージ1件（SC-05 / F-06）。項目は screens.md 3-5、見た目は mockup/channel-show.html。
     状態は4つ: 通常／編集済み／削除済み／編集中。
     「返信」アイコンと「返信N件」リンクは実装単位(5)で入れる。 --}}
@php
    $isEditing = isset($editingMessage) && $editingMessage->id === $message->id;
    // 編集・削除は自分の投稿にだけ。ただし削除済みには出さない（screens.md 3-5 / behavior.md 1章）。
    // UIで隠すことはサーバ側の判定の代わりにしない（MessagePolicy で必ず再確認する）。
    $canEdit = $message->isPostedBy(auth()->user()) && ! $message->isDeleted();
@endphp
<article class="msg @if ($message->isDeleted()) msg--deleted @endif">
    @if ($canEdit && ! $isEditing)
        <div class="msg__acts">
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
                        <a class="btn btn--sm" href="{{ route('channels.show', $channel) }}">やめる</a>
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
    </div>
</article>
