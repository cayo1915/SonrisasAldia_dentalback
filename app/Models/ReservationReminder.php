<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationReminder extends Model
{
    use HasFactory;

    protected $table = 'tbl_reservation_reminders';

    public $timestamps = true;

    protected $fillable = [
        'reservation_id',
        'reminder_time',
        'sent',
        'status'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function reservation()
    {
        // 1 reserva - muchos recordatorios
        // 1 recordatorio -  1 reserva
        return $this->belongsTo(Reservation::class, 'reservation_id'); //reservacion reminder pertenece a Rservation
    }

    # Query scope
    public function scopeActive($query)
    {
        return $query->where('status', Status::ACTIVE->value);
    }
}
