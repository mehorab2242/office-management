<?php

namespace App\Http\Requests;

use App\Models\Expense;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', Expense::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'expense_date' => ['required', 'date_format:Y-m-d'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999.99'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'payment_status' => ['nullable', Rule::in(['paid', 'unpaid', 'pending'])],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:5000'],
            'payer_allocations' => ['sometimes', 'array', 'max:20'],
            'payer_allocations.*.payer_name' => ['required', 'string', 'max:255'],
            'payer_allocations.*.amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $expense = $this->route('expense');

            $allocations = $this->exists('payer_allocations')
                ? $this->input('payer_allocations', [])
                : ($expense?->payerAllocations->toArray() ?? []);
            if (count($allocations) > 0) {
                $sum = '0.00';
                foreach ($allocations as $allocation) {
                    $sum = bcadd($sum, (string) $allocation['amount'], 2);
                }
                if (bccomp($sum, (string) ($this->input('amount') ?? $expense?->amount), 2) !== 0) {
                    $validator->errors()->add('payer_allocations', 'Payer amounts must equal the expense amount.');
                }
            }
        }];
    }
}
