<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ToothChartTooth extends Model
{
    use HasFactory;

    protected $table = 'tbl_tooth_chart_teeth';

    protected $fillable = [
        'clinical_history_id',
        'tooth_number',
        'is_checked',
        'observation',
        'quadrant',
        'status'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function chart()
    {
        return $this->belongsTo(ClinicalHistory::class, 'clinical_history_id');
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

    # Método para "eliminar" lógicamente
    public function markAsDeleted()
    {
        return $this->update(['status' => Status::DELETED->value]);
    }
}
