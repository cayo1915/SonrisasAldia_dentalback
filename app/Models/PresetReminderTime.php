<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresetReminderTime extends Model
{
    use HasFactory;

    protected $table = 'tbl_preset_reminder_times';

    public $timestamps = true;

    protected $fillable = [
        'hours',
        'status'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    # Query scope
    public function scopeActive($query)
    {
        return $query->where('status', Status::ACTIVE->value);
    }
}
