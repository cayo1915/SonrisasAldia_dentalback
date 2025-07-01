<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserReminderSetting extends Model
{
    use HasFactory;

    protected $table = 'tbl_user_reminder_settings';

    public $timestamps = true;

    protected $fillable = [
        'patient_id',
        'type',
        'preset_hours',
        'custom_hours_before',
        'status'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function patient()
    {
        return $this->belongsTo(User::class,  'patient_id');
    }

    # Query scope
    public function scopeActive($query)
    {
        return $query->where('status', Status::ACTIVE->value);
    }
}
