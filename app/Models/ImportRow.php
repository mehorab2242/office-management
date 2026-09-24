<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRow extends Model
{
    protected $fillable = [
        'sheet_name', 'row_number', 'raw_values', 'raw_formulas',
        'validation_errors', 'status', 'expense_id',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    protected function casts(): array
    {
        return [
            'raw_values' => 'array',
            'raw_formulas' => 'array',
            'validation_errors' => 'array',
        ];
    }
}
