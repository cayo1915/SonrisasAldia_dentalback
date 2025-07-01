<?php

namespace App\Listeners;

use App\Traits\HasResponse;
use App\Traits\ReservaHelpers\HandlesReservationEmails;

class SendReservationNotification
{
    use HasResponse;
    use HandlesReservationEmails;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        /** @var Reservation $reservation */
        $reservation = $event->reservation;
        $this->sendReservationMail($reservation->id, 'created');
    }
}
