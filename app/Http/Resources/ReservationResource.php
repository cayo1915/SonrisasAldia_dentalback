<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray($request)
    {
        $patient     = $this->relationLoaded('patient') ? $this->whenLoaded('patient') : null;
        $doctor = $this->relationLoaded('doctor') ? $this->whenLoaded('doctor') : null;
        $horary = $this->relationLoaded('horary') ? $this->whenLoaded('horary') : null;

        return [
            'id'    => $this->id,
            'date'  => $this->date,
            'total' => $this->total,
            'idpatient'       => $this->idpatient,
            $this->mergeWhen($patient, fn() => [
                'name_patient'  => $patient->rrhh->name ?? '',
                'surname_patient'  => $patient->rrhh->surname ?? '',
                'n_document_patient'  => $patient->rrhh->n_document ?? '',
            ]),
            'iddoctor'       => $this->iddoctor,
            $this->mergeWhen($doctor, fn() => [
                'name_doctor'  => $doctor->rrhh->name ?? '',
                'surname_doctor'  => $doctor->rrhh->surname ?? '',
                'n_document_doctor'  => $doctor->rrhh->n_document ?? '',
                'speciality_doctor'  => $doctor->rrhh->speciality ?? '',
            ]),
            $this->mergeWhen($horary, fn() => [
                'idhorary'  => $horary->id,
                'start'     => date("H:i", strtotime($horary->start)),
                'end'       => date("H:i", strtotime($horary->end)),
            ]),
            'is_paid' => $this->is_paid,
            'is_attended' => $this->is_attended,
            'is_confirmed' => $this->is_confirmed,
            'status' => $this->status,
        ];
    }
}
