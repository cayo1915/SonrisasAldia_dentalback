<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */

    public $isConfirmed;
    public $patientFullName;
    public $doctorFullName;
    public $date;
    public $startHour;
    public $endHour;

    public function __construct($reservation, $patientFullName, $doctorFullName, $date, $startHour, $endHour)
    {
        $this->isConfirmed = $reservation->is_confirmed;
        $this->patientFullName = $patientFullName;
        $this->doctorFullName = $doctorFullName;
        $this->date = $date;
        $this->startHour = $startHour;
        $this->endHour = $endHour;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recordatorio de reserva',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.reservations.reminder',
            with: [
                'isConfirmed' => $this->isConfirmed,
                'patientFullName' => $this->patientFullName,
                'doctorFullName' => $this->doctorFullName,
                'date' => $this->date,
                'startHour' => $this->startHour,
                'endHour' => $this->endHour,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
