<?php

namespace App\Models;

use App\Enums\AgendaType;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agenda extends Model
{
    use HasFactory;

    protected $table = 'tbl_agenda';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'break_start',
        'break_end',
        'modality',
        'duration_hour',
        'wait_time_hour',
        'iddoctor',
        'agenda_type',
        'full_day',
        'start_date_block',
        'end_date_block',
        'start_hour_block',
        'end_hour_block',
        'color_agenda',
        'comment',
        'status'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function doctor()
    {
        return $this->belongsTo(User::class, 'iddoctor');
    }

    public function horary(): HasMany
    {
        return $this->hasMany(Horary::class, 'idagenda');
    }

    public function agendaDaysHours()
    {
        return $this->hasMany(AgendaDaysHours::class, 'idagenda')->where('status', Status::ACTIVE->value);
    }

    # Query scopes
    public function scopeActiveForID($query, $id)
    {
        return $query->where('id', $id)->where('status', Status::ACTIVE->value);
    }

    public function scopeActive($query)
    {
        return $query->where('status', Status::ACTIVE->value);
    }

    public function scopeAvailability($query)
    {
        return $query->where('agenda_type', AgendaType::AVAILABILITY->value);
    }

    public function scopeUnAvailability($query)
    {
        return $query->where('agenda_type', AgendaType::UNAVAILABILITY->value);
    }
}
