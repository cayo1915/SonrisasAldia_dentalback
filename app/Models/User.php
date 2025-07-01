<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = 'tbl_user';

    public $timestamps = true;

    protected $fillable = [
        'idrrhh',
        'idrole',
        'n_document',
        'email',
        'password',
        'encrypted_password',
        'token_epn',
        'status_notification_push',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_at',
        'updated_at',
        'password',
        'encrypted_password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        // 'password' => 'hashed',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    #relations

    public function rrhh(): BelongsTo
    {
        return $this->belongsTo(RRHH::class, 'idrrhh');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'idrole');
    }

    public function agenda(): HasMany
    {
        return $this->hasMany(Agenda::class, 'iddoctor');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'idpatient');
    }

    public function clinicalHistories()
    {
        return $this->hasMany(ClinicalHistory::class, 'patient_id');
    }

    # Query scope
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

    public function scopeActiveNotification($query)
    {
        return $query->where('status_notification_push', Status::ACTIVE->value);
    }

    public function scopeVerificationEmail($query, $id)
    {
        return $query->where('id', $id)->whereNotNull('email_verified_at')->where('status', Status::ACTIVE->value);
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
                    ->orWhere('email', 'like',  '%' . $term . '%');
            }
        );

        #Filtro de cargo
        $query->when(
            request('idrole'),
            fn($query) => $query->where('idrole', request('idrole'))
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
