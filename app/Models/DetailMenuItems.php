<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailMenuItems extends Model
{
    use HasFactory;

    protected $table = 'tbl_detail_menu_items';
    public $timestamps = true;
    protected $fillable = [
        'id_menu_items',
        'id_submenu_items',
        'id_submenu_items2',
        'id_submenu_items3',
        'status'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function menuItems(): BelongsTo
    {
        return $this->belongsTo(MenuItems::class, 'id_menu_items');
    }

    public function subMenuItems(): BelongsTo
    {
        return $this->belongsTo(SubMenuItems::class, 'id_submenu_items');
    }

    public function subMenuItems2(): BelongsTo
    {
        return $this->belongsTo(SubMenuItems::class, 'id_submenu_items2');
    }

    public function subMenuItems3(): BelongsTo
    {
        return $this->belongsTo(SubMenuItems::class, 'id_submenu_items3');
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
}
