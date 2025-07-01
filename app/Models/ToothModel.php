<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ToothModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_tooth_models';

    protected $fillable = [
        'name',
        'status'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function teeth()
    {
        return $this->hasMany(ToothModelTooth::class, 'tooth_model_id');
    }

    public function charts()
    {
        return $this->hasMany(ClinicalHistory::class, 'tooth_model_id');
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
