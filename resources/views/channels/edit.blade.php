{{-- SC-06 チャンネルを編集する／削除の確認（F-07・F-08）。
     項目は screens.md 3-6、見た目は mockup/channel-edit.html。
     公開範囲の欄は無い（作成後に変更できないため）。
     削除確認カードは同じページ内に常に描き、上段の「削除する」はページ内アンカーにする
     （permissions-api.md 2章。追加の画面やJSは使わない）。 --}}
@extends('layouts.app')

@section('title', 'チャンネルを編集する')

@push('scripts')
    <script src="{{ asset('js/app.js') }}" defer></script>
@endpush

@section('content')
    <div class="page" style="max-width:680px">
        <h1 class="h-title" style="margin-bottom:var(--sp-6)">チャンネルを編集する</h1>

        <form method="POST" action="{{ route('channels.update', $channel) }}">
            @csrf
            @method('PATCH')

            <div class="card" style="margin-bottom:var(--sp-8)"><div class="card__body">
                <div class="field @error('name') is-error @enderror">
                    <label class="label" for="name">チャンネル名<span class="req">*</span></label>
                    <input class="input" id="name" type="text" name="name" value="{{ old('name', $channel->name) }}"
                           data-counter="name-count" data-counter-max="50">
                    <div class="field-foot">
                        @error('name')
                            <span class="err">{{ $message }}</span>
                        @else
                            {{-- 公開範囲は変えられないことをここで伝える（screens.md 3-6） --}}
                            <span class="help">{{ $channel->isPublic() ? '公開チャンネルです。' : 'プライベートチャンネルです。' }}公開範囲はあとから変更できません。</span>
                        @enderror
                        <span class="count" id="name-count">{{ mb_strlen(old('name', $channel->name)) }} / 50</span>
                    </div>
                </div>

                <div class="field @error('description') is-error @enderror">
                    <label class="label" for="description">説明</label>
                    <textarea class="textarea" id="description" name="description"
                              data-counter="description-count" data-counter-max="200">{{ old('description', $channel->description) }}</textarea>
                    <div class="field-foot">
                        @error('description')
                            <span class="err">{{ $message }}</span>
                        @else
                            <span class="help"></span>
                        @enderror
                        <span class="count" id="description-count">{{ mb_strlen(old('description', $channel->description ?? '')) }} / 200</span>
                    </div>
                </div>

                <div class="inline-2" style="justify-content:flex-end;margin-top:var(--sp-6)">
                    <a class="btn" href="{{ route('channels.show', $channel) }}">キャンセル</a>
                    <button class="btn btn--primary" type="submit">保存する</button>
                </div>
            </div></div>
        </form>

        <h2 class="h-heading" style="margin-bottom:var(--sp-3)">チャンネルを削除する</h2>

        <div class="danger-box">
            <div class="spread" style="margin-bottom:var(--sp-4)">
                <div>
                    <div class="t-label" style="color:var(--danger)">このチャンネルを削除します</div>
                    <div class="t-caption">メッセージと返信もすべて削除され、元に戻せません。</div>
                </div>
                {{-- 確認カードは下に常に描いてあるので、ここはページ内アンカー（permissions-api.md 2章） --}}
                <a class="btn btn--danger" href="#delete-confirm">削除する</a>
            </div>

            <div class="card" id="delete-confirm" style="border-color:var(--danger-line)"><div class="card__body">
                <div class="h-heading" style="margin-bottom:var(--sp-2)">「{{ $channel->name }}」を削除しますか？</div>
                {{-- 件数は messages を数えた値。チャンネルの削除は物理削除で、削除済みメッセージの行も
                     一緒に消えるため deleted_at は問わずに数える（screens.md 3-6 の追記 / data.md 2-2） --}}
                <p class="t-body t-muted" style="margin-bottom:var(--sp-4)">メッセージ {{ $channel->messageCount() }}件と返信 {{ $channel->replyCount() }}件も削除されます。この操作は取り消せません。確認のためチャンネル名を入力してください。</p>

                <form method="POST" action="{{ route('channels.destroy', $channel) }}">
                    @csrf
                    @method('DELETE')

                    <input class="input" type="text" name="name" placeholder="{{ $channel->name }}" style="max-width:280px"
                           data-confirm-input="delete-submit" data-confirm-value="{{ $channel->name }}">
                    <div class="inline-2" style="justify-content:flex-end;margin-top:var(--sp-5)">
                        <a class="btn" href="{{ route('channels.show', $channel) }}">やめる</a>
                        {{-- 入力がチャンネル名と一致するまで押せない（design-guide.md §4「押せない」）。
                             一致の確認はサーバ側でも再検証する（behavior.md 3章） --}}
                        <button class="btn btn--danger-solid" id="delete-submit" type="submit" disabled>削除する</button>
                    </div>
                </form>
            </div></div>
        </div>
    </div>
@endsection
