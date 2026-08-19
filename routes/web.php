<?php

use App\Http\Controllers\ChannelController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| URL と HTTP メソッドは docs/design/permissions-api.md 2章のとおり。
| /register・/login・/logout は Laravel Fortify が登録する。
|
*/

Route::redirect('/', '/channels');

Route::middleware(['auth', 'no-store'])->group(function () {
    Route::get('/channels', [ChannelController::class, 'index'])->name('channels.index');
    Route::get('/channels/create', [ChannelController::class, 'create'])->name('channels.create');
    Route::post('/channels', [ChannelController::class, 'store'])->name('channels.store');
    Route::get('/channels/{channel}', [ChannelController::class, 'show'])->name('channels.show');
    Route::get('/channels/{channel}/edit', [ChannelController::class, 'edit'])->name('channels.edit');
    Route::patch('/channels/{channel}', [ChannelController::class, 'update'])->name('channels.update');
    Route::delete('/channels/{channel}', [ChannelController::class, 'destroy'])->name('channels.destroy');

    // メンバー管理（F-09〜F-11）。使えるのはプライベートチャンネルの作成者だけ。
    Route::get('/channels/{channel}/members', [MemberController::class, 'index'])->name('members.index');
    Route::post('/channels/{channel}/members', [MemberController::class, 'store'])->name('members.store');
    Route::delete('/channels/{channel}/members/{user}', [MemberController::class, 'destroy'])->name('members.destroy');

    // メッセージ（F-12〜F-14）。一覧の描画（F-06）は channels.show の中。
    Route::post('/channels/{channel}/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::get('/channels/{channel}/messages/{message}/edit', [MessageController::class, 'edit'])->name('messages.edit');
    Route::patch('/channels/{channel}/messages/{message}', [MessageController::class, 'update'])->name('messages.update');
    Route::delete('/channels/{channel}/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
});
