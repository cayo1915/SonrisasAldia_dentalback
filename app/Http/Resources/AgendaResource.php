<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgendaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $agendaDays = $this->relationLoaded('agendaDaysHours') ? $this->agendaDaysHours : collect();
        $daysArray = [];
        foreach ($agendaDays as $key => $day) {
            $daysArray[] = [
                'id' => $day['idday'],
                'start_hour' => $day['start_hour'],
                'end_hour' => $day['end_hour'],
                'name' => $day['day']['name']
            ];
        }

        return [
            'id'                =>  $this->id,
            'modality'          =>  json_decode($this->modality),
            'name'              =>  $this->name,
            'days'              =>  $daysArray,
            'duration_hour'     =>  $this->duration_hour,
            'wait_time_hour'    =>  $this->wait_time_hour,
            'iddoctor'          =>  $this->iddoctor,
            'comment'           =>  $this->comment,
            'status'            =>  $this->status,
            'break_start'       =>  $this->break_start,
            'break_end'         =>  $this->break_end,
        ];
    }
}
