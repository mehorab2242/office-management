<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OnHandReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'received_date' => $this->received_date?->toDateString(), 'amount' => $this->amount, 'note' => $this->note, 'created_at' => $this->created_at?->toIso8601String(), 'updated_at' => $this->updated_at?->toIso8601String()];
    }
}
