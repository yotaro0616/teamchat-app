{{-- SC-03 チャンネル一覧。いまは認証（実装単位1）の着地先として見出しだけ。
     チャンネルの行・「チャンネルを作る」の入口・可視範囲の絞り込みは実装単位(2)で作る。 --}}
@extends('layouts.app')

@section('title', 'チャンネル一覧')

@section('content')
    <div class="page">
        <div class="page__head">
            <div>
                <h1 class="h-title">チャンネル一覧</h1>
                <p class="t-caption" style="margin-top:4px">公開チャンネルは、ログインしている社員なら誰でも読み書きできます。</p>
            </div>
        </div>
    </div>
@endsection
