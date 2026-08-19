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
 * F-15 スレッド返信投稿（SC-08）。
 *
 * 受け入れ条件 AC-5-2、テスト観点 TP-5-02・TP-5-03・TP-5-04（docs/design/acceptance.md）。
 * 返信は messages の自己参照で、別テーブルにはしない（data.md 1章）。
 */
class ThreadReplyTest extends TestCase
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

    /** AC-5-2 */
    public function test_返信を投稿すると返信件数が1つ増える(): void
    {
        $this->seedInitialData();

        $channel = Channel::where('name', '開発')->firstOrFail();
        $parent = Message::where('channel_id', $channel->id)
            ->where('body', 'ステージングのログインが通らないのですが、他の方も同じでしょうか。')
            ->firstOrFail();

        $sato = $this->user('sato@example.com');

        // 投稿したら同じスレッドへ戻る
        $this->actingAs($sato)
            ->post("/channels/{$channel->id}/messages/{$parent->id}/replies", ['reply_body' => '直っているのを確認しました'])
            ->assertRedirect("/channels/{$channel->id}/messages/{$parent->id}/thread");

        // channel_id は親と一致させる（data.md 2-4）
        $this->assertDatabaseHas('messages', [
            'channel_id' => $channel->id,
            'user_id' => $sato->id,
            'parent_message_id' => $parent->id,
            'body' => '直っているのを確認しました',
            'edited_at' => null,
            'deleted_at' => null,
        ]);

        // スレッドは3件から4件になり、いちばん下に出る
        $this->actingAs($sato)
            ->get("/channels/{$channel->id}/messages/{$parent->id}/thread")
            ->assertOk()
            ->assertSee('返信4件')
            ->assertSeeInOrder([
                '更新を確認しました。ログインできています。ありがとうございます。',
                '直っているのを確認しました',
            ]);

        // 左のメッセージ一覧の「返信N件」も4件になる
        $this->actingAs($sato)
            ->get("/channels/{$channel->id}")
            ->assertOk()
            ->assertSee('返信4件');
    }

    /** TP-5-04 削除済みの元メッセージにも返信できる（behavior.md 1章） */
    public function test_削除済みの元メッセージにも返信できる(): void
    {
        $channel = Channel::factory()->create();
        $parent = Message::factory()->deleted()->create(['channel_id' => $channel->id]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post("/channels/{$channel->id}/messages/{$parent->id}/replies", ['reply_body' => '削除済みへの返信'])
            ->assertRedirect("/channels/{$channel->id}/messages/{$parent->id}/thread");

        $this->assertDatabaseHas('messages', [
            'parent_message_id' => $parent->id,
            'body' => '削除済みへの返信',
        ]);
    }

    /** 返信は投稿者本人でなくても、見えるチャンネルなら誰でもできる（ポリシーを使わない） */
    public function test_他の人のメッセージにも返信できる(): void
    {
        $channel = Channel::factory()->create();
        $parent = Message::factory()->create(['channel_id' => $channel->id]);

        $this->actingAs(User::factory()->create())
            ->post("/channels/{$channel->id}/messages/{$parent->id}/replies", ['reply_body' => '他人への返信'])
            ->assertRedirect("/channels/{$channel->id}/messages/{$parent->id}/thread");

        $this->assertDatabaseHas('messages', ['parent_message_id' => $parent->id, 'body' => '他人への返信']);
    }

    /** TP-5-03 サーバ側でも同じ上限ではじく（screens.md 4章 / spec §5-1） */
    public function test_空欄と1001文字は投稿できない(): void
    {
        $channel = Channel::factory()->create();
        $parent = Message::factory()->create(['channel_id' => $channel->id]);
        $user = User::factory()->create();
        $url = "/channels/{$channel->id}/messages/{$parent->id}/replies";

        $this->actingAs($user)->post($url, ['reply_body' => ''])->assertSessionHasErrors('reply_body');
        $this->actingAs($user)->post($url, ['reply_body' => str_repeat('あ', 1001)])->assertSessionHasErrors('reply_body');

        $this->assertDatabaseCount('messages', 1);
    }

    /**
     * 弾かれた返信の本文が、本流の投稿欄に復元されない。
     *
     * SC-08 は投稿欄と返信欄が同じページに並ぶので、両方の name が body だと old() が
     * 区別できず、返信のつもりの文章を本流に投稿できてしまう（permissions-api.md 2章の
     * ※設計判断で欄の名前を reply_body に分けた）。送信ボタンの非活性はこの担保にしない
     * （behavior.md 3章）。
     */
    public function test_弾かれた返信の本文が本流の投稿欄に入らない(): void
    {
        $channel = Channel::factory()->create();
        $parent = Message::factory()->create(['channel_id' => $channel->id]);
        $user = User::factory()->create();
        $threadUrl = "/channels/{$channel->id}/messages/{$parent->id}/thread";
        $long = str_repeat('あ', 1001);

        $this->actingAs($user)
            ->from($threadUrl)
            ->post("/channels/{$channel->id}/messages/{$parent->id}/replies", ['reply_body' => $long])
            ->assertRedirect($threadUrl);

        $page = $this->actingAs($user)->get($threadUrl)->assertOk()->content();

        // 返信欄には戻る（入力を捨てない）
        $this->assertStringContainsString('name="reply_body"', $page);
        $this->assertSame(1, substr_count($page, $long));

        // 本流の投稿欄は空のまま
        $composer = explode('id="composer-body"', $page)[1];
        $composer = substr($composer, 0, strpos($composer, '</textarea>'));
        $this->assertStringNotContainsString('あああ', $composer);
    }

    /** TP-5-03 1000文字ちょうどは通る */
    public function test_本文1000文字ちょうどで返信できる(): void
    {
        $channel = Channel::factory()->create();
        $parent = Message::factory()->create(['channel_id' => $channel->id]);
        $body = str_repeat('あ', 1000);

        $this->actingAs(User::factory()->create())
            ->post("/channels/{$channel->id}/messages/{$parent->id}/replies", ['reply_body' => $body])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('messages', ['parent_message_id' => $parent->id, 'body' => $body]);
    }

    /**
     * TP-5-02 返信を親に指すURLは、開くことも投稿することもできない。
     *
     * 返信は1段まで（questions.md Q-07）。403ではなく404にする（behavior.md 3章）。
     */
    public function test_返信を親にしたurlは404(): void
    {
        $channel = Channel::factory()->create();
        $parent = Message::factory()->create(['channel_id' => $channel->id]);
        $reply = Message::factory()->replyTo($parent)->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get("/channels/{$channel->id}/messages/{$reply->id}/thread")
            ->assertNotFound();

        $this->actingAs($user)
            ->post("/channels/{$channel->id}/messages/{$reply->id}/replies", ['reply_body' => 'ネストした返信'])
            ->assertNotFound();

        // 2段目は1件も作られない
        $this->assertDatabaseMissing('messages', ['parent_message_id' => $reply->id]);
    }

    /** 見えないプライベートチャンネルには返信できない（403ではなく404。behavior.md 3章） */
    public function test_見られないプライベートチャンネルには返信できない(): void
    {
        $this->seedInitialData();

        $channel = Channel::where('name', '採用プロジェクト')->firstOrFail();
        $parent = Message::where('channel_id', $channel->id)
            ->whereNull('parent_message_id')
            ->firstOrFail();

        $this->actingAs($this->user('takahashi@example.com'))
            ->post("/channels/{$channel->id}/messages/{$parent->id}/replies", ['reply_body' => '入れないはず'])
            ->assertNotFound();

        $this->assertDatabaseMissing('messages', ['body' => '入れないはず']);
    }

    /** 別のチャンネルのメッセージIDを差し込んだ返信も404（判定の順序。behavior.md 3章） */
    public function test_別のチャンネルのメッセージidを差し込むと404(): void
    {
        $channel = Channel::factory()->create();
        $other = Channel::factory()->create();
        $parent = Message::factory()->create(['channel_id' => $other->id]);

        $this->actingAs(User::factory()->create())
            ->post("/channels/{$channel->id}/messages/{$parent->id}/replies", ['reply_body' => '差し込み'])
            ->assertNotFound();

        $this->assertDatabaseMissing('messages', ['body' => '差し込み']);
    }

    /** ログインしていなければ投稿できない（behavior.md 3章） */
    public function test_ログインしていないと返信できない(): void
    {
        $channel = Channel::factory()->create();
        $parent = Message::factory()->create(['channel_id' => $channel->id]);

        $this->post("/channels/{$channel->id}/messages/{$parent->id}/replies", ['reply_body' => '未ログイン'])
            ->assertRedirect('/login');

        $this->assertDatabaseMissing('messages', ['body' => '未ログイン']);
    }
}
