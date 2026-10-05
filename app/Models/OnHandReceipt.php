<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnHandReceipt extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'received_date', 'amount', 'note'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return ['received_date' => 'date', 'amount' => 'decimal:2'];
    }
}
