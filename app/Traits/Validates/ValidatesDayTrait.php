<?php

namespace App\Traits\Validates;

use App\Models\Agenda;
use App\Models\AgendaDaysHours;
use App\Models\Day;
use Carbon\Carbon;

trait ValidatesDayTrait
{
    protected function validateNewDays($agendas, $daysArrayOriginal)
    {
        foreach ($agendas as $key => $agendaIterativa) {
            $validate = $this->validateDatesAgenda($agendaIterativa, $daysArrayOriginal);
            if (!$validate->original['status']) {
                return $validate;
            }
        }
        return $this->successResponse('OK');
    }

    private function validateDatesAgenda($agendaIterativa, $daysArrayOriginal)
    {
        $daysHoursIterativa = AgendaDaysHours::where('idagenda', $agendaIterativa->id)->active()->get();
        $daysHoursEdit      = $daysArrayOriginal;

        $coincidences = [];
        foreach ($daysHoursEdit as $key => $dayHourE) {
            foreach ($daysHoursIterativa as $key => $dayHourI) {
                if ($dayHourE['day'] === $dayHourI->idday) {
                    if ($this->hayCruceDeAgenda($dayHourE, $dayHourI)) {
                        $coincidences[] = [$dayHourE['day']];
                    }
                }
            }
        }

        if (count($coincidences) > 0) {
            $dayNames = Day::whereIn('id', $coincidences)->pluck('name')->toArray();
            $message = implode(', ', $dayNames);

            $agendaId = Agenda::find($agendaIterativa->id)->load('agendaDaysHours.day');

            return $this->errorResponse('Los siguientes días ya están registrados.', 422, [
                'daysCoincidences' => $message,
                'register' => $agendaId
            ]);
        }


        return $this->successResponse('OK');
    }

    private function hayCruceDeAgenda($agendaE, $agendaI): bool
    {
        $agendaIStart = new Carbon($agendaI['start_hour']);
        $agendaIEnd = new Carbon($agendaI['end_hour']);
        $agendaEStart = new Carbon($agendaE['start']);
        $agendaEEnd = new Carbon($agendaE['end']);

        return $agendaEStart < $agendaIEnd &&
            $agendaEEnd > $agendaIStart;
    }
}
