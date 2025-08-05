<?php

declare(strict_types=1);

namespace App\Domain\Tracking\Entities;

use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\RouteOptimization\ValueObjects\RouteStats;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\Tracking\Entities\Events\FleetRouteEvent;
use App\Domain\Tracking\ValueObjects\ConvexPolygon;
use App\Domain\Tracking\ValueObjects\RouteCompletionStats;
use App\Domain\Tracking\ValueObjects\RouteDrivingStats;
use App\Domain\Tracking\ValueObjects\RouteTrackingData;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class FleetRoute
{
    private const ROUNDING_PRECISION = 2;

    /** @var Collection<FleetRouteEvent> */
    private Collection $route;

    private RouteDrivingStats|null $drivingStats = null;
    private RouteCompletionStats|null $completionStats = null;
    private RouteTrackingData|null $trackingData = null;

    /**
     * @param int $id
     * @param CarbonInterface $startAt
     * @param ServicePro $servicePro
     * @param RouteStats $routeStats
     */
    public function __construct(
        private int $id,
        private CarbonInterface $startAt,
        private ServicePro $servicePro,
        private RouteStats $routeStats,
        private readonly string|null $routeGeometry = null,
    ) {
    }

    /**
     * Returns ID of the route
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Returns start time of the route
     *
     * @return CarbonInterface
     */
    public function getStartAt(): CarbonInterface
    {
        return $this->startAt;
    }

    /**
     * Returns service pro assigned to the route
     *
     * @return ServicePro
     */
    public function getServicePro(): ServicePro
    {
        return $this->servicePro;
    }

    /**
     * Returns route
     *
     * @return Collection<FleetRouteEvent>
     */
    public function getRoute(): Collection
    {
        return $this->route;
    }

    /**
     * Returns route stats
     *
     * @return RouteStats
     */
    public function getRouteStats(): RouteStats
    {
        return $this->routeStats;
    }

    /**
     * Sets route
     *
     * @param Collection<FleetRouteEvent> $route
     *
     * @return void
     */
    public function setRoute(Collection $route): void
    {
        $this->route = new Collection();
        $route->each(function (FleetRouteEvent $event) {
            $this->route->add($event);
        });
    }

    /**
     * Returns area of the route
     *
     * @return ConvexPolygon
     */
    public function getArea(): ConvexPolygon
    {
        $points = $this->getRoute()->map(fn (FleetRouteEvent $event) => $event->getLocation());

        return new ConvexPolygon($points);
    }

    /**
     * Returns route driving stats
     *
     * @return RouteDrivingStats|null
     */
    public function getDrivingStats(): RouteDrivingStats|null
    {
        return $this->drivingStats;
    }

    /**
     * Sets route driving stats
     *
     * @param RouteDrivingStats $drivingStats
     *
     * @return $this
     */
    public function setDrivingStats(RouteDrivingStats $drivingStats): FleetRoute
    {
        $this->drivingStats = $drivingStats;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRouteGeometry(): string|null
    {
        return $this->routeGeometry;
    }

    /**
     * @param RouteCompletionStats $completionStats
     *
     * @return $this
     */
    public function setCompletionStats(RouteCompletionStats $completionStats): FleetRoute
    {
        $this->completionStats = $completionStats;

        return $this;
    }

    /**
     * @return RouteCompletionStats|null
     */
    public function getCompletionStats(): RouteCompletionStats|null
    {
        return $this->completionStats;
    }

    /**
     * @param RouteTrackingData $trackingData
     *
     * @return $this
     */
    public function setTrackingData(RouteTrackingData $trackingData): FleetRoute
    {
        $this->trackingData = $trackingData;

        return $this;
    }

    /**
     * @return RouteTrackingData|null
     */
    public function getTrackingData(): RouteTrackingData|null
    {
        return $this->trackingData;
    }

    /**
     * Return the center of a polygon
     *
     * @return Coordinate|null
     */
    public function getAreaCenter(): Coordinate|null
    {
        $points = $this->getArea()->getVertexes();

        if ($points->isEmpty()) {
            return null;
        }

        $centerLatitude = $points->average(fn (Coordinate $point) => $point->getLatitude());
        $centerLongitude = $points->average(fn (Coordinate $point) => $point->getLongitude());

        return new Coordinate($centerLatitude, $centerLongitude);
    }

    /**
     * Formats route stats as an array
     *
     * @return array<string, mixed>
     */
    public function getStatsAsArray(): array
    {
        $routeAdherence = $this->getCompletionStats()?->getRouteAdherence();
        $totalDriveMiles = $this->getDrivingStats()?->getTotalDriveDistance()->getMiles();
        $averageDriveMiles = $this->getDrivingStats()?->getAverageDriveDistanceBetweenServices()->getMiles();

        return [
            'total_appointments' => $this->getCompletionStats()?->getTotalAppointments(),
            'total_service_time_minutes' => $this->getCompletionStats()?->getTotalServiceTime()->getTotalMinutes(),
            'total_drive_time_minutes' => $this->getDrivingStats()?->getTotalDriveTime()->getTotalMinutes(),
            'total_drive_miles' => is_null($totalDriveMiles) ? null : round($totalDriveMiles, self::ROUNDING_PRECISION),
            'average_drive_time_minutes' => $this->getDrivingStats()?->getAverageDriveTimeBetweenServices()->getTotalMinutes(),
            'average_drive_miles' => is_null($averageDriveMiles) ? null : round($averageDriveMiles, self::ROUNDING_PRECISION),
            'route_adherence' => is_null($routeAdherence) ? null : round($routeAdherence, self::ROUNDING_PRECISION),
        ];
    }
}
