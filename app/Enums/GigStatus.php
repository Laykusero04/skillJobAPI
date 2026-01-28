<?php

namespace App\Enums;

enum GigStatus: string
{
    case Open = 'open';
    case Filled = 'filled';
    case Completed = 'completed';
    case Closed = 'closed';
}
