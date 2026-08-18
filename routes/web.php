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
});
