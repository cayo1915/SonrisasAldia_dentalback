<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Charge extends Model
{
    use HasFactory;

    protected $table = 'tbl_charge';

    protected $fillable = [
        'name',
        'description',
        'status'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    # Query scopes
    public function scopeActiveForID($query, $id)
    {
        return $query->where('id', $id)->where('status', Status::ACTIVE->value);
    }

    public function scopeActive($query)
    {
        return $query->where('status', Status::ACTIVE->value);
    }

    # Método para "eliminar" lógicamente un cargo
    public function markAsDeleted()
    {
        return $this->update(['status' => Status::DELETED->value]);
    }

    # Filtros
    public function scopeChargeFilters($query)
    {
        #Filtro de Buscador
        $query->when(
            request('search'),
            fn($query) => $query->where('name', 'LIKE', '%' . request('search') . '%')
        );

        #Filtro de nombre
        $query->when(
            request('name'),
            fn($query) => $query->where('name', 'LIKE', '%' . request('name') . '%')
        );

        #Filtro de RUT
        $query->when(
            request('description'),
            fn($query) => $query->where('description', 'LIKE', '%' . request('description') . '%')
        );

        #Filtro de estados
        $query->when(
            request('status'),
            fn($query) => $query->where('status', request('status'))
        )->when(
            !request('status'),
            fn($query) => $query->where('status', Status::ACTIVE->value)
        );
    }
}
