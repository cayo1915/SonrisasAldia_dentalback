<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class SaleOrder
 *
 * @property int $id
 * @property string $order_code
 * @property float $global_discount
 * @property float $total_amount
 * @property string $order_type
 * @property string $order_id
 * @property ?string $detail
 * @property ?string $extra_details
 * @property string $payment_status
 */
class SaleOrder extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_sale_orders';

    protected $fillable = [
        'patient_id',
        'order_code',
        'global_discount',
        'total_amount',
        'order_type',
        'order_id',
        'detail',
        'extra_details',
        'payment_status'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function scopeOnlyReservations($query)
    {
        return $query->where('order_type', Reservation::class);
    }

    public function order(): MorphTo
    {
        return $this->morphTo();
    }
}
