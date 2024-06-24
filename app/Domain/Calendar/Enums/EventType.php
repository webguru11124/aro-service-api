<?php

declare(strict_types=1);

namespace App\Domain\Calendar\Enums;

enum EventType: string
{
    case MEETING = 'meeting';
    case OFFICE_EVENT = 'office-event';
    case TEAM_BUILDING = 'team-building';
    case TRAINING_EVENT = 'training-event';
    case VEHICLE_MAINTENANCE = 'vehicle-maintenance';
    case OTHER = 'other';
}
