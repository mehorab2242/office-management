<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    protected $fillable = ['original_name', 'storage_path', 'mime_type', 'file_size', 'uploaded_by'];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}
