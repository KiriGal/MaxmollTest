<?php

namespace app\Enums;

enum OrderStatus : string
{
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
