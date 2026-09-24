<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    protected $fillable = ['source_name', 'file_sha256', 'status', 'uploaded_by'];

    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function cashSummaries(): HasMany
    {
        return $this->hasMany(LegacyCashSummary::class);
    }

    public function itemListEntries(): HasMany
    {
        return $this->hasMany(ItemListEntry::class);
    }
}
