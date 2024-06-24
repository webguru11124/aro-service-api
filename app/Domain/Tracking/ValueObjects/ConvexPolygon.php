<?php

declare(strict_types=1);

namespace App\Domain\Tracking\ValueObjects;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Illuminate\Support\Collection;

class ConvexPolygon
{
    /**
     * @param Collection<Coordinate> $points
     */
    public function __construct(
        private readonly Collection $points
    ) {
        // Validate if all collection elements are Coordinate objects
        $this->points->each(
            fn (Coordinate $coordinate) => $coordinate instanceof Coordinate
        );
    }

    /**
     * Returns the convex polygon of a set of points
     *
     * @return Collection
     */
    public function getVertexes(): Collection
    {
        $points = $this->points;

        $n = $points->count();

        if ($n <= 3) {
            // If there are 3 or fewer points, the convex hull is the polygon itself.
            return new Collection($points->toArray());
        }

        // Find the point with the lowest Y-coordinate (and leftmost if tied).
        /** @var Coordinate $pivot */
        $pivot = $points->first();
        foreach ($points as $point) {
            if (
                $point->getLatitude() < $pivot->getLatitude()
                || ($point->getLatitude() === $pivot->getLatitude()
                    && $point->getLongitude() < $pivot->getLongitude())
            ) {
                $pivot = $point;
            }
        }

        $sortedPoints = $this->sortPointsByPolarAngle($points, $pivot);

        // Initialize the convex polygon with the first three points.
        $area = new Collection([$pivot, $sortedPoints->first(), $sortedPoints->get(1)]);

        // Iterate through the sorted points and build the convex polygon.
        for ($i = 2; $i < $n; $i++) {
            while (
                $area->count() > 1
                && $this->crossProduct($area->get($area->count() - 2), $area->get($area->count() - 1), $sortedPoints->get($i)) <= 0
            ) {
                $area->pop();
            }
            $area->push($sortedPoints->get($i));
        }

        $area->push($area->first());

        return $area;
    }

    /**
     * Calculates the cross product of three points
     *
     * @param Coordinate $o
     * @param Coordinate $a
     * @param Coordinate $b
     *
     * @return float
     */
    private function crossProduct(Coordinate $o, Coordinate $a, Coordinate $b): float
    {
        return ($a->getLongitude() - $o->getLongitude()) * ($b->getLatitude() - $o->getLatitude()) - ($a->getLatitude() - $o->getLatitude()) * ($b->getLongitude() - $o->getLongitude());
    }

    /**
     * Sorts the points by polar angle
     *
     * @param Collection $points
     * @param Coordinate $pivot
     *
     * @return Collection
     */
    private function sortPointsByPolarAngle(Collection $points, Coordinate $pivot): Collection
    {
        $pointsArray = $points->toArray();
        usort($pointsArray, function (Coordinate $point1, Coordinate $point2) use ($pivot) {
            if (
                $point1->getLongitude() === $pivot->getLongitude()
                && $point1->getLatitude() === $pivot->getLatitude()
            ) {
                return -1;
            }
            if (
                $point2->getLongitude() === $pivot->getLongitude()
                && $point2->getLatitude() === $pivot->getLatitude()
            ) {
                return 1;
            }

            $angle1 = atan2($point1->getLatitude() - $pivot->getLatitude(), $point1->getLongitude() - $pivot->getLongitude());
            $angle2 = atan2($point2->getLatitude() - $pivot->getLatitude(), $point2->getLongitude() - $pivot->getLongitude());

            return $angle1 <=> $angle2;
        });

        return new Collection($pointsArray);
    }
}
