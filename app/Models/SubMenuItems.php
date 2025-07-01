<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubMenuItems extends Model
{
    use HasFactory;
    protected $table = 'tbl_submenu_items';

    public $timestamps = true;

    protected $fillable = [
        'label',
        'link',
        'icon',
        'submenu',
        'idsrole',
        'showSubRoute',
        'status'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
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
}
