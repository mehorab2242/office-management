<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Gate;

class UpdateOnHandReceiptRequest extends StoreOnHandReceiptRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('on_hand_receipt'));
    }

    public function rules(): array
    {
        return collect(parent::rules())->map(fn (array $rules): array => array_merge(['sometimes'], $rules))->all();
    }
}
