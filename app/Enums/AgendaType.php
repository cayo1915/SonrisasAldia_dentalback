<?php
// app/Enums/Status.php
namespace App\Enums;

enum AgendaType: string
{
    case AVAILABILITY = 'availability';
    case UNAVAILABILITY = 'unavailability';
}
