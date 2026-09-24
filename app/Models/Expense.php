<?php

namespace App\Models;

use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'period_month', 'expense_date', 'category_id', 'description', 'amount',
        'payment_status', 'payment_method', 'reference', 'note', 'created_by', 'updated_by',
        'import_batch_id', 'source_sheet', 'source_row',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function payerAllocations(): HasMany
    {
        return $this->hasMany(ExpensePayerAllocation::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
            'expense_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }
}
