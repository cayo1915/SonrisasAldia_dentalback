<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClinicalHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $teeth = $this->relationLoaded('teeth') ? $this->whenLoaded('teeth') : null;
        $doctor = $this->relationLoaded('doctor') ? $this->whenLoaded('doctor') : null;
        $patient     = $this->relationLoaded('patient') ? $this->whenLoaded('patient') : null;

        return [
            'id' => $this->id,
            'tooth_model_id' => $this->tooth_model_id,
            'doctor_id' => $this->doctor_id,
            $this->mergeWhen($doctor, fn() => [
                'name_doctor'  => $doctor->rrhh->name ?? '',
                'surname_doctor'  => $doctor->rrhh->surname ?? '',
                'n_document_doctor'  => $doctor->rrhh->n_document ?? '',
            ]),
            'patient_id' => $this->patient_id,
            $this->mergeWhen($patient, fn() => [
                'name_patient'  => $patient->rrhh->name ?? '',
                'surname_patient'  => $patient->rrhh->surname ?? '',
                'n_document_patient'  => $patient->rrhh->n_document ?? '',
            ]),
            'reservation_id' => $this->reservation_id,
            'register_date' => $this->register_date,
            'history_number' => $this->history_number,
            'document_number' => $this->document_number,
            'medical_condition' => $this->medical_condition,
            'allergies' => $this->allergies,
            'medications' => $this->medications,
            'observation' => $this->observation,
            'teeth' => $teeth,
            'status'      => $this->status,
        ];
    }
}
