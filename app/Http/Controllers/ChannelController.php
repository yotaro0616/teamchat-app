<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ChannelController extends Controller
{
    /**
     * チャンネル一覧（SC-03 / F-04）。
     *
     * いまは認証（実装単位1）の着地先として最小限しか描かない。
     * 一覧の中身・作成の入口・可視範囲の絞り込みは実装単位2で作る。
     */
    public function index(): View
    {
        return view('channels.index');
    }
}
