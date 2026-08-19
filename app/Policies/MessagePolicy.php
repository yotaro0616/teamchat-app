<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

/**
 * メッセージの権限（docs/design/permissions-api.md 1章 F-13・F-14 と注記[2]）。
 *
 * ここが受け持つのは「投稿者本人かどうか」と「まだ削除されていないか」だけ（違えば403）。
 * 「チャンネルが見えるか」「そのメッセージがそのチャンネルのものか」（どちらも違えば404）は、
 * それより先にコントローラで判定する。順序を逆にすると、自分に見えないプライベートチャンネルに
 * 403が返り、存在が漏れる（behavior.md 3章「判定の順序」）。
 *
 * **チャンネルの作成者であっても、他人が書いたメッセージは編集・削除できない**
 * （permissions-api.md 1章 注記[2]。作成者という立場そのものに特権は無い）。
 */
class MessagePolicy
{
    /**
     * 編集できるのは投稿者本人だけ（spec §3-4）。
     * 削除済みは終端状態なので、本人でも編集できない（behavior.md 1章・3章）。
     */
    public function update(User $user, Message $message): bool
    {
        return $message->isPostedBy($user) && ! $message->isDeleted();
    }

    /**
     * 削除できるのは投稿者本人だけ。すでに削除済みならこれ以上できることはない（同上）。
     */
    public function delete(User $user, Message $message): bool
    {
        return $message->isPostedBy($user) && ! $message->isDeleted();
    }
}
