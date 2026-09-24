<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Gate;

class UpdateExpenseRequest extends StoreExpenseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('expense'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['expense_date'] = ['sometimes', 'nullable', 'date_format:Y-m-d'];
        $rules['description'] = ['sometimes', 'required', 'string', 'max:255'];
        $rules['amount'] = ['sometimes', 'required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999.99'];

        return $rules;
    }
}
