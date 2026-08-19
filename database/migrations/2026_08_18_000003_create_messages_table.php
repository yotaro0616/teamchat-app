<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 列は docs/design/data.md 2-4 のとおり。スレッド返信も同じテーブルに入る
        // （parent_message_id の自己参照。返信を別テーブルにしない、data.md 1章）。
        //
        // deleted_at は Laravel の SoftDeletes とは意味が違うので softDeletes() は使わず手書きする。
        // 「削除済み」は行をクエリから外すためのものではなく、画面をプレースホルダに差し替えるための印で、
        // F-06・F-16 は deleted_at を問わず全件取得するのが正（data.md 2-4 の補足・3章）。
        //
        // 返信が1段までであること（parent が返信自身でないこと）は DB 制約では強制せず、
        // アプリ側でチェックする業務ルールにしてある（data.md 2-4）。
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            // チャンネルの削除は物理削除で、メッセージ・返信を道連れにする（data.md 2-2）。
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('parent_message_id')->nullable()->constrained('messages')->cascadeOnDelete();
            $table->string('body', 1000);
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
