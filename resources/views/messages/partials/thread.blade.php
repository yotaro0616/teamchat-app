{{-- SC-08 スレッド（F-15・F-16）。項目は screens.md 3-8、見た目は mockup/thread.html。
     SC-08 専用のビューは作らず、SC-05 のビューが $thread を受け取ったときだけこのパネルを足す
     （screens.md 3-8「画面の作り方について」）。
     $thread = 元メッセージ、$replies = その返信（古い順・削除済みも含む）。 --}}
<aside class="thread">
    <div class="thread__head">
        <div>
            <div class="t-label">スレッド</div>
            <div class="t-caption">@include('channels.partials.sig', ['channel' => $channel]){{ $channel->name }}</div>
        </div>
        {{-- 閉じるはサーバへ送るものが無いのでリンクにする（screens.md 3-8「閉じる（×）について」）。
             JSで開閉しない --}}
        <a class="icon-btn" title="閉じる" href="{{ route('channels.show', $channel) }}">
            <svg class="ic" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M4 4l8 8M12 4l-8 8"/></svg>
        </a>
    </div>
    <div class="thread__body">
        {{-- 元メッセージはパネル上部に固定。削除済みでも枠として残る（data.md 3章 F-16行） --}}
        <div class="thread__origin">
            @include('messages.partials.message', ['message' => $thread, 'channel' => $channel, 'inThread' => true])
        </div>
        {{-- 0件でも「返信0件」と出す（screens.md 3-8「返信が0件のときの見せ方について」） --}}
        <div class="thread__count">返信{{ $replies->count() }}件</div>
        @foreach ($replies as $reply)
            @include('messages.partials.message', ['message' => $reply, 'channel' => $channel, 'inThread' => true])
        @endforeach
    </div>

    {{-- 返信の投稿欄（F-15）。空欄・超過のあいだ「送信」は押せない（screens.md 4章）。
         返信への返信はできないので、この欄が作るのは常に $thread への返信（questions.md Q-07） --}}
    @php
        $replyBody = old('body', '');
        $replyLength = mb_strlen($replyBody);
    @endphp
    <form class="composer" method="POST" action="{{ route('replies.store', [$channel, $thread]) }}">
        @csrf
        <div class="composer__box">
            <textarea class="composer__ta" name="body" id="reply-body"
                      placeholder="返信を送る"
                      data-counter="reply-count" data-counter-max="1000"
                      data-counter-unit=" 文字" data-counter-submit="reply-submit">{{ $replyBody }}</textarea>
            <div class="composer__foot">
                <span class="count" id="reply-count">{{ number_format($replyLength) }} / 1,000 文字</span>
                <button class="btn btn--primary btn--sm" type="submit"
                        id="reply-submit" @disabled($replyLength === 0 || $replyLength > 1000)>送信</button>
            </div>
        </div>
    </form>
</aside>
