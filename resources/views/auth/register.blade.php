{{-- SC-02 新規登録。項目は screens.md 3-2、見た目は mockup/register.html --}}
@extends('layouts.guest')

@section('title', '新規登録')

@push('scripts')
    <script src="{{ asset('js/app.js') }}" defer></script>
@endpush

@section('content')
    <h1 class="h-display" style="text-align:center;margin-bottom:var(--sp-6);font-size:var(--fs-title)">新規登録</h1>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="field @error('name') is-error @enderror">
            <label class="label" for="name">表示名<span class="req">*</span></label>
            <input class="input" id="name" type="text" name="name" value="{{ old('name') }}"
                   data-counter="name-count" data-counter-max="30">
            <div class="field-foot">
                <span class="help">メッセージに表示される名前です。</span>
                <span class="count" id="name-count">{{ mb_strlen(old('name', '')) }} / 30</span>
            </div>
            @error('name')
                <span class="err">{{ $message }}</span>
            @enderror
        </div>

        <div class="field @error('email') is-error @enderror">
            <label class="label" for="email">メールアドレス<span class="req">*</span></label>
            <input class="input" id="email" type="text" name="email" value="{{ old('email') }}">
            @error('email')
                <span class="err">{{ $message }}</span>
            @enderror
        </div>

        <div class="field @error('password') is-error @enderror">
            <label class="label" for="password">パスワード<span class="req">*</span></label>
            <input class="input" id="password" type="password" name="password">
            <span class="help">8文字以上で入力してください。</span>
            @error('password')
                <span class="err">{{ $message }}</span>
            @enderror
        </div>

        <div class="field @error('password_confirmation') is-error @enderror">
            <label class="label" for="password_confirmation">パスワード（確認用）<span class="req">*</span></label>
            <input class="input" id="password_confirmation" type="password" name="password_confirmation">
            @error('password_confirmation')
                <span class="err">{{ $message }}</span>
            @enderror
        </div>

        <button class="btn btn--primary btn--lg btn--block" type="submit">登録する</button>
    </form>

    <div class="auth__foot">すでにアカウントをお持ちの方は <a href="{{ route('login') }}">ログイン</a></div>
@endsection
