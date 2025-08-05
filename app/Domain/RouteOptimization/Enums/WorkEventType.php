<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Enums;

enum WorkEventType: string
{
    case APPOINTMENT = 'Appointment';
    case START_LOCATION = 'Start Location';
    case END_LOCATION = 'End Location';
    case LUNCH = 'Lunch';
    case TRAVEL = 'Travel';
    case BREAK = 'Break';
    case EXTRA_WORK = 'ExtraWork';
    case WAITING = 'Waiting';
    case RESERVED_TIME = 'ReservedTime';
    case MEETING = 'Meeting';
}
