<?php

declare(strict_types=1);

namespace App\ChargePoint\Status;

enum ChargePointStatus: string
{
    case AVAILABLE = 'AVAILABLE';
    case OCCUPIED = 'OCCUPIED';
    case OFFLINE = 'OFFLINE';
}
