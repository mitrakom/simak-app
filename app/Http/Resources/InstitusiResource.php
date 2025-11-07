<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstitusiResource extends JsonResource
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
            'nama' => $this->nama,
            'slug' => $this->slug,
            'has_feeder_config' => $this->hasFeederConfig(),
            'users_count' => $this->when($this->relationLoaded('users'), function () {
                return $this->users->count();
            }),
            'prodis_count' => $this->when($this->relationLoaded('prodis'), function () {
                return $this->prodis->count();
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
