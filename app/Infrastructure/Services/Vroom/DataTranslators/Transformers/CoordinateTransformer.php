<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\DataTranslators\Transformers;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Infrastructure\Services\Vroom\DTO\VroomCoordinate;

class CoordinateTransformer
{
    /**
     * @param Coordinate $coordinate
     *
     * @return VroomCoordinate
     */
    public function transform(Coordinate $coordinate): VroomCoordinate
    {
        return new VroomCoordinate(
            $coordinate->getLatitude(),
            $coordinate->getLongitude()
        );
    }
}
