<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\Translators;

use App\Domain\RouteOptimization\Enums\ServiceType;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\ServiceType as PestRoutesServiceType;

class PestRoutesServiceTypeTranslator
{
    public function toDomain(PestRoutesServiceType $pestRoutesServiceType): ServiceType
    {
        return match (true) {
            $pestRoutesServiceType->isInitial => ServiceType::INITIAL,
            $pestRoutesServiceType->isReservice => ServiceType::RESERVICE,
            default => ServiceType::REGULAR,
        };
    }
}
