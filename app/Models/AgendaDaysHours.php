<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgendaDaysHours extends Model
{
    use HasFactory;

    protected $table = 'tbl_agenda_days_hours';

    protected $fillable = [
        'idagenda',
        'idday',
        'start_hour',
        'end_hour',
        'status'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    /**
     * Relación con tbl_agenda
     */
    public function agenda()
    {
        return $this->belongsTo(Agenda::class, 'idagenda');
    }

    /**
     * Relación con tbl_day
     */
    public function day()
    {
        return $this->belongsTo(Day::class, 'idday');
    }

    public function scopeActive($query)
    {
        return $query->where('status', Status::ACTIVE->value);
    }
}
