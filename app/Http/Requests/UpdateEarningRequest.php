<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Gate;

class UpdateEarningRequest extends StoreEarningRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('earning'));
    }

    public function rules(): array
    {
        return collect(parent::rules())->map(fn (array $rules): array => array_merge(['sometimes'], $rules))->all();
    }
}
