<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use Illuminate\Http\JsonResponse;

/**
 * 公開API（F-19）。認証不要・読み取り専用（spec §3-7 / permissions-api.md 2章・3章）。
 *
 * 存在しないチャンネルIDと非公開チャンネルのIDは同じ404にする（存在有無で応答を変えない。
 * behavior.md 3章 / questions.md Q-04と同じ考え方）。
 * ルートモデル結合を使わない理由は下記コメントのとおり。
 */
class MessageController extends Controller
{
    public function index(string $channelId): JsonResponse
    {
        $channel = Channel::find($channelId);

        // 通常の abort_unless($cond, 404) は使わない。ブラウザが Accept: application/json を
        // 送らない素のGETでは、例外ハンドラがHTMLの404ページを返してしまうため
        // （AC-7-1〜3はブラウザから開く想定。詳細は実装計画の「進めかたの注意」）。
        abort_unless(
            $channel && $channel->isPublic(),
            response()->json(['message' => '指定されたチャンネルが見つかりません'], 404)
        );

        $messages = $channel->messagesForPublicApi()->map(fn ($message) => [
            'id' => $message->id,
            'channel_id' => $message->channel_id,
            'body' => $message->body,
            'author_name' => $message->user->name,
            'created_at' => $message->created_at->format('Y-m-d\TH:i:s').'+09:00',
        ]);

        return response()->json($messages);
    }
}
