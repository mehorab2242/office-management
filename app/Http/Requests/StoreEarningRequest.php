<?php

namespace App\Http\Requests;

use App\Models\Earning;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreEarningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Earning::class);
    }

    public function rules(): array
    {
        return ['earning_date' => ['required', 'date_format:Y-m-d'], 'source' => ['required', 'string', 'max:100'], 'description' => ['required', 'string', 'max:255'], 'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999.99'], 'reference' => ['nullable', 'string', 'max:255'], 'note' => ['nullable', 'string', 'max:5000']];
    }
}
