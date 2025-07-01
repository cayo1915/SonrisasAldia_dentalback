<?php
// app/Enums/Status.php
namespace App\Enums;

enum Status: string
{
    case INACTIVE = 'inactive';
    case ACTIVE   = 'active';
    case DELETED  = 'deleted';
}
