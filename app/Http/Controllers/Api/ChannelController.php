<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use Illuminate\Http\JsonResponse;

/**
 * 公開API（F-18）。認証不要・読み取り専用（spec §3-7 / permissions-api.md 2章・3章）。
 */
class ChannelController extends Controller
{
    public function index(): JsonResponse
    {
        $channels = Channel::query()
            ->where('type', 'public')
            ->orderBy('id')
            ->get()
            ->map(fn (Channel $channel) => [
                'id' => $channel->id,
                'name' => $channel->name,
                'description' => $channel->description,
            ]);

        return response()->json($channels);
    }
}
