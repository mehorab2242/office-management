<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyCashSummary extends Model
{
    protected $fillable = [
        'sheet_name', 'period_month', 'received_total',
        'spent_total', 'in_hand_total', 'source_formulas',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
            'received_total' => 'decimal:2',
            'spent_total' => 'decimal:2',
            'in_hand_total' => 'decimal:2',
            'source_formulas' => 'array',
        ];
    }
}
