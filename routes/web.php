<?php

use App\Http\Controllers\ChannelController;
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
});
