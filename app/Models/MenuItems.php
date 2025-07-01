<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItems extends Model
{
    use HasFactory;

    protected $table = 'tbl_menu_items';

    public $timestamps = true;

    protected $fillable = [
        'label',
        'submenuOpen',
        'showSubRoute',
        'submenuHdr',
        'idsrole',
        'status'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function detailMenuItems(): HasMany
    {
        return $this->hasMany(DetailMenuItems::class, 'id_menu_items');
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
