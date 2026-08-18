<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * チャンネル作成（F-05 / SC-04）の入力チェック。
 *
 * ルールは screens.md 4章、上限値は spec §5-1。
 */
class StoreChannelRequest extends FormRequest
{
    /**
     * 権限の判定はここに置かない（permissions-api.md 2章の補足）。
     * authorize() はコントローラ本体より先に走るため、ここに作成者チェックを書くと
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
            'name' => ['required', 'string', 'max:50', 'unique:channels,name'],
            'description' => ['nullable', 'string', 'max:200'],
            'type' => ['required', 'in:public,private'],
        ];
    }

    /**
     * 文言は screens.md 4章のものをそのまま使う。
     *
     * lang/ja/validation.php の attributes は name を「表示名」（新規登録の項目）としているため、
     * 共通の文言に任せると「表示名を入力してください」になってしまう。
     * 共通側を書き換えると新規登録の文言が壊れるので、チャンネル側はここで指定する。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'チャンネル名を入力してください',
            'name.max' => 'チャンネル名は50文字以内で入力してください',
            'name.unique' => '同じ名前のチャンネルがすでにあります',
            'description.max' => '説明は200文字以内で入力してください',
        ];
    }
}
