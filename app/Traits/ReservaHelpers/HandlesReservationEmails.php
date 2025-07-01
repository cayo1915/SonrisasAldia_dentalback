<?php

namespace App\Traits\ReservaHelpers;

use App\Mail\ReservationAttendedMail;
use App\Mail\ReservationCancelledMail;
use App\Mail\ReservationConfirmedMail;
use App\Mail\ReservationCreatedMail;
use App\Mail\ReservationPaidMail;
use App\Mail\ReservationReminderMail;
use App\Mail\ReservationRescheduledMail;
use App\Models\Reservation;
use App\Traits\NotificationHelpers\SendNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

trait HandlesReservationEmails
{
    use SendNotification;

    public function sendReservationMail(int $reservationId, string $type): void
    {
        $reservation = Reservation::with('patient.rrhh', 'doctor.rrhh', 'horary')->find($reservationId);

        if (!$reservation || !$reservation->patient || !$reservation->doctor) {
            return;
        }

        // Paciente
        $patient = $reservation->patient;
        $doctor = $reservation->doctor;
        $horary = $reservation->horary;

        $patientFullName = trim(($patient->rrhh->name ?? '') . ' ' . ($patient->rrhh->surname ?? ''));
        $doctorFullName = trim(($doctor->rrhh->name ?? '') . ' ' . ($doctor->rrhh->surname ?? ''));
        $patientEmail = $patient->rrhh->email ?? null;
        $patientStatusNotificationPush = $reservation->patient->status_notification_push ?? 'inactive';

        if (!$patientEmail) {
            return;
        }

        $startHour = $horary->start ?? '';
        $endHour = $horary->end ?? '';
        $date = $reservation->date;

        $mailable = match ($type) {
            'cancelled'     => new ReservationCancelledMail($patientFullName, $doctorFullName, $date, $startHour, $endHour),
            'confirmed'     => new ReservationConfirmedMail($patientFullName, $doctorFullName, $date, $startHour, $endHour),
            'rescheduled'   => new ReservationRescheduledMail($patientFullName, $doctorFullName, $date, $startHour, $endHour), // si tienes uno
            'paid'          => new ReservationPaidMail($patientFullName, $doctorFullName, $date, $startHour, $endHour), // si tienes uno
            'attended'      => new ReservationAttendedMail($patientFullName, $doctorFullName, $date, $startHour, $endHour), // si tienes uno
            'created'      => new ReservationCreatedMail($patientFullName, $doctorFullName, $date, $startHour, $endHour), // si tienes uno
            'reminder'      => new ReservationReminderMail($reservation, $patientFullName, $doctorFullName, $date, $startHour, $endHour), // si tienes uno
            default => null,
        };

        if ($mailable) {
            Mail::to($patientEmail)->send($mailable);
        }
        if ($patientStatusNotificationPush === 'active') {
            $pushPayload = match ($type) {
                'cancelled' => [
                    'ids_receiver' => [$patient->id],
                    'message_title' => 'Reserva cancelada',
                    'message_body' => "Tu cita con el Dr. $doctorFullName ha sido cancelada.",
                    'data_json'     => ['url' => 'Citas']
                ],
                'confirmed' => [
                    'ids_receiver' => [$patient->id],
                    'message_title' => 'Reserva confirmada',
                    'message_body' => "Tu cita con el Dr. $doctorFullName ha sido confirmada para el $date a las $startHour.",
                    'data_json'     => ['url' => 'Citas']
                ],
                'rescheduled' => [
                    'ids_receiver' => [$patient->id],
                    'message_title' => 'Reserva reprogramada',
                    'message_body' => "Tu cita fue reprogramada con el Dr. $doctorFullName para el $date a las $startHour.",
                    'data_json'     => ['url' => 'Citas']
                ],
                'paid' => [
                    'ids_receiver' => [$patient->id],
                    'message_title' => 'Reserva pagada',
                    'message_body' => "Tu cita con el Dr. $doctorFullName ha sido pagada correctamente.",
                    'data_json'     => ['url' => 'Citas']
                ],
                'attended' => [
                    'ids_receiver' => [$patient->id],
                    'message_title' => 'Cita completada',
                    'message_body' => "Gracias por asistir a tu cita con el Dr. $doctorFullName.",
                    'data_json'     => ['url' => 'Citas']
                ],
                'created' => [
                    'ids_receiver' => [$patient->id],
                    'message_title' => 'Cita creada',
                    'message_body' => "Has agendado una cita con el Dr. $doctorFullName para el $date a las $startHour.",
                    'data_json'     => ['url' => 'Citas']
                ],
                default => null,
            };
            if ($pushPayload) {
                try {
                    $this->sendNotification($pushPayload);
                } catch (\Throwable $e) {
                    Log::error("Error al enviar notificación: " . $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        }
    }
}
