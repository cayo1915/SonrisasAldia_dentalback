<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RRHHResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $charge = $this->relationLoaded('charge') ? $this->whenLoaded('charge') : null;

        return [
            'id'    => $this->id,
            'n_document'     => $this->n_document,
            'name'      => $this->name,
            'surname' => $this->surname,
            'second_surname' => $this->second_surname,
            'birth_date'     => $this->birth_date,
            'age'         => $this->birth_date ? Carbon::parse($this->birth_date)->age : null,
            'sexo'      => $this->sexo,
            'direccion' => $this->direccion,
            'idcharge'     => $this->idcharge,
            $this->mergeWhen($charge, fn() => [
                'charge'          => $charge->name,
            ]),
            'status'      => $this->status,
        ];
    }
}
