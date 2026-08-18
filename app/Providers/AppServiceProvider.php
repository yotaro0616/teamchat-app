<?php

namespace App\Providers;

use App\Models\Channel;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // サイドバーのチャンネル一覧（screens.md 3章「共通レイアウト」）。
        // 出るのは公開チャンネル全部と、自分がメンバーのプライベートだけ（questions.md Q-04）。
        // 可視範囲の絞り込みは Channel::visibleTo() の1本に通し、ビューで隠す形にはしない。
        View::composer('layouts.app', function ($view) {
            $view->with('sidebarChannels', Channel::visibleTo(auth()->user())->orderBy('id')->get());
        });
    }
}
