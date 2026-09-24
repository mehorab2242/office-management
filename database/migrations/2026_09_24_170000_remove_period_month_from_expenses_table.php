<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(Schema::getIndexes('expenses'))->pluck('name');
        $foreignKeys = collect(Schema::getForeignKeys('expenses'))->pluck('name');

        Schema::table('expenses', function (Blueprint $table) use ($indexes, $foreignKeys): void {
            if ($foreignKeys->contains('expenses_category_id_foreign')) {
                $table->dropForeign('expenses_category_id_foreign');
            }
            if ($foreignKeys->contains('expenses_created_by_foreign')) {
                $table->dropForeign('expenses_created_by_foreign');
            }
            foreach ([
                'expenses_period_month_id_index',
                'expenses_category_id_period_month_index',
                'expenses_payment_status_period_month_index',
                'expenses_created_by_period_month_index',
            ] as $index) {
                if ($indexes->contains($index)) {
                    $table->dropIndex($index);
                }
            }
            $table->dropColumn('period_month');
            $table->foreign('category_id')->references('id')->on('categories')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->date('period_month')->nullable()->after('id');
            $table->index(['period_month', 'id']);
            $table->index(['category_id', 'period_month']);
            $table->index(['payment_status', 'period_month']);
            $table->index(['created_by', 'period_month']);
        });
    }
};
