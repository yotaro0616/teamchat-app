{{-- SC-01 ログイン。項目は screens.md 3-1、見た目は mockup/login.html --}}
@extends('layouts.guest')

@section('title', 'ログイン')

@section('content')
    <h1 class="h-display" style="text-align:center;margin-bottom:var(--sp-6);font-size:var(--fs-title)">ログイン</h1>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="field @error('email') is-error @enderror">
            <label class="label" for="email">メールアドレス</label>
            <input class="input" id="email" type="text" name="email" value="{{ old('email') }}">
            @error('email')
                <span class="err">{{ $message }}</span>
            @enderror
        </div>

        {{-- 認証に失敗したときのエラーもこの欄に出る（screens.md 3-1「パスワード欄を赤枠にして」）。
             どちらが誤っているかは教えない --}}
        <div class="field @error('password') is-error @enderror">
            <label class="label" for="password">パスワード</label>
            <input class="input" id="password" type="password" name="password">
            @error('password')
                <span class="err">{{ $message }}</span>
            @enderror
        </div>

        <button class="btn btn--primary btn--lg btn--block" type="submit">ログイン</button>
    </form>

    <div class="auth__foot">アカウントをお持ちでない方は <a href="{{ route('register') }}">新規登録</a></div>
@endsection
