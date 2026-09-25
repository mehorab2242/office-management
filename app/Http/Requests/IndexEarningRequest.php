<?php

namespace App\Http\Requests;

use App\Models\Earning;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class IndexEarningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', Earning::class);
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'], 'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'], 'source' => ['sometimes', 'nullable', 'string', 'max:100'],
            'date_from' => ['sometimes', 'nullable', 'date_format:Y-m-d'], 'date_to' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'amount_min' => ['sometimes', 'numeric', 'min:0'], 'amount_max' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
