<?php

namespace App\Http\Resources;

use Carbon\Carbon;
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
        $role = $this->relationLoaded('role') ? $this->whenLoaded('role') : null;
        $rrhh = $this->relationLoaded('rrhh') ? $this->whenLoaded('rrhh') : null;
        $agenda = $this->relationLoaded('agenda') ? $this->whenLoaded('agenda') : null;

        return [
            'id'    => $this->id,
            'email'          => $this->email,
            'n_document'     => $this->n_document,
            'status_epn'     => $this->status_notification_push ? true : false,
            // Incluir las marcas de tiempo
            'status'     => $this->status,
            'idrole'    =>  $this->idrole,
            $this->mergeWhen($role, fn() => [
                'role'    => $this->role->name,
            ]),
            'idrrhh'    =>  $this->idrrhh,
            $this->mergeWhen($rrhh, fn() => [
                'name'    => $rrhh->name . ' ' . $rrhh->surname,
                'phone'   => $rrhh->phone,
                'birth_date'    => $rrhh->birth_date,
                'speciality'    => $rrhh->speciality,
                'age'         => $rrhh->birth_date ? Carbon::parse($rrhh->birth_date)->age : null,
                'image'      => isset($rrhh->photo) ? config('common.app_back_url') . '/' . $rrhh->photo : '/assets/img/profiles/avatar-19.jpg'
            ]),
            $this->mergeWhen($agenda, fn () => [
                'agenda' => AgendaResource::collection($agenda)
            ]),
            'created_at' => isset($this->created_at) ? Carbon::parse($this->created_at)->format('d M Y, h:i a') : null,
            'updated_at' => isset($this->updated_at) ? Carbon::parse($this->updated_at)->format('d M Y, h:i a') : null,

        ];
    }
}
