<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $patientFullName;
    public $doctorFullName;
    public $date;
    public $startHour;
    public $endHour;

    public function __construct($patientFullName, $doctorFullName, $date, $startHour, $endHour)
    {
        $this->patientFullName = $patientFullName;
        $this->doctorFullName = $doctorFullName;
        $this->date = $date;
        $this->startHour = $startHour;
        $this->endHour = $endHour;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva reserva creada'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reservations.created',
            with: [
                'patientFullName' => $this->patientFullName,
                'doctorFullName' => $this->doctorFullName,
                'date' => $this->date,
                'startHour' => $this->startHour,
                'endHour' => $this->endHour,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
