<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * スレッド返信の投稿（F-15 / SC-08）の入力チェック。
 *
 * 決まりは本流の投稿（StoreMessageRequest）と同じ（必須・1000文字以内。spec §5-1）だが、
 * **入力欄の名前だけを reply_body に分けている**（permissions-api.md 2章の※設計判断）。
 * SC-08 は本流の投稿欄と返信欄を同じページに並べる画面で、どちらも body だと old() が
 * 区別できず、返信が弾かれたときにその本文が本流の投稿欄にも復元されてしまう。
 *
 * この欄も「文言によるエラー表示はしない」と決めているので messages() は置かない
 * （空欄・超過は送信ボタンを押せない状態で表現する。screens.md 4章）。
 */
class StoreReplyRequest extends FormRequest
{
    /**
     * 権限の判定はここに置かない（permissions-api.md 2章の補足）。
     * authorize() はコントローラ本体より先に走るため、ここに判定を書くと
     * 見えないチャンネルへの操作に403が先に返り、存在が漏れる。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'reply_body' => ['required', 'string', 'max:1000'],
        ];
    }
}
