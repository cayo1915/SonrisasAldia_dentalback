<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $title;
    public $bodyText;
    public $data;

    public function __construct($idreceiver, $title, $bodyText, $data)
    {
        $this->user         = $idreceiver;
        $this->title        = $title;
        $this->bodyText     = $bodyText;
        $this->data         = $data; # Json {url: '/url', idurl: 0, count: x, idnotification: 0 }
    }

    public function broadcastOn()
    {
        return new PrivateChannel('notification' . $this->user);
    }
}
