<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Models\Channel;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * スレッド（F-15 返信投稿 / F-16 スレッド表示 / SC-08）。
 *
 * URL と HTTP メソッドは docs/design/permissions-api.md 2章のとおり。
 * 返信は messages の自己参照（parent_message_id）で、別テーブルにはしない（data.md 1章）。
 *
 * 判定は MessageController と同じ順に行う（behavior.md 3章「判定の順序」）。
 *   1. チャンネルが見えるか                → 見えなければ404
 *   2. そのメッセージがそのチャンネルのものか → 違えば404
 *   3. その親が返信ではないか               → 返信なら404
 * 3 は「返信は1段まで」（questions.md Q-07）をアプリ側で担保するもの。返信はスレッドの親に
 * なれない＝そのURLは存在しない、という扱いで、403（権限が無い操作）にはしない（behavior.md 3章）。
 *
 * 返信の投稿にポリシーは使わない。見えるチャンネルなら誰でも返信でき、削除済みの元メッセージにも
 * 返信できる（behavior.md 1章 / questions.md「どのQにも当たらなかった回答」）。
 */
class ThreadController extends Controller
{
    /**
     * スレッドを開く（F-16 / SC-08）。
     *
     * SC-08 専用のビューは作らず、チャンネル画面（SC-05）に3列目のパネルを足して描く
     * （screens.md 3-8 の※設計判断）。
     * 元メッセージも返信も deleted_at を問わず出す。削除済みは枠として残る（data.md 3章 F-16行）。
     */
    public function show(Channel $channel, Message $message): View
    {
        $this->ensureVisible($channel);
        $this->ensureBelongsTo($message, $channel);
        $this->ensureNotReply($message);

        return view('channels.show', [
            'channel' => $channel->load('creator'),
            'messages' => $channel->messagesForDisplay(),
            'thread' => $message->load('user'),
            'replies' => $message->repliesForDisplay(),
        ]);
    }

    /**
     * 返信を投稿する（F-15）。
     *
     * channel_id は親と必ず一致させる（親と常に一致する意図的な冗長化。data.md 2-4）。
     */
    public function store(StoreMessageRequest $request, Channel $channel, Message $message): RedirectResponse
    {
        $this->ensureVisible($channel);
        $this->ensureBelongsTo($message, $channel);
        $this->ensureNotReply($message);

        $channel->messages()->create([
            'user_id' => auth()->id(),
            'parent_message_id' => $message->id,
            'body' => $request->validated('body'),
        ]);

        return redirect()->route('threads.show', [$channel, $message]);
    }

    /**
     * 自分がメンバーでないプライベートチャンネルは、存在しないIDと同じ404にする
     * （behavior.md 3章 / questions.md Q-04）。403にすると存在自体が漏れる。
     */
    private function ensureVisible(Channel $channel): void
    {
        abort_unless($channel->isVisibleTo(auth()->user()), 404);
    }

    /**
     * URL のチャンネルとメッセージが食い違っていたら、存在しないIDと同じ404にする。
     */
    private function ensureBelongsTo(Message $message, Channel $channel): void
    {
        abort_unless($message->channel_id === $channel->id, 404);
    }

    /**
     * 返信を親に指すURLは存在しない扱いにする（返信は1段まで。behavior.md 3章）。
     */
    private function ensureNotReply(Message $message): void
    {
        abort_if($message->isReply(), 404);
    }
}
