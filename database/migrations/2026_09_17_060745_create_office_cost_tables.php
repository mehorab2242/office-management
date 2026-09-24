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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('source_name');
            $table->char('file_sha256', 64);
            $table->string('status', 30)->default('preview');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('file_sha256');
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('period_month');
            $table->date('expense_date')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->string('payment_status', 30)->nullable();
            $table->string('payment_method', 100)->nullable();
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('import_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_sheet')->nullable();
            $table->unsignedInteger('source_row')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['period_month', 'id']);
            $table->index(['expense_date', 'id']);
            $table->index(['category_id', 'period_month']);
            $table->index(['payment_status', 'period_month']);
            $table->index(['created_by', 'period_month']);
            $table->unique(['import_batch_id', 'source_sheet', 'source_row']);
        });

        Schema::create('expense_payer_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
            $table->string('payer_name');
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->string('sheet_name');
            $table->unsignedInteger('row_number');
            $table->json('raw_values');
            $table->json('raw_formulas')->nullable();
            $table->json('validation_errors')->nullable();
            $table->string('status', 30)->default('pending');
            $table->foreignId('expense_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['import_batch_id', 'sheet_name', 'row_number']);
        });

        Schema::create('legacy_cash_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->string('sheet_name');
            $table->date('period_month');
            $table->decimal('received_total', 12, 2)->nullable();
            $table->decimal('spent_total', 12, 2)->nullable();
            $table->decimal('in_hand_total', 12, 2)->nullable();
            $table->json('source_formulas')->nullable();
            $table->timestamps();
            $table->unique(['import_batch_id', 'sheet_name']);
        });

        Schema::create('item_list_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');
            $table->string('quantity_raw')->nullable();
            $table->unsignedInteger('source_row');
            $table->timestamps();
            $table->unique(['import_batch_id', 'source_row']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 60);
            $table->string('subject_type', 100);
            $table->unsignedBigInteger('subject_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['subject_type', 'subject_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('item_list_entries');
        Schema::dropIfExists('legacy_cash_summaries');
        Schema::dropIfExists('import_rows');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('expense_payer_allocations');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('import_batches');
        Schema::dropIfExists('categories');
    }
};
