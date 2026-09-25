<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('earnings', function (Blueprint $table): void {
            $table->id();
            $table->date('earning_date');
            $table->string('source', 100);
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['earning_date', 'id']);
            $table->index(['source', 'earning_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('earnings');
    }
};
