<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Http\Requests\UpdateMessageRequest;
use App\Models\Channel;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * メッセージ（F-12〜F-14 / SC-05）。一覧の描画（F-06）は ChannelController::show()。
 *
 * URL と HTTP メソッドは docs/design/permissions-api.md 2章のとおり。
 *
 * 判定は必ず次の順に行う（permissions-api.md 2章の補足 / behavior.md 3章「判定の順序」）。
 *   1. チャンネルが見えるか            → 見えなければ404
 *   2. そのメッセージがそのチャンネルのものか → 違えば404
 *   3. 投稿者本人か・まだ削除されていないか  → 違えば403（MessagePolicy）
 * 2 を落とすと、見えるチャンネルのURLの下に別チャンネル（プライベートを含む）のメッセージIDを
 * 差し込むだけで手が届いてしまう。UIでアイコンを隠したことを、これらの判定の代わりにしない。
 */
class MessageController extends Controller
{
    /**
     * メッセージを投稿する（F-12）。
     */
    public function store(StoreMessageRequest $request, Channel $channel): RedirectResponse
    {
        $this->ensureVisible($channel);

        // 返信（parent_message_id あり）の投稿は F-15＝実装単位(5)。ここでは本流だけを作る。
        $channel->messages()->create([
            'user_id' => auth()->id(),
            'parent_message_id' => null,
            'body' => $request->validated('body'),
        ]);

        return redirect()->route('channels.show', $channel);
    }

    /**
     * メッセージを編集する（F-13 の表示）。
     *
     * 専用の画面は作らず、同じチャンネル画面（SC-05）をその1件だけ編集状態にして描き直す
     * （permissions-api.md 2章の※設計判断。design-guide.md §4「編集中（メッセージ）」）。
     */
    public function edit(Channel $channel, Message $message): View|RedirectResponse
    {
        $this->ensureVisible($channel);
        $this->ensureBelongsTo($message, $channel);
        $this->authorize('update', $message);

        // 暫定: 返信はスレッド表示（SC-08）側を編集状態にする決めだが、その画面は実装単位(5)。
        // それまでは編集状態にせずチャンネル画面へ戻す（permissions-api.md 2章の補足）。
        if ($message->isReply()) {
            return redirect()->route('channels.show', $channel);
        }

        return view('channels.show', [
            'channel' => $channel->load('creator'),
            'messages' => $channel->messagesForDisplay(),
            'editingMessage' => $message,
        ]);
    }

    /**
     * メッセージを編集する（F-13）。
     *
     * 「編集済み」の判定に使うのは edited_at だけ。updated_at では判定しない（data.md 2-4）。
     */
    public function update(UpdateMessageRequest $request, Channel $channel, Message $message): RedirectResponse
    {
        $this->ensureVisible($channel);
        $this->ensureBelongsTo($message, $channel);
        $this->authorize('update', $message);

        $message->editBody($request->validated('body'));

        // 暫定: 返信なら本来は同じスレッドの表示へ戻す（実装単位(5)で差し替える）。
        return redirect()->route('channels.show', $channel);
    }

    /**
     * メッセージを削除する（F-14）。
     *
     * 論理削除。deleted_at を立てるだけで body は残し、画面には
     * 「このメッセージは削除されました」の枠として残り続ける（data.md 0章・2-4）。
     */
    public function destroy(Channel $channel, Message $message): RedirectResponse
    {
        $this->ensureVisible($channel);
        $this->ensureBelongsTo($message, $channel);
        $this->authorize('delete', $message);

        // delete() は物理削除になる（SoftDeletes を使っていない）。必ずこちらを通す。
        $message->markAsDeleted();

        return redirect()->route('channels.show', $channel);
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
}
