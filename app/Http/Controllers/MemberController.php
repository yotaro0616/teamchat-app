<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * メンバー管理（F-09〜F-11 / SC-07）。
 *
 * URL と HTTP メソッドは docs/design/permissions-api.md 2章のとおり。
 * この機能はプライベートチャンネル限定で、公開チャンネルにはメンバー管理そのものが無い
 * （data.md 2-3 / features.md）。
 *
 * 判定は必ず「見えるか（見えなければ404）→ プライベートの作成者か（違えば403）」の順に行い、
 * 入力チェックはそのあと。逆にすると、自分に見えないプライベートチャンネルに403やエラー文が返り、
 * 存在が漏れる（behavior.md 3章「判定の順序」／ permissions-api.md 2章の補足）。
 */
class MemberController extends Controller
{
    /**
     * メンバー一覧（SC-07 / F-09）。
     *
     * 並びは参加した順、同時刻なら利用者のID順（screens.md 3-7 の追記）。
     * users にも created_at があるので、中間テーブル側の列であることを明示して修飾する。
     */
    public function index(Channel $channel): View
    {
        $this->ensureManageable($channel);

        return view('channels.members', [
            'channel' => $channel,
            'members' => $channel->members()
                ->orderBy('channel_user.created_at')
                ->orderBy('users.id')
                ->get(),
        ]);
    }

    /**
     * メンバーを追加する（F-10）。
     *
     * 表示名またはメールアドレスの完全一致で1人に特定する（screens.md 4章 / questions.md Q-06 は暫定）。
     */
    public function store(Request $request, Channel $channel): RedirectResponse
    {
        $this->ensureManageable($channel);

        // 入力チェックはフォームリクエストに置かない。コントローラ本体より先に走るため、
        // 見えないチャンネルへのPOSTにエラー文が先に返って存在が漏れる（permissions-api.md 2章の補足）。
        $validated = $request->validate(
            ['key' => 'required'],
            ['key.required' => '表示名またはメールアドレスを入力してください'],
        );

        $key = $validated['key'];

        $candidates = User::where('name', $key)->orWhere('email', $key)->get();

        // 2人以上に当たったときも「該当なし」と同じ扱いにする（screens.md 4章の追記）。
        // 1人に特定できない以上、追加する相手を選べないため。
        if ($candidates->count() !== 1) {
            return back()->withErrors(['key' => '該当する社員が見つかりません'])->withInput();
        }

        $target = $candidates->first();

        if ($channel->members()->whereKey($target->id)->exists()) {
            return back()->withErrors(['key' => 'すでにメンバーです'])->withInput();
        }

        // channel_user は updated_at を持たないので created_at を明示する（data.md 2-3 の補足）。
        $channel->members()->attach($target->id, ['created_at' => now()]);

        return redirect()->route('members.index', $channel);
    }

    /**
     * メンバーを外す（F-11）。channel_user の該当行を物理削除する（data.md 2-3）。
     *
     * 外された人の過去のメッセージには触れない（questions.md Q-05 は回答なし。data.md 2-3 に暫定として記録）。
     */
    public function destroy(Channel $channel, User $user): RedirectResponse
    {
        $this->ensureManageable($channel);

        // 作成者は外せない（questions.md「どのQにも当たらなかった回答」）。
        // 画面ではボタンを押せない状態にしているが、サーバ側でも必ず再検証する（behavior.md 3章）。
        abort_if($channel->isCreatedBy($user), 403);

        // 相手がメンバーでなければ消す行が無いだけ。エラーにはしない（permissions-api.md 2章の補足）。
        $channel->members()->detach($user->id);

        return redirect()->route('members.index', $channel);
    }

    /**
     * 見えるか（見えなければ404）→ プライベートの作成者か（違えば403）の順に確かめる。
     *
     * 順序を逆にすると、自分がメンバーでないプライベートチャンネルに403が返って存在が漏れる
     * （behavior.md 3章「判定の順序」）。公開チャンネルは作成者であってもここで403になる（同3章）。
     */
    private function ensureManageable(Channel $channel): void
    {
        abort_unless($channel->isVisibleTo(auth()->user()), 404);

        $this->authorize('manageMembers', $channel);
    }
}
