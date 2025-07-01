<?php

namespace App\Observers;

use App\Enums\Status;
use App\Models\Reservation;
use App\Models\ClinicalHistory;
use App\Models\ToothChartTooth;
use App\Models\ToothModelTooth;
use App\Traits\ReservaHelpers\HandlesReservationEmails;

class ReservationObserver
{
    use HandlesReservationEmails;

    public function created(Reservation $reservation)
    {
        $exists = ClinicalHistory::where('doctor_id', $reservation->iddoctor)
            ->where('patient_id', $reservation->idpatient)
            ->where('reservation_id', $reservation->id)
            ->active()
            ->exists();

        if ($exists) {
            return;
        }

        $toothModelId = 1;

        $chart = ClinicalHistory::create([
            'doctor_id' => $reservation->iddoctor,
            'patient_id' => $reservation->idpatient,
            'reservation_id' => $reservation->id,
            'tooth_model_id' => $toothModelId,
        ]);

        $modelTeeth = ToothModelTooth::where('tooth_model_id', $toothModelId)
            ->active()
            ->get();

        foreach ($modelTeeth as $tooth) {
            ToothChartTooth::create([
                'clinical_history_id' => $chart->id,
                'tooth_number' => $tooth->tooth_number,
                'is_checked' => false,
                'observation' => null,
                'quadrant' => $tooth->quadrant,
            ]);
        }
    }

    public function updated(Reservation $reservation)
    {
        if ($reservation->wasChanged('status') && $reservation->status === Status::DELETED->value) {
            $this->sendReservationMail($reservation->id, 'cancelled');
            return;
        }

        if ($reservation->wasChanged('is_confirmed') && $reservation->is_confirmed) {
            $this->sendReservationMail($reservation->id, 'confirmed');
            return;
        }

        if ($reservation->wasChanged('is_paid') && $reservation->is_paid) {
            $this->sendReservationMail($reservation->id, 'paid');
            return;
        }

        if ($reservation->wasChanged('is_attended') && $reservation->is_attended) {
            $this->sendReservationMail($reservation->id, 'attended');
            return;
        }

        if ($reservation->wasChanged('is_rescheduled') && $reservation->is_rescheduled) {
            $this->sendReservationMail($reservation->id, 'rescheduled');
            return;
        }
    }
}
