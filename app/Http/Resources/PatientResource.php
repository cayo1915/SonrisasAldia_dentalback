<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $rrhh = $this->relationLoaded('rrhh') ? $this->whenLoaded('rrhh') : null;
        return [
            'id'    => $this->id,
            'email'          => $this->email,
            'n_document'     => $this->n_document,
            'status_epn'     => $this->status_notification_push ? true : false,
            'status'        => $this->status,
            'idrole'        =>  $this->idrole,
            'idrrhh'        =>  $this->idrrhh,
            $this->mergeWhen($rrhh, fn() => [
                'name'    => $rrhh->name . '-' . $rrhh->surname,
                'birth_date'    => $rrhh->birth_date,
                'age'         => $rrhh->birth_date ? Carbon::parse($rrhh->birth_date)->age : null,
                'image'      => isset($rrhh->photo) ? config('common.app_back_url') . '/' . $rrhh->photo : '/assets/img/profiles/avatar-19.jpg'
            ]),
            "reservations" => $this->reservations,
            "clinical_histories" => $this->clinicalHistories,
        ];
    }
}
