<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeZone extends Model
{
    use HasFactory;

    protected $table = 'tbl_country_time_zone';

    public $timestamps = false;

    protected $fillable = [
        'description',
        'time_zone',
        'status'
    ];
}
