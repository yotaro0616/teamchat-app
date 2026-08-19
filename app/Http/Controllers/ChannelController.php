<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChannelRequest;
use App\Http\Requests\UpdateChannelRequest;
use App\Models\Channel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * チャンネル（F-04〜F-08 / SC-03〜SC-06）。
 *
 * URL と HTTP メソッドは docs/design/permissions-api.md 2章のとおり。
 *
 * 権限の判定は必ず「見えるか（見えなければ404）→ 作成者か（違えば403）」の順に行う。
 * 逆にすると、自分に見えないプライベートチャンネルに403が返り、存在が漏れる
 * （behavior.md 3章「判定の順序」）。UIでボタンを隠したことを判定の代わりにしない。
 */
class ChannelController extends Controller
{
    /**
     * チャンネル一覧（SC-03 / F-04）。
     */
    public function index(): View
    {
        $channels = Channel::visibleTo(auth()->user())
            ->with('creator')
            ->orderBy('id')
            ->get();

        return view('channels.index', ['channels' => $channels]);
    }

    /**
     * チャンネルを作る（SC-04 / F-05 の表示）。
     */
    public function create(): View
    {
        return view('channels.create');
    }

    /**
     * チャンネルを作る（F-05）。
     */
    public function store(StoreChannelRequest $request): RedirectResponse
    {
        $channel = Channel::create($request->safe()->only(['name', 'description', 'type'])
            + ['created_by' => auth()->id()]);

        // 作った人はそのチャンネルのメンバーになる。公開・プライベートを問わない
        // （spec §3-2 / data.md 2-3）。channel_user は updated_at を持たないので created_at を明示する。
        $channel->members()->attach(auth()->id(), ['created_at' => now()]);

        return redirect()->route('channels.show', $channel);
    }

    /**
     * チャンネル（SC-05 / F-06）。
     *
     * メッセージは deleted_at を問わず全件、古い順（data.md 3章 F-06行）。
     * 削除済みを外すのは検索（F-17）と公開API（F-19）だけで、ここでは外さない。
     */
    public function show(Channel $channel): View
    {
        $this->ensureVisible($channel);

        return view('channels.show', [
            'channel' => $channel->load('creator'),
            'messages' => $channel->messagesForDisplay(),
        ]);
    }

    /**
     * チャンネルを編集する（SC-06 / F-07 の表示）。削除確認カードも同じページに描く。
     */
    public function edit(Channel $channel): View
    {
        $this->ensureVisible($channel);
        $this->authorize('update', $channel);

        return view('channels.edit', ['channel' => $channel]);
    }

    /**
     * チャンネルを編集する（F-07）。
     *
     * 公開範囲（type）は受け取らない。作成後に変更できない（screens.md 3-6 / data.md 2-2）。
     */
    public function update(UpdateChannelRequest $request, Channel $channel): RedirectResponse
    {
        $this->ensureVisible($channel);
        $this->authorize('update', $channel);

        $channel->update($request->safe()->only(['name', 'description']));

        return redirect()->route('channels.show', $channel);
    }

    /**
     * チャンネルを削除する（F-08）。物理削除で channel_user を道連れにする（data.md 2-2）。
     */
    public function destroy(Request $request, Channel $channel): RedirectResponse
    {
        $this->ensureVisible($channel);
        $this->authorize('delete', $channel);

        // 確認入力の一致は、JSでボタンを非活性にするだけでなくサーバ側でも必ず再検証する
        // （behavior.md 3章）。一致しないときはエラー文を出さず編集画面へ戻すだけにする
        // ——この欄は「エラー文は出さず、ボタンを押せない状態で表現する」と決まっているため
        // （screens.md 4章 / permissions-api.md 2章の補足）。
        if ($request->input('name') !== $channel->name) {
            return redirect()->route('channels.edit', $channel);
        }

        $channel->delete();

        return redirect()->route('channels.index');
    }

    /**
     * 自分がメンバーでないプライベートチャンネルは、存在しないIDと同じ404にする
     * （behavior.md 3章 / questions.md Q-04）。403にすると存在自体が漏れる。
     */
    private function ensureVisible(Channel $channel): void
    {
        abort_unless($channel->isVisibleTo(auth()->user()), 404);
    }
}
