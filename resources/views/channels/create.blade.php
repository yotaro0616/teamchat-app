{{-- SC-04 チャンネルを作る（F-05）。項目は screens.md 3-4、見た目は mockup/channel-new.html --}}
@extends('layouts.app')

@section('title', 'チャンネルを作る')

@push('scripts')
    <script src="{{ asset('js/app.js') }}" defer></script>
@endpush

@section('content')
    <div class="page" style="max-width:680px">
        <h1 class="h-title" style="margin-bottom:var(--sp-6)">チャンネルを作る</h1>

        <form method="POST" action="{{ route('channels.store') }}">
            @csrf

            <div class="card"><div class="card__body">
                <div class="field @error('name') is-error @enderror">
                    <label class="label" for="name">チャンネル名<span class="req">*</span></label>
                    <input class="input" id="name" type="text" name="name" value="{{ old('name') }}"
                           data-counter="name-count" data-counter-max="50">
                    <div class="field-foot">
                        @error('name')
                            <span class="err">{{ $message }}</span>
                        @else
                            <span class="help"></span>
                        @enderror
                        <span class="count" id="name-count">{{ mb_strlen(old('name', '')) }} / 50</span>
                    </div>
                </div>

                <div class="field @error('description') is-error @enderror">
                    <label class="label" for="description">説明</label>
                    <textarea class="textarea" id="description" name="description"
                              data-counter="description-count" data-counter-max="200">{{ old('description') }}</textarea>
                    <div class="field-foot">
                        @error('description')
                            <span class="err">{{ $message }}</span>
                        @else
                            <span class="help">何について話す場所かを短く書きます。</span>
                        @enderror
                        <span class="count" id="description-count">{{ mb_strlen(old('description', '')) }} / 200</span>
                    </div>
                </div>

                {{-- 公開範囲は作成時だけ選べる。あとから変更できない（screens.md 3-6 / data.md 2-2） --}}
                <div class="field">
                    <span class="label">公開範囲</span>
                    <div class="radio-set">
                        <label class="radio @if (old('type', 'public') === 'public') is-checked @endif">
                            <input type="radio" name="type" value="public" @checked(old('type', 'public') === 'public')>
                            <span>
                                <span class="rt">公開</span>
                                <span class="rd" style="display:block">ログインしている社員なら誰でも読み書きできます。</span>
                            </span>
                        </label>
                        <label class="radio @if (old('type') === 'private') is-checked @endif">
                            <input type="radio" name="type" value="private" @checked(old('type') === 'private')>
                            <span>
                                <span class="rt">プライベート</span>
                                <span class="rd" style="display:block">追加された人だけが読み書きできます。作成者がメンバーを管理します。</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="inline-2" style="justify-content:flex-end;margin-top:var(--sp-6)">
                    <a class="btn" href="{{ route('channels.index') }}">キャンセル</a>
                    <button class="btn btn--primary" type="submit">作成する</button>
                </div>
            </div></div>
        </form>
    </div>
@endsection
