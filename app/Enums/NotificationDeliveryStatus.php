<?php

namespace App\Enums;

enum NotificationDeliveryStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case VIEWED = 'viewed';
    case FAILED = 'failed';
}
