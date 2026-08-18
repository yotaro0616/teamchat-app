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
        // 列は docs/design/data.md 2-2 のとおり。
        // type の変更不可（作成後に公開範囲を変えられない）は、編集フォームに項目を出さないことで
        // 担保する決めなので、DB 制約としては課さない（同 2-2）。
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('description', 200)->nullable();
            $table->enum('type', ['public', 'private']);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
