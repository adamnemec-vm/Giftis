<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_item_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_name');
            $table->string('commenter_token')->nullable();
            $table->text('body');
            $table->timestamps();

            $table->index('gift_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_item_comments');
    }
};
