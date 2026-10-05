<?php

namespace App\Http\Requests;

use App\Models\OnHandReceipt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreOnHandReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', OnHandReceipt::class);
    }

    public function rules(): array
    {
        return ['received_date' => ['required', 'date_format:Y-m-d'], 'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999.99'], 'note' => ['nullable', 'string', 'max:1000']];
    }
}
