<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ToothModelTooth extends Model
{
    use HasFactory;

    protected $table = 'tbl_tooth_model_teeth';

    protected $fillable = [
        'tooth_model_id',
        'tooth_number',
        'quadrant',
        'status'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function model()
    {
        return $this->belongsTo(ToothModel::class, 'tooth_model_id');
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
