<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeAccountantMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $business_name;
    public $rut_bussiness;
    public $user;
    public $email;
    public $name;
    public $password;
    public $url;

    public function __construct($business_name, $rut_bussiness, $user, $email, $name, $password, $url)
    {
        $this->business_name = $business_name;
        $this->rut_bussiness = $rut_bussiness;
        $this->user          = $user;
        $this->name          = $name;
        $this->email         = $email;
        $this->password      = $password;
        $this->url           = $url;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome Accountant Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.sendCredentials',
            with: [
                'business_name' => $this->business_name,
                'rut_bussiness' => $this->rut_bussiness,
                'user'          => $this->user,
                'name'          => $this->name,
                'email'         => $this->email,
                'password'      => $this->password,
                'url'           => $this->url,
            ],
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
