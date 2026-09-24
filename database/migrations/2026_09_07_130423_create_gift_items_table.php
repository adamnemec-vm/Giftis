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
        Schema::create('gift_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wishlist_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('url')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency')->default('Kč');
            $table->string('image_path')->nullable();
            $table->text('description')->nullable();
            $table->string('priority')->default('medium'); // low, medium, high
            $table->string('status')->default('available'); // available, reserved
            $table->string('reserved_by_name')->nullable();
            $table->foreignId('reserved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reservation_token')->nullable();
            $table->timestamp('reserved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_items');
    }
};
