<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * メッセージ投稿（F-12 / SC-05）とスレッド返信の投稿（F-15 / SC-08）の入力チェック。
 *
 * 返信も本文の決まりは本流と同じ（必須・1000文字以内）なので、この1本を共用する
 * （permissions-api.md 2章の補足）。
 *
 * ルールは screens.md 4章、上限値は spec §5-1。
 * この欄は「文言によるエラー表示はしない」と決めているので messages() は置かない
 * （空欄・超過は送信ボタンを押せない状態で表現する。screens.md 4章）。
 * ここを通り抜けたリクエストだけがはじかれた場合は、元の画面に戻すだけになる。
 */
class StoreMessageRequest extends FormRequest
{
    /**
     * 権限の判定はここに置かない（permissions-api.md 2章の補足）。
     * authorize() はコントローラ本体より先に走るため、ここに本人チェックを書くと
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
            'body' => ['required', 'string', 'max:1000'],
        ];
    }
}
