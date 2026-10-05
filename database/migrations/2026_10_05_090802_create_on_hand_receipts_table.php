<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('on_hand_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('received_date');
            $table->decimal('amount', 12, 2);
            $table->string('note', 1000)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'received_date', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('on_hand_receipts');
    }
};
