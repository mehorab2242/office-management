<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpensePayerAllocation extends Model
{
    protected $fillable = ['payer_name', 'amount'];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }
}
