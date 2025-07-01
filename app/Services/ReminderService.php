<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationReminder;
use App\Models\UserReminderSetting;
use App\Traits\HasResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReminderService
{
    use HasResponse;

    public function createOrUpdateReminderConfig($params)
    {
        DB::beginTransaction();
        try {
            $patientId = auth()->user()->id;

            $existingConfigs = UserReminderSetting::where('patient_id', $patientId)->active()->get();

            if (isset($params['preset'])) {
                $presetData = [
                    'patient_id' => $patientId,
                    'type' => 'preset',
                    'preset_hours' => json_encode($params['preset']['preset_hours']),
                ];

                $existingPreset = $existingConfigs->where('type', 'preset')->first();
                if ($existingPreset) {
                    $existingPreset->update($presetData);
                } else {
                    UserReminderSetting::create($presetData);
                }
            }

            if (isset($params['personalized'])) {
                $personalizedData = [
                    'patient_id' => $patientId,
                    'type' => 'personalized',
                    'custom_hours_before' => $params['personalized']['custom_hours_before'],
                ];

                $existingPersonalized = $existingConfigs->where('type', 'personalized')->first();
                if ($existingPersonalized) {
                    $existingPersonalized->update($personalizedData);
                } else {
                    UserReminderSetting::create($personalizedData);
                }
            }

            DB::commit();
            return $this->successResponse('Configuración de recordatorio guardada con éxito', 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error al guardar configuración de recordatorio: " . $th->getMessage());
            throw $th;
        }
    }


    public function generateReservationReminders($reservationId)
    {
        try {
            $reservation = Reservation::with('patient.rrhh', 'horary')->find($reservationId);

            if (!$reservation) {
                return $this->errorResponse('Reserva no encontrada', 404);
            }

            if (!$reservation->patient) {
                return $this->errorResponse('La reserva no tiene paciente asignado', 422);
            }

            if (!$reservation->horary) {
                return $this->errorResponse('La reserva no tiene horario asignado', 422);
            }

            $patient = $reservation->patient;
            $horary = $reservation->horary;

            // Fecha y hora de la reserva
            $date = $reservation->date ?? now()->toDateString();
            $startHour = $horary->start ?? '00:00:00';

            // Combinar fecha y hora de inicio
            $startTime = Carbon::parse("{$date} {$startHour}");

            $patientId = $patient->id;
            $settings = UserReminderSetting::where('patient_id', $patientId)->active()->get();

            foreach ($settings as $setting) {
                if ($setting->type === 'preset') {
                    $presetHours = json_decode($setting->preset_hours, true); // [1, 12, 24]

                    foreach ($presetHours as $hoursBefore) {
                        $reminderTime = $startTime->copy()->subHours($hoursBefore);

                        if ($reminderTime->isFuture()) {
                            ReservationReminder::create([
                                'reservation_id' => $reservation->id,
                                'reminder_time'  => $reminderTime,
                            ]);
                        }
                    }
                }

                if ($setting->type === 'personalized') {
                    $hoursBefore = $setting->custom_hours_before;
                    $reminderTime = $startTime->copy()->subHours($hoursBefore);

                    if ($reminderTime->isFuture()) {
                        ReservationReminder::create([
                            'reservation_id' => $reservation->id,
                            'reminder_time'  => $reminderTime,
                        ]);
                    }
                }
            }

            // Previsualización de la configuración de recordatorios
            $previewHorary = [
                'reservation_id' => $reservation->id,
                'reminders_count' => $settings->count(),
            ];

            return $this->successResponse('Reminder creado con éxito', $previewHorary);
        } catch (\Throwable $th) {
            Log::error('Error al generar recordatorios: ' . $th->getMessage());
            return $this->errorResponse('Ocurrió un error al generar los recordatorios', 500);
        }
    }
}
