<?php

namespace Database\Seeders;

use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class MessageSeeder extends Seeder
{
    /**
     * docs/spec.md §5-4 のメッセージ。仕様書が求めている条件は次のとおり。
     *
     * - 公開チャンネルにはそれぞれ10件前後。日本語の自然な会話で、書いた人と日時がばらけていること
     * - 返信が付いたメッセージが2件以上（うち1件は返信が3件以上）
     * - 「編集済み」の印が付いたメッセージが1件以上
     * - 「このメッセージは削除されました」と表示されるメッセージが1件以上
     * - プライベートチャンネルにも数件
     *
     * 「開発」チャンネルの本流と、そこに付く返信は mockup/channel-show.html と thread.html の
     * 実例をそのまま写した（acceptance.md の受け入れ条件はこのデータが入っている前提）。
     * 他のチャンネルの会話は仕様書に無いため、同じ調子で書き起こした。
     *
     * created_at は並び順（古いものが上）を確かめられるように1件ずつ明示する。
     * updated_at は created_at と同じにする（「編集済み」の判定に updated_at は使わない、data.md 2-4）。
     */
    public function run(): void
    {
        $users = User::pluck('id', 'email');
        $channels = Channel::pluck('id', 'name');

        // key: 本文の目印。返信をぶら下げる先を指すのに使う
        $messages = [
            '開発' => [
                ['who' => 'suzuki', 'at' => '2026-08-17 09:12', 'body' => 'おはようございます。9月リリースの日程を今週中に確定させたいです。金曜の夕方までに、残っているタスクを各自共有してもらえますか。', 'replies' => [
                    ['who' => 'sato', 'at' => '2026-08-17 09:25', 'body' => '承知しました。今日中にこちらの残タスクをまとめて共有します。'],
                    ['who' => 'takahashi', 'at' => '2026-08-17 09:40', 'body' => '私も夕方までに出します。検索まわりだけ少し見積もりが揺れています。'],
                ]],
                ['who' => 'takahashi', 'at' => '2026-08-17 09:20', 'body' => '承知しました。検索まわりの実装は木曜に終わる見込みです。', 'edited' => true],
                ['who' => 'sato', 'at' => '2026-08-17 09:31', 'body' => 'ありがとうございます。私はチャンネル管理画面のレビューを今日中に返します。'],
                ['who' => 'takahashi', 'at' => '2026-08-17 10:05', 'body' => 'ステージングのログインが通らないのですが、他の方も同じでしょうか。', 'deleted' => true, 'replies' => [
                    ['who' => 'sato', 'at' => '2026-08-17 10:20', 'body' => 'こちらでも再現しました。少し調べてみます。'],
                    ['who' => 'suzuki', 'at' => '2026-08-17 10:45', 'body' => '原因が分かりました。ステージングの設定ファイルの参照先が古いままでした。更新しておきます。'],
                    ['who' => 'takahashi', 'at' => '2026-08-17 11:02', 'body' => '更新を確認しました。ログインできています。ありがとうございます。'],
                ]],
                ['who' => 'suzuki', 'at' => '2026-08-17 10:48', 'body' => 'ステージング環境でログインに失敗する件、原因が分かりました。設定ファイルの参照先が古いままでした。夕方までに直します。'],
                ['who' => 'sato', 'at' => '2026-08-17 11:15', 'body' => '助かりました。手順書のほうも合わせて直しておきます。'],
                ['who' => 'takahashi', 'at' => '2026-08-17 13:40', 'body' => '見積もりの件、来週の打ち合わせまでに数字を揃えておきます。必要な資料があれば教えてください。'],
                ['who' => 'sato', 'at' => '2026-08-17 14:32', 'body' => 'ありがとうございます。前回と同じ様式で大丈夫です。こちらでも先週分の実績をまとめておきます。'],
                ['who' => 'suzuki', 'at' => '2026-08-17 16:05', 'body' => 'リリース前の確認項目を一覧にしました。抜けがあれば追記してください。'],
                ['who' => 'takahashi', 'at' => '2026-08-18 09:08', 'body' => 'おはようございます。検索の実装、想定より早く終わりそうです。'],
                ['who' => 'sato', 'at' => '2026-08-18 09:35', 'body' => 'それは助かります。空いた時間でテストの追加をお願いできますか。'],
            ],
            '全体連絡' => [
                ['who' => 'suzuki', 'at' => '2026-08-17 08:55', 'body' => 'おはようございます。今週の全体会議は水曜15時からです。会議室は3階の大会議室に変わりました。'],
                ['who' => 'sato', 'at' => '2026-08-17 09:02', 'body' => '承知しました。前回の議事録を事前に共有しておきます。'],
                ['who' => 'takahashi', 'at' => '2026-08-17 09:18', 'body' => 'water サーバーの入れ替えが今日の午後にあります。しばらく使えない時間があります。'],
                ['who' => 'suzuki', 'at' => '2026-08-17 11:30', 'body' => '経費精算の締め切りは今月末です。忘れずにお願いします。'],
                ['who' => 'sato', 'at' => '2026-08-17 13:12', 'body' => '来月の懇親会の日程を調整しています。参加できない日があれば教えてください。'],
                ['who' => 'takahashi', 'at' => '2026-08-17 15:44', 'body' => '共有フォルダの整理を進めています。古い資料は月末にアーカイブへ移します。'],
                ['who' => 'suzuki', 'at' => '2026-08-18 08:40', 'body' => 'おはようございます。今日は在宅の方が多いので、連絡はこのチャンネルでお願いします。', 'replies' => [
                    ['who' => 'takahashi', 'at' => '2026-08-18 08:52', 'body' => '了解しました。午後は打ち合わせで少し離席します。'],
                ]],
                ['who' => 'sato', 'at' => '2026-08-18 10:05', 'body' => '社内のネットワーク工事が来週火曜の夜間にあります。作業中は接続が切れます。'],
                ['who' => 'takahashi', 'at' => '2026-08-18 11:20', 'body' => '新しい備品の申請フォームができました。必要な方はこちらから申請してください。'],
                ['who' => 'suzuki', 'at' => '2026-08-18 14:10', 'body' => '健康診断の日程調整表を配りました。今週中に希望日を記入してください。'],
            ],
            '雑談' => [
                ['who' => 'takahashi', 'at' => '2026-08-17 12:05', 'body' => '近くに新しくできたカレー屋、なかなか良かったです。'],
                ['who' => 'sato', 'at' => '2026-08-17 12:14', 'body' => 'いいですね。今度行ってみます。辛さは選べますか。'],
                ['who' => 'takahashi', 'at' => '2026-08-17 12:20', 'body' => '5段階で選べました。2でも十分辛かったです。'],
                ['who' => 'suzuki', 'at' => '2026-08-17 12:38', 'body' => '辛いものが苦手なので1にしておきます。'],
                ['who' => 'sato', 'at' => '2026-08-17 17:50', 'body' => '今日は涼しいですね。ようやく秋の気配がしてきました。'],
                ['who' => 'takahashi', 'at' => '2026-08-18 09:50', 'body' => '週末に見た映画がとても良かったので、おすすめしておきます。'],
                ['who' => 'suzuki', 'at' => '2026-08-18 10:02', 'body' => 'タイトルを教えてください。配信で見られるものでしょうか。'],
                ['who' => 'takahashi', 'at' => '2026-08-18 10:15', 'body' => '配信でも見られます。あとで詳しく送りますね。'],
                ['who' => 'sato', 'at' => '2026-08-18 12:30', 'body' => 'お昼のおすすめがあれば教えてください。この辺りに詳しくなくて。'],
                ['who' => 'suzuki', 'at' => '2026-08-18 12:41', 'body' => '駅前の定食屋がおすすめです。混む前の11時半くらいが狙い目です。'],
            ],
            '採用プロジェクト' => [
                ['who' => 'sato', 'at' => '2026-08-17 10:30', 'body' => '来月の面接の候補日を出しました。都合の悪い日があれば教えてください。'],
                ['who' => 'suzuki', 'at' => '2026-08-17 10:52', 'body' => '火曜の午後だけ別件が入っています。それ以外は大丈夫です。'],
                ['who' => 'sato', 'at' => '2026-08-17 14:05', 'body' => '募集要項の文面を見直しました。気になるところがあれば直してください。'],
                ['who' => 'suzuki', 'at' => '2026-08-18 09:15', 'body' => '確認しました。仕事内容の項目をもう少し具体的にしたほうが良さそうです。'],
            ],
        ];

        foreach ($messages as $channelName => $rows) {
            foreach ($rows as $row) {
                $parent = $this->create($channels[$channelName], $users, $row);

                foreach ($row['replies'] ?? [] as $reply) {
                    // 返信も親と同じ channel_id を持つ（data.md 2-4）。
                    $this->create($channels[$channelName], $users, $reply, $parent->id);
                }
            }
        }
    }

    /**
     * edited_at・deleted_at と日時は $fillable に入れていない（利用者の入力から埋まる列ではないため）。
     * ここでは代入してから save() する。
     *
     * @param  Collection<string, int>  $users
     * @param  array<string, mixed>  $row
     */
    private function create(int $channelId, Collection $users, array $row, ?int $parentId = null): Message
    {
        $postedAt = $row['at'].':00';

        $message = new Message([
            'channel_id' => $channelId,
            'user_id' => $users[$row['who'].'@example.com'],
            'parent_message_id' => $parentId,
            'body' => $row['body'],
        ]);

        // 「編集済み」は edited_at で判定する。updated_at では判定しない（data.md 2-4）。
        $message->edited_at = ($row['edited'] ?? false) ? $postedAt : null;
        // 削除済みでも body はクリアしない（data.md 0章）。
        $message->deleted_at = ($row['deleted'] ?? false) ? $postedAt : null;
        $message->created_at = $postedAt;
        $message->updated_at = $postedAt;
        $message->save();

        return $message;
    }
}
