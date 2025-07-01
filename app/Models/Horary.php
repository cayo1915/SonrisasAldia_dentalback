<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Horary extends Model
{
    use HasFactory;

    protected $table = 'tbl_horary';

    public $timestamps = true;

    protected $fillable = [
        'idday',
        'start',
        'end',
        'duration',
        'wait_time',
        'idagenda',
        'status'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * Relación con tbl_day
     */
    public function day()
    {
        return $this->belongsTo(Day::class, 'idday');
    }

    /**
     * Relación con tbl_agenda
     */
    public function agenda()
    {
        return $this->belongsTo(Agenda::class, 'idagenda');
    }

    public function scopeActive($query)
    {
        return $query->where('status', Status::ACTIVE->value);
    }
}
