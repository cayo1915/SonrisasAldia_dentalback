<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $notification  = $this->relationLoaded('notification') ? $this->whenLoaded('notification') : null;
        return [
            'id' => $this->id,
            'idnotification' => $this->idnotification,
            'idreceiver' => $this->idreceiver,
            'date_seen' => $this->date_seen,
            'app_view' => $this->app_view,
            'delivery_status' => $this->delivery_status,
            $this->mergeWhen($notification, fn() => [
                'receiver_name' => isset($notification->sender->rrhh->name) ? $notification->sender->rrhh->name : '',
                'receiver_surname' => isset($notification->sender->rrhh->surname) ? $notification->sender->rrhh->surname : '',
                'receiver_second_surname' => isset($notification->sender->rrhh->second_surname) ?  $notification->sender->rrhh->second_surname : '',
                'receiver_n_document' => isset($notification->sender->rrhh->n_document) ? $notification->sender->rrhh->n_document : '',
                'idsender' => $notification->idsender,
                'message_title' => $notification->message_title,
                'message_body' => $notification->message_body,
                'data_json' => json_decode($notification->data_json),
                'date_sent' => $notification->date_sent,
            ]),
            'status' => $this->status
        ];
    }
}
