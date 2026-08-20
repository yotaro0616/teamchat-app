<?php

use App\Http\Controllers\Api\ChannelController;
use App\Http\Controllers\Api\MessageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| 置くのは公開API（F-18/F-19）の2本だけ。認証不要・読み取り専用で、書き込み系は作らない
| （permissions-api.md 2章の補足）。Laravel 既定の GET /api/user は、対応する機能が
| 機能一覧に無いため外した。
|
*/

// 公開API（F-18/F-19、認証不要。permissions-api.md 2章・3章）。
Route::get('/channels', [ChannelController::class, 'index']);
Route::get('/channels/{channel}/messages', [MessageController::class, 'index']);
