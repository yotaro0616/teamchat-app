<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * チャンネル編集（F-07 / SC-06）の入力チェック。
 *
 * ルールは screens.md 4章、上限値は spec §5-1。
 * 公開範囲（type）はここで受け取らない。作成後に変更できず、編集フォームにも項目が無いため
 * （screens.md 3-6 / data.md 2-2）。
 */
class UpdateChannelRequest extends FormRequest
{
    /**
     * 権限の判定はここに置かない（permissions-api.md 2章の補足）。
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
            'name' => [
                'required',
                'string',
                'max:50',
                // 自分自身は重複の対象から外す（名前を変えずに説明だけ直せるようにするため）。
                Rule::unique('channels', 'name')->ignore($this->route('channel')),
            ],
            'description' => ['nullable', 'string', 'max:200'],
        ];
    }

    /**
     * 文言は screens.md 4章のものをそのまま使う（理由は StoreChannelRequest と同じ）。
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
