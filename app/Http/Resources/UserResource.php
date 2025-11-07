<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->when($this->email_verified_at, function () {
                return $this->email_verified_at?->toISOString();
            }),
            'institusi' => $this->whenLoaded('institusi', function () {
                return new InstitusiResource($this->institusi);
            }),
            'roles' => $this->when($this->relationLoaded('roles'), function () {
                return $this->roles->pluck('name');
            }),
            'permissions' => $this->when($this->relationLoaded('permissions'), function () {
                return $this->permissions->pluck('name');
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
