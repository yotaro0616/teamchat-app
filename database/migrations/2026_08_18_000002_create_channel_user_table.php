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
        // 列は docs/design/data.md 2-3 のとおり。
        // サロゲートIDは持たず (channel_id, user_id) の複合主キーにする。
        // updated_at は持たない決めなので $table->timestamps() は使わず created_at だけを書く（同 2-3 の補足）。
        // channels への外部キーだけ ON DELETE CASCADE。チャンネルの削除は物理削除で、
        // メンバー行を道連れにする（data.md 2-2。メッセージ個別の論理削除とは別物）。
        Schema::create('channel_user', function (Blueprint $table) {
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->timestamp('created_at');

            $table->primary(['channel_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channel_user');
    }
};
