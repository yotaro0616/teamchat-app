<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use Database\Seeders\ChannelSeeder;
use Database\Seeders\MessageSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F-16 スレッド表示（SC-08）。
 *
 * 受け入れ条件 AC-5-1・AC-5-3、テスト観点 TP-5-01・TP-5-02・TP-5-03・TP-5-05
 * （docs/design/acceptance.md）。
 * SC-08 は専用ビューを持たず、SC-05 に3列目のパネルを足したもの（screens.md 3-8）。
 */
class ThreadTest extends TestCase
{
    use RefreshDatabase;

    private function seedInitialData(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(ChannelSeeder::class);
        $this->seed(MessageSeeder::class);
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    /**
     * 初期データで返信3件が付いている「開発」の親（高橋 健 10:05・削除済み）を返す。
     */
    private function threadOfThree(): Message
    {
        $channel = Channel::where('name', '開発')->firstOrFail();

        return Message::where('channel_id', $channel->id)
            ->whereNull('parent_message_id')
            ->where('body', 'ステージングのログインが通らないのですが、他の方も同じでしょうか。')
            ->firstOrFail();
    }

    /** AC-5-1 */
    public function test_返信3件のスレッドを開くと返信が3件並ぶ(): void
    {
        $this->seedInitialData();

        $parent = $this->threadOfThree();

        $this->actingAs($this->user('sato@example.com'))
            ->get("/channels/{$parent->channel_id}/messages/{$parent->id}/thread")
            ->assertOk()
            // 見出し・チャンネル名・返信件数・返信本文が上から順に並ぶ（mockup/thread.html）
            ->assertSeeInOrder([
                'スレッド',
                '開発',
                'このメッセージは削除されました',
                '返信3件',
                'こちらでも再現しました。少し調べてみます。',
                '原因が分かりました。ステージングの設定ファイルの参照先が古いままでした。更新しておきます。',
                '更新を確認しました。ログインできています。ありがとうございます。',
            ])
            ->assertSee('返信を送る');
    }

    /** AC-5-1 元メッセージは削除済みでも枠として残り、投稿者名と日時も残る（data.md 3章 F-16行） */
    public function test_削除済みの元メッセージも枠として残る(): void
    {
        $this->seedInitialData();

        $parent = $this->threadOfThree();

        $this->actingAs($this->user('sato@example.com'))
            ->get("/channels/{$parent->channel_id}/messages/{$parent->id}/thread")
            ->assertOk()
            ->assertSee('高橋 健')
            ->assertSee('2026/08/17 10:05')
            ->assertSee('このメッセージは削除されました')
            // 本文はクリアしないが画面には出さない（data.md 0章）
            ->assertDontSee('ステージングのログインが通らないのですが、他の方も同じでしょうか。');
    }

    /** AC-5-3 */
    public function test_親を削除しても返信件数とスレッドの中身は残る(): void
    {
        $this->seedInitialData();

        $channel = Channel::where('name', '開発')->firstOrFail();
        $parent = Message::where('channel_id', $channel->id)
            ->where('body', 'おはようございます。9月リリースの日程を今週中に確定させたいです。金曜の夕方までに、残っているタスクを各自共有してもらえますか。')
            ->firstOrFail();

        $suzuki = $this->user('suzuki@example.com');

        $this->actingAs($suzuki)
            ->delete("/channels/{$channel->id}/messages/{$parent->id}")
            ->assertRedirect("/channels/{$channel->id}");

        // 一覧では枠に変わっても「返信2件」は残る
        $this->actingAs($suzuki)
            ->get("/channels/{$channel->id}")
            ->assertOk()
            ->assertSee('このメッセージは削除されました')
            ->assertSee('返信2件');

        // 開けば返信2件がそのまま出る
        $this->actingAs($suzuki)
            ->get("/channels/{$channel->id}/messages/{$parent->id}/thread")
            ->assertOk()
            ->assertSee('返信2件')
            ->assertSee('承知しました。今日中にこちらの残タスクをまとめて共有します。')
            ->assertSee('私も夕方までに出します。検索まわりだけ少し見積もりが揺れています。');
    }

    /** TP-5-01 */
    public function test_返信が無いメッセージには返信n件のリンクが出ない(): void
    {
        $channel = Channel::factory()->create();
        $message = Message::factory()->create(['channel_id' => $channel->id]);

        $this->actingAs($message->user)
            ->get("/channels/{$channel->id}")
            ->assertOk()
            // 「返信0件」という表示はどこにも無い（mockup/channel-show.html）
            ->assertDontSee('返信0件')
            ->assertDontSee('class="thread-link"', false)
            // 返信アイコン（スレッドを開く唯一の導線）は返信0件でも出る（screens.md 3-5）
            ->assertSee(route('threads.show', [$channel, $message]), false);
    }

    /** TP-5-01 返信が1件以上あるメッセージには「返信N件」が出る */
    public function test_返信があるメッセージには返信n件のリンクが出る(): void
    {
        $channel = Channel::factory()->create();
        $parent = Message::factory()->create(['channel_id' => $channel->id]);
        Message::factory()->count(2)->replyTo($parent)->create();

        $this->actingAs($parent->user)
            ->get("/channels/{$channel->id}")
            ->assertOk()
            ->assertSee('返信2件');
    }

    /** TP-5-02 パネルの中には返信の導線を出さない（返信への返信はできない） */
    public function test_パネルの中に返信の導線が無い(): void
    {
        $channel = Channel::factory()->create();
        $parent = Message::factory()->create(['channel_id' => $channel->id]);
        $reply = Message::factory()->replyTo($parent)->create();

        $response = $this->actingAs($parent->user)
            ->get("/channels/{$channel->id}/messages/{$parent->id}/thread")
            ->assertOk()
            // 返信を親にしたスレッドURLは、画面のどこにも現れない
            ->assertDontSee(route('threads.show', [$channel, $reply]), false);

        // 「返信N件」リンクもパネルの中には出さない（mockup/thread.html の元メッセージに無い）。
        // この画面は左の一覧（SC-05）も一緒に描いていて、そちらには親の分が1つ出るので、
        // 「出ない」ではなく「1つだけ＝パネルが増やしていない」で見る
        $this->assertSame(1, substr_count($response->content(), 'class="thread-link"'));
    }

    /** TP-5-02 パネルの中でも編集・削除は本人にだけ出る（他人の返信には空の枠すら描かない） */
    public function test_パネルの中の編集と削除は本人にだけ出る(): void
    {
        $channel = Channel::factory()->create();
        $parent = Message::factory()->create(['channel_id' => $channel->id]);
        $mine = Message::factory()->replyTo($parent)->create(['user_id' => $parent->user_id]);
        $others = Message::factory()->replyTo($parent)->create();

        $this->actingAs($parent->user)
            ->get("/channels/{$channel->id}/messages/{$parent->id}/thread")
            ->assertOk()
            ->assertSee(route('messages.edit', [$channel, $mine]), false)
            ->assertDontSee(route('messages.edit', [$channel, $others]), false)
            ->assertDontSee('action="'.route('messages.destroy', [$channel, $others]).'"', false);
    }

    /** TP-5-03 空欄のうちは送信を押せない状態で描かれる（screens.md 4章） */
    public function test_返信欄の送信ボタンは最初は押せない状態で描かれる(): void
    {
        $channel = Channel::factory()->create();
        $parent = Message::factory()->create(['channel_id' => $channel->id]);

        $this->actingAs($parent->user)
            ->get("/channels/{$channel->id}/messages/{$parent->id}/thread")
            ->assertOk()
            ->assertSee('id="reply-submit" disabled', false)
            ->assertSee('0 / 1,000 文字');
    }

    /** TP-5-05 */
    public function test_閉じるはチャンネル画面へ戻る(): void
    {
        $channel = Channel::factory()->create();
        $parent = Message::factory()->create(['channel_id' => $channel->id]);

        $this->actingAs($parent->user)
            ->get("/channels/{$channel->id}/messages/{$parent->id}/thread")
            ->assertOk()
            ->assertSee('title="閉じる"', false)
            ->assertSee('href="'.route('channels.show', $channel).'"', false);
    }

    /** 返信が0件でも「返信0件」と出す（screens.md 3-8「返信が0件のときの見せ方について」） */
    public function test_返信が0件のスレッドは返信0件と出す(): void
    {
        $channel = Channel::factory()->create();
        $parent = Message::factory()->create(['channel_id' => $channel->id]);

        $this->actingAs($parent->user)
            ->get("/channels/{$channel->id}/messages/{$parent->id}/thread")
            ->assertOk()
            ->assertSee('返信0件');
    }

    /** 削除済みの返信も枠として残る（data.md 3章 F-16行） */
    public function test_削除済みの返信も枠として残る(): void
    {
        $channel = Channel::factory()->create();
        $parent = Message::factory()->create(['channel_id' => $channel->id]);
        Message::factory()->replyTo($parent)->deleted()->create();

        $this->actingAs($parent->user)
            ->get("/channels/{$channel->id}/messages/{$parent->id}/thread")
            ->assertOk()
            // 件数からも外さない
            ->assertSee('返信1件')
            ->assertSee('このメッセージは削除されました');
    }

    /** 見えないプライベートチャンネルは403ではなく404（behavior.md 3章） */
    public function test_見られないプライベートチャンネルのスレッドは開けない(): void
    {
        $this->seedInitialData();

        $channel = Channel::where('name', '採用プロジェクト')->firstOrFail();
        $parent = Message::where('channel_id', $channel->id)
            ->whereNull('parent_message_id')
            ->firstOrFail();

        // 高橋 健はメンバーではない
        $this->actingAs($this->user('takahashi@example.com'))
            ->get("/channels/{$channel->id}/messages/{$parent->id}/thread")
            ->assertNotFound();
    }

    /** チャンネルとメッセージが食い違うURLも404（判定の順序。behavior.md 3章） */
    public function test_別のチャンネルのメッセージidを差し込むと404(): void
    {
        $channel = Channel::factory()->create();
        $other = Channel::factory()->create();
        $message = Message::factory()->create(['channel_id' => $other->id]);

        $this->actingAs(User::factory()->create())
            ->get("/channels/{$channel->id}/messages/{$message->id}/thread")
            ->assertNotFound();
    }

    /** ログインしていなければログイン画面へ（behavior.md 3章） */
    public function test_ログインしていないとスレッドを開けない(): void
    {
        $channel = Channel::factory()->create();
        $parent = Message::factory()->create(['channel_id' => $channel->id]);

        $this->get("/channels/{$channel->id}/messages/{$parent->id}/thread")
            ->assertRedirect('/login');
    }
}
