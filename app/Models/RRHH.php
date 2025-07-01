<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RRHH extends Model
{
    use HasFactory;
    protected $table = 'tbl_rrhh';

    public $timestamps = true;

    protected $primayKey = 'id';

    protected $fillable = [
        'n_document',
        'name',
        'surname',
        'birth_date',
        'email',
        'phone',
        'photo',
        'speciality',
        'idcharge',
        'status'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function charge()
    {
        return $this->belongsTo(Charge::class, 'idcharge');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'idrrhh');
    }

    # Query scopes
    public function scopeActiveForID($query, $id)
    {
        return $query->where('id', $id)->where('status', Status::ACTIVE->value);
    }

    public function scopeActiveForDocument($query, $document)
    {
        return $query->where('n_document', $document)->where('status', Status::ACTIVE->value);
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


    # Filtros
    public function scopeFilters($query)
    {
        #Filtro de Buscador
        $query->when(
            request('search'),
            function ($query) {
                $term = request('search');
                $query->where('n_document', 'like',  '%' . $term . '%')
                    ->orWhere('name', 'like',  '%' . $term . '%')
                    ->orWhere('surname', 'like',  '%' . $term . '%');
            }
        );

        #Filtro de cargo
        $query->when(
            request('idcharge'),
            fn($query) => $query->where('idcharge', request('idcharge'))
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
