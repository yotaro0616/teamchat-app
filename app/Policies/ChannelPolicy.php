<?php

namespace App\Policies;

use App\Models\Channel;
use App\Models\User;

/**
 * チャンネルの権限（docs/design/permissions-api.md 1章 F-07・F-08）。
 *
 * ここが受け持つのは「作成者かどうか」だけ（違えば403）。
 * 「見えるかどうか」（見えなければ404）は、それより先にコントローラで判定する。
 * 順序を逆にすると、自分に見えないプライベートチャンネルに403が返り、存在が漏れる
 * （behavior.md 3章「判定の順序」）。
 */
class ChannelPolicy
{
    /**
     * 名前・説明を編集できるのは作成者だけ（spec §3-2 / F-07）。
     */
    public function update(User $user, Channel $channel): bool
    {
        return $channel->isCreatedBy($user);
    }

    /**
     * 削除できるのは作成者だけ（spec §3-2 / F-08）。
     */
    public function delete(User $user, Channel $channel): bool
    {
        return $channel->isCreatedBy($user);
    }

    /**
     * メンバーを見る・追加する・外せるのは、プライベートチャンネルの作成者だけ
     * （permissions-api.md 1章 F-09〜F-11 / 2章の補足）。
     *
     * 公開チャンネルにはメンバー管理そのものが無い（data.md 2-3）ので、作成者でも通さない
     * （behavior.md 3章の公開チャンネルの行。403になる）。
     */
    public function manageMembers(User $user, Channel $channel): bool
    {
        return ! $channel->isPublic() && $channel->isCreatedBy($user);
    }
}
