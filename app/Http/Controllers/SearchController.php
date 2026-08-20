<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 検索（F-17 / SC-09）。
 *
 * `{channel}` のようなURLパラメータを取らないため、チャンネル・メッセージの各コントローラにある
 * 「見えるか（404）→ そのリソースのものか（404）→ 本人か（403）」の判定は無い
 * （permissions-api.md「検索(6)」補足、http.md）。
 * 安全性は判定の順序ではなく、Message::scopeSearchableBy() が検索クエリ自体を
 * Channel::visibleTo() の範囲に絞り込むことで担保する。
 */
class SearchController extends Controller
{
    /**
     * 検索結果（F-17）。
     *
     * `q` が空／未指定のときはエラー文を出さず、結果を出さない「まだ検索していない」状態にする
     * （screens.md 3-9 実装時追記）。空欄はボタン非活性だけで表現するため、入力チェックは行わない。
     */
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));

        $results = $keyword === ''
            ? collect()
            : Message::query()
                ->searchableBy($request->user())
                ->matchingKeyword($keyword)
                ->with(['channel', 'user'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get();

        return view('search.index', ['keyword' => $keyword, 'results' => $results]);
    }
}
