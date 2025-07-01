<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'name'      => $this->name,
            'status'      => $this->status,
            'created_at' => isset($this->created_at) ? Carbon::parse($this->created_at)->format('d M Y, h:i a') : null,
            'updated_at' => isset($this->updated_at) ? Carbon::parse($this->updated_at)->format('d M Y, h:i a') : null,
        ];
    }
}
