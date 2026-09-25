<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EarningResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'earning_date' => $this->earning_date?->toDateString(), 'source' => $this->source, 'description' => $this->description, 'amount' => $this->amount, 'reference' => $this->reference, 'note' => $this->note, 'created_by' => $this->whenLoaded('creator', fn (): ?array => $this->creator ? (new UserResource($this->creator))->resolve() : null), 'created_at' => $this->created_at?->toIso8601String(), 'updated_at' => $this->updated_at?->toIso8601String()];
    }
}
