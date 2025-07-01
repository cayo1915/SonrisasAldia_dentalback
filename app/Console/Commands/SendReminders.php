<?php

namespace App\Console\Commands;

use App\Models\ReservationReminder;
use App\Traits\ReservaHelpers\HandlesReservationEmails;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendReminders extends Command
{
    use HandlesReservationEmails;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar recordatorios de reservas pendientes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $reservations = ReservationReminder::where('sent', false)
            ->where('reminder_time', '<=', now())
            ->active()
            ->get();

        foreach ($reservations as $reminder) {
            try {
                $reservation = $reminder->reservation;
                $this->sendReservationMail($reservation->id, 'reminder');
                $reminder->update(['sent' => true]);
            } catch (\Throwable $e) {
                Log::error("Error enviando recordatorio para ReservationReminder ID {$reminder->id}: " . $e->getMessage());
            }
        }
    }
}
