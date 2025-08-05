<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Google\DataTranslators\Transformers;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Google\Type\LatLng;

class CoordinateTransformer
{
    /**
     * @param Coordinate $coordinate
     *
     * @return LatLng
     */
    public function transform(Coordinate $coordinate): LatLng
    {
        return (new LatLng())
            ->setLatitude($coordinate->getLatitude())
            ->setLongitude($coordinate->getLongitude());
    }
}
