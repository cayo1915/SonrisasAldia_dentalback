<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $table = 'tbl_role';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'status'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    # Query scope
    public function scopeActive($query)
    {
        return $query->where('status', Status::ACTIVE->value);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }
}
