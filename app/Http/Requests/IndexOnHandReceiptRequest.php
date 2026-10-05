<?php

namespace App\Http\Requests;

use App\Models\OnHandReceipt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class IndexOnHandReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', OnHandReceipt::class);
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'], 'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'date_from' => ['sometimes', 'nullable', 'date_format:Y-m-d'], 'date_to' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ];
    }
}
