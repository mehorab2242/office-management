<?php

namespace App\Services;

use App\Models\Earning;
use Illuminate\Database\Eloquent\Builder;

class EarningQuery
{
    public function build(array $filters): Builder
    {
        $query = Earning::query()->with('creator');
        if ($filters['search'] ?? null) {
            $query->where(fn (Builder $q): Builder => $q->where('description', 'like', '%'.$filters['search'].'%')->orWhere('reference', 'like', '%'.$filters['search'].'%'));
        }
        foreach (['source' => 'source', 'date_from' => 'earning_date', 'date_to' => 'earning_date', 'amount_min' => 'amount', 'amount_max' => 'amount'] as $filter => $column) {
            if (! isset($filters[$filter])) {
                continue;
            }
            $operator = str_ends_with($filter, '_from') ? '>=' : (str_ends_with($filter, '_to') ? '<=' : (str_ends_with($filter, '_min') ? '>=' : (str_ends_with($filter, '_max') ? '<=' : '=')));
            $query->where($column, $operator, $filters[$filter]);
        }

        return $query->orderByDesc('earning_date')->orderByDesc('id');
    }
}
