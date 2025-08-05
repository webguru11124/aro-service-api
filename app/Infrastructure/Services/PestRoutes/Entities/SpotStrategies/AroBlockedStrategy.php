<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes\Entities\SpotStrategies;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Infrastructure\Services\PestRoutes\Entities\Spot;
use App\Infrastructure\Services\PestRoutes\Enums\SpotType;

class AroBlockedStrategy implements SpotStrategy
{
    /**
     * @return SpotType
     */
    public function getSpotType(): SpotType
    {
        return SpotType::ARO_BLOCKED;
    }

    /**
     * @param Spot $spot
     *
     * @return string
     */
    public function getWindow(Spot $spot): string
    {
        $blockReason = $this->getBlockReasonObject($spot);

        return ((int) $blockReason->time[1] - 2) <= 12 ? 'AM' : 'PM';
    }

    /**
     * @return bool
     */
    public function isAroSpot(): bool
    {
        return true;
    }

    /**
     * @param Spot $spot
     *
     * @return Coordinate|null
     */
    public function getPreviousCoordinate(Spot $spot): Coordinate|null
    {
        $blockReason = $this->getBlockReasonObject($spot);

        if (empty($blockReason->from) || empty($blockReason->to)) {
            return null;
        }

        return new Coordinate($blockReason->from[1], $blockReason->from[0]);
    }

    /**
     * @param Spot $spot
     *
     * @return Coordinate|null
     */
    public function getNextCoordinate(Spot $spot): Coordinate|null
    {
        $blockReason = $this->getBlockReasonObject($spot);

        if (empty($blockReason->from) || empty($blockReason->to)) {
            return null;
        }

        return new Coordinate($blockReason->to[1], $blockReason->to[0]);
    }

    private function getBlockReasonObject(Spot $spot): object
    {
        $blockReason = substr($spot->getBlockReason(), 4);

        return json_decode($blockReason);
    }
}
