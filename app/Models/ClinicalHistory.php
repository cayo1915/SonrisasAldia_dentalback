<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClinicalHistory extends Model
{
    use HasFactory;

    protected $table = 'tbl_clinical_histories';

    protected $fillable = [
        'tooth_model_id',
        'doctor_id',
        'patient_id',
        'reservation_id',
        'register_date',
        'history_number',
        'document_number',
        'medical_condition',
        'medications',
        'allergies',
        'observation',
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

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function teeth()
    {
        return $this->hasMany(ToothChartTooth::class, 'clinical_history_id');
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

    # Filtros
    public function scopeFilters($query)
    {
        // Filtro de doctor
        $query->when(
            request()->filled('doctor_id'),
            fn($q) => $q->where('doctor_id', request('doctor_id'))
        );

        // Filtro de paciente
        $query->when(
            request()->filled('patient_id'),
            fn($q) => $q->where('patient_id', request('patient_id'))
        );

        // Filtro de estado
        $query->when(
            request()->filled('status'),
            fn($q) => $q->where('status', request('status'))
        )->when(
            !request()->filled('status'),
            fn($q) => $q->where('status', Status::ACTIVE->value)
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

        // Ordenamiento por campo (id o register_date)
        $query->when(
            request()->filled('order_by'),
            function ($q) {
                $orderBy = request('order_by');
                $orderDir = in_array(strtolower(request('order_direction')), ['asc', 'desc'])
                    ? request('order_direction') : 'desc';

                if (in_array($orderBy, ['id', 'register_date'])) {
                    $q->orderBy($orderBy, $orderDir);
                }
            },
            function ($q) {
                // Orden por defecto
                $q->orderBy('register_date', 'desc');
            }
        );
    }
}
