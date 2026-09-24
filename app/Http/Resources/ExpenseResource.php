<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'period_month' => $this->period_month?->toDateString(),
            'expense_date' => $this->expense_date?->toDateString(),
            'description' => $this->description,
            'amount' => $this->amount,
            'category' => $this->whenLoaded('category', fn (): ?array => $this->category
                ? (new CategoryResource($this->category))->resolve()
                : null),
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'reference' => $this->reference,
            'note' => $this->note,
            'created_by' => $this->whenLoaded('creator', fn (): ?array => $this->creator
                ? (new UserResource($this->creator))->resolve()
                : null),
            'payer_allocations' => $this->whenLoaded('payerAllocations', fn (): array => $this->payerAllocations
                ->map(fn ($allocation): array => [
                    'payer_name' => $allocation->payer_name,
                    'amount' => $allocation->amount,
                ])->all()),
        ];
    }
}
