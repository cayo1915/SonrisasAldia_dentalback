<?php

namespace App\Models;

use App\Enums\NotificationDeliveryStatus;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DetailUserNotifications extends Model
{
    use HasFactory;
    protected $table = "tbl_detail_user_notifications";

    protected $primaryKey = "id";

    protected $fillable = [
        'idreceiver',
        'idnotification',
        'date_seen',
        'delivery_status',
        'app_view',
        'status'
    ];

    public function receiver()
    {
        return $this->belongsTo(User::class, 'idreceiver');
    }

    public function notification()
    {
        return $this->belongsTo(Notifications::class, 'idnotification');
    }

    # Query scope
    public function scopeActive($query)
    {
        return $query->where('status', Status::ACTIVE->value);
    }

    public function scopeNotificationSend($query)
    {
        return $query->where('delivery_status', NotificationDeliveryStatus::SENT->value);
    }


    public function markAsViewed()
    {
        return $this->update([
            'date_seen' => now(),
            'delivery_status' => NotificationDeliveryStatus::VIEWED->value
        ]);
    }


    # Filtros
    public function scopeFilters($query)
    {
        #Filtro de vistos o no vistos
        $query->when(
            request('view'),
            fn($query) => $query->where('delivery_status', request('view'))
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
