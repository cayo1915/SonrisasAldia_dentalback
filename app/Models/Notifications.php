<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Notifications extends Model
{
    use HasFactory;
    protected $table = "tbl_notifications";

    protected $primaryKey = "id";

    public $timestamps = true;

    protected $fillable = [
        'idsender',
        'message_title',
        'message_body',
        'data_json',
        'date_sent',
        'status'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idsender');
    }

    public function userrelation(): HasMany
    {
        return $this->hasMany(DetailUserNotifications::class, 'idnotification');
    }

    # Query scope
    public function scopeActive($query)
    {
        return $query->where('status', Status::ACTIVE->value);
    }

    public function scopeActiveForID($query, $id)
    {
        return $query->where('id', $id)->where('status', Status::ACTIVE->value);
    }

    # Método para "eliminar" lógicamente
    public function markAsDeleted()
    {
        return $this->update(['status' => Status::DELETED->value]);
    }



    # Filtros
    public function scopeFilters($query)
    {
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
