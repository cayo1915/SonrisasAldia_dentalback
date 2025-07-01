<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Reservation extends Model
{
    use HasFactory;

    protected $table = 'tbl_reservation';
    public $timestamps = true;
    protected $fillable = [
        'date',
        'total',
        'idpatient',
        'iddoctor',
        'idhorary',
        'type_modality',
        'is_confirmed',
        'is_paid',
        'is_attended',
        'is_rescheduled',
        'rescheduled_at',
        'status',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];



    # Relaciones
    public function patient()
    {
        return $this->belongsTo(User::class, 'idpatient');
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'iddoctor');
    }

    public function horary()
    {
        return $this->belongsTo(Horary::class, 'idhorary');
    }

    public function saleOrders(): MorphMany
    {
        return $this->morphMany(SaleOrder::class, 'order');
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

    # Filtros
    public function scopeFilters($query)
    {
        // Filtro de doctor
        $query->when(
            request()->filled('iddoctor'),
            fn($q) => $q->where('iddoctor', request('iddoctor'))
        );

        // Filtro de paciente
        $query->when(
            request()->filled('idpatient'),
            fn($q) => $q->where('idpatient', request('idpatient'))
        );

        // Filtro de estado
        $query->when(
            request()->filled('status'),
            fn($q) => $q->where('status', request('status'))
        )->when(
            !request()->filled('status'),
            fn($q) => $q->where('status', Status::ACTIVE->value)
        );

        // Filtro por confirmación
        $query->when(
            request()->has('is_confirmed'),
            fn($q) => $q->where('is_confirmed', request('is_confirmed'))
        );

        // Filtro por pago
        $query->when(
            request()->has('is_paid'),
            fn($q) => $q->where('is_paid', request('is_paid'))
        );

        // Filtro por asistencia
        $query->when(
            request()->has('is_attended'),
            fn($q) => $q->where('is_attended', request('is_attended'))
        );

        // Filtro por reprogramación
        $query->when(
            request()->has('is_rescheduled'),
            fn($q) => $q->where('is_rescheduled', request('is_rescheduled'))
        );

        // Filtro por fecha desde un día específico en adelante
        $query->when(
            request()->filled('date'),
            fn($q) => $q->whereDate('date', '>=', request('date'))
        );

        // Búsqueda por name, surname o n_document del paciente (vía patient.rrhh)
        $query->when(request()->filled('search'), function ($q) {
            $q->whereHas('patient.rrhh', function ($subQuery) {
                $search = request('search');
                $subQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('surname', 'like', '%' . $search . '%')
                    ->orWhere('n_document', 'like', '%' . $search . '%');
            });
        });

        $query->orderBy('date', 'asc');
    }
}
