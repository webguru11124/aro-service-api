<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Services;

use App\Domain\Scheduling\Entities\ClusterOfServices;
use App\Domain\Scheduling\Entities\SchedulingState;
use App\Domain\Scheduling\Entities\ServicePoint;
use App\Domain\Scheduling\Helpers\TriangulateHelper;
use App\Domain\Scheduling\ValueObjects\Point;
use App\Domain\Scheduling\ValueObjects\Triangle;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Illuminate\Support\Collection;

class AppointmentSchedulingService
{
    private const EARTH_RADIUS = 6371;
    private const LOCAL_MAP_SIZE = 1500;
    private const MAX_PRIORITY_THRESHOLD = 0.75;

    private SchedulingState $schedulingState;

    /** @var Collection<ServicePoint> */
    private Collection $servicePoints;

    /** @var Collection<ClusterOfServices> */
    private Collection $clusters;

    private float $maxLat;
    private float $minLat;
    private float $maxLng;
    private float $minLng;
    private float $cosF;
    private Point $minPoint;
    private Point $maxPoint;

    public function __construct(
        private TriangulateHelper $triangulateHelper,
    ) {
    }

    /**
     * Schedules pending services to routes
     *
     * @param SchedulingState $schedulingState
     *
     * @return SchedulingState
     */
    public function schedulePendingServices(SchedulingState $schedulingState): SchedulingState
    {
        $this->schedulingState = $schedulingState;
        $this->schedulingState->setMetricsBeforeScheduling();

        $this->resolveServicePoints();

        if ($this->servicePoints->isEmpty()) {
            return $this->schedulingState;
        }

        $this->resolveMaxMinCoordinates();
        $this->buildGraph();
        $this->initClusters();

        $this->addHighPriorityServicesToClusters();
        $this->addMorePriorityServicesToClusters();
        $this->addLessPriorityServicesToClusters();

        $this->expandClustersByMostWeightedService();
        $this->assignServicesFromClusters();

        $points = $this->exportPoints();
        $servicePro = $this->exportServicePro();

        return $this->schedulingState;
    }

    private function resolveServicePoints(): void
    {
        $this->servicePoints = $this->schedulingState->getPendingServicePointsForScheduledDate();
    }

    private function initClusters(): void
    {
        $this->clusters = $this->schedulingState->getInitialClusters();
    }

    private function assignServicesFromClusters(): void
    {
        $this->schedulingState->assignServicesFromClusters($this->clusters);
    }

    private function buildGraph(): void
    {
        /** @var Collection<Point> $points */
        $points = $this->servicePoints->mapWithKeys(
            fn (ServicePoint $servicePoint) => [
                $servicePoint->getId() => $this->getLocalPointXYFromCoordinates($servicePoint->getLocation()),
            ]
        );

        if ($points->count() === 2) {
            $this->servicePoints[0]->addNearestServiceId($this->servicePoints[1]->getId());
            $this->servicePoints[1]->addNearestServiceId($this->servicePoints[0]->getId());

            return;
        }

        $triangles = $this->triangulateHelper->triangulate($points);
        $triangles->each(function (Triangle $triangle) {
            $serviceId1 = $triangle->a;
            $serviceId2 = $triangle->b;
            $serviceId3 = $triangle->c;

            if (!in_array($serviceId2, $this->servicePoints[$serviceId1]->getNearestServiceIds())) {
                $this->servicePoints[$serviceId1]->addNearestServiceId($serviceId2);
            }
            if (!in_array($serviceId3, $this->servicePoints[$serviceId1]->getNearestServiceIds())) {
                $this->servicePoints[$serviceId1]->addNearestServiceId($serviceId3);
            }
            if (!in_array($serviceId1, $this->servicePoints[$serviceId2]->getNearestServiceIds())) {
                $this->servicePoints[$serviceId2]->addNearestServiceId($serviceId1);
            }
            if (!in_array($serviceId1, $this->servicePoints[$serviceId3]->getNearestServiceIds())) {
                $this->servicePoints[$serviceId3]->addNearestServiceId($serviceId1);
            }
        });
    }

    private function resolveMaxMinCoordinates(): void
    {
        $this->maxLat = $this->servicePoints->max(fn (ServicePoint $service) => $service->getLocation()->getLatitude());
        $this->minLat = $this->servicePoints->min(fn (ServicePoint $service) => $service->getLocation()->getLatitude());
        $this->maxLng = $this->servicePoints->max(fn (ServicePoint $service) => $service->getLocation()->getLongitude());
        $this->minLng = $this->servicePoints->min(fn (ServicePoint $service) => $service->getLocation()->getLongitude());

        $this->cosF = cos(($this->minLat + $this->maxLat) / 2);
        $this->minPoint = $this->getGlobalPointXYFromCoordinates(new Coordinate($this->minLat, $this->minLng));
        $this->maxPoint = $this->getGlobalPointXYFromCoordinates(new Coordinate($this->maxLat, $this->maxLng));
    }

    private function getLocalPointXYFromCoordinates(Coordinate $location): Point
    {
        $globalPoint = $this->getGlobalPointXYFromCoordinates($location);

        $dx = abs($this->minPoint->x - $this->maxPoint->x);
        $dy = abs($this->minPoint->y - $this->maxPoint->y);

        $x = $dx > 0 ? abs($globalPoint->x - $this->minPoint->x) / $dx * self::LOCAL_MAP_SIZE : 0;
        $y = $dy > 0 ? abs($this->maxPoint->y - $globalPoint->y) / $dy * (self::LOCAL_MAP_SIZE * $dy / $dx) : 0;

        return new Point(x: $x, y: $y);
    }

    private function getGlobalPointXYFromCoordinates(Coordinate $location): Point
    {
        return new Point(
            x: self::EARTH_RADIUS * $location->getLongitude() * $this->cosF,
            y: self::EARTH_RADIUS * $location->getLatitude()
        );
    }

    /**
     * Add high priority services to clusters
     */
    private function addHighPriorityServicesToClusters(): void
    {
        $highPriorityServiceIds = $this->servicePoints->filter(
            fn (ServicePoint $servicePoint) => !$servicePoint->isReserved() && $servicePoint->isHighPriority()
        )->map(
            fn (ServicePoint $servicePoint) => $servicePoint->getId()
        );

        $totalCapacity = $this->schedulingState->getTotalCapacity();

        if ($highPriorityServiceIds->count() >= $totalCapacity) {
            do {
                $serviceAddedToCluster = false;

                foreach ($this->clusters as $cluster) {
                    $nearestServicePoint = $this->getNearestServiceToCluster($cluster, $highPriorityServiceIds);

                    if ($nearestServicePoint === null) {
                        continue;
                    }

                    $cluster->addService($nearestServicePoint);
                    $serviceAddedToCluster = true;
                }
            } while ($serviceAddedToCluster);
        } else {
            $highPriorityServiceIds->each(function (int $servicePointId) {
                $servicePoint = $this->servicePoints[$servicePointId];
                $this->getNearestAvailableCluster($servicePoint)?->addService($servicePoint);
            });
        }
    }

    /**
     * Adds nearest priority services to empty clusters
     */
    private function addMorePriorityServicesToClusters(): void
    {
        $priorityServiceIds = $this->servicePoints->filter(
            fn (ServicePoint $servicePoint) => !$servicePoint->isReserved()
                && $servicePoint->getPriority() > ServicePoint::MAX_PRIORITY * self::MAX_PRIORITY_THRESHOLD
        )->sortByDesc(
            fn (ServicePoint $servicePoint) => $servicePoint->getPriority()
        )->map(
            fn (ServicePoint $servicePoint) => $servicePoint->getId()
        );

        $priorityServiceIds->each(function (int $servicePointId) {
            $servicePoint = $this->servicePoints[$servicePointId];
            $this->getNearestAvailableCluster($servicePoint, true)?->addService($servicePoint);
        });
    }

    /**
     * Add one nearest non-reserved service to each empty cluster
     */
    private function addLessPriorityServicesToClusters(): void
    {
        $nonReservedServiceIds = $this->servicePoints->filter(
            fn (ServicePoint $servicePoint) => !$servicePoint->isReserved()
        )->map(
            fn (ServicePoint $servicePoint) => $servicePoint->getId()
        );

        foreach ($this->clusters as $cluster) {
            if ($cluster->getServicesCount() > 0) {
                continue;
            }

            $nearestService = $this->getNearestServiceToCluster($cluster, $nonReservedServiceIds);

            if ($nearestService === null) {
                continue;
            }

            $cluster->addService($nearestService);
        }
    }

    private function getNearestAvailableCluster(ServicePoint $servicePoint, bool $shouldBeEmpty = false): ClusterOfServices|null
    {
        $minDistance = 10000000;
        $nearestCluster = null;

        foreach ($this->clusters as $cluster) {
            if ($shouldBeEmpty && $cluster->getServicesCount() > 0) {
                continue;
            }

            if (!$cluster->canHandleService($servicePoint)) {
                continue;
            }

            $distance = $cluster->getDistanceToServicePoint($servicePoint);

            if ($distance->getMiles() < $minDistance) {
                $minDistance = $distance->getMiles();
                $nearestCluster = $cluster;
            }
        }

        return $nearestCluster;
    }

    private function getNearestServiceToCluster(ClusterOfServices $cluster, Collection $servicePointIds): ServicePoint|null
    {
        $minDistance = 10000000;
        $nearestService = null;

        foreach ($servicePointIds as $servicePointId) {
            /** @var ServicePoint $servicePoint */
            $servicePoint = $this->servicePoints[$servicePointId];

            if ($servicePoint->isReserved() || !$cluster->canHandleService($servicePoint)) {
                continue;
            }

            $distance = $cluster->getDistanceToServicePoint($servicePoint);

            if ($distance->getMiles() < $minDistance) {
                $minDistance = $distance->getMiles();
                $nearestService = $servicePoint;
            }
        }

        return $nearestService;
    }

    private function expandClustersByMostWeightedService(): void
    {
        do {
            $clusterExpanded = false;

            foreach ($this->clusters as $cluster) {
                if ($cluster->isFull()) {
                    continue;
                }

                $weights = [];

                foreach ($cluster->getServices() as $service) {
                    $weight = $this->calcWeight($service);

                    if ($weight == 0) {
                        continue;
                    }

                    $weights[$service->getId()] = $weight;
                }

                if (count($weights) === 0) {
                    continue;
                }

                arsort($weights);
                $serviceId = array_key_first($weights);
                $nearestService = $this->getMostWeightedNearestService($serviceId);

                if ($nearestService === null) {
                    continue;
                }

                $cluster->addService($nearestService);
                $clusterExpanded = true;
            }
        } while ($clusterExpanded);
    }

    private function calcWeight(ServicePoint $servicePoint): float
    {
        $nearestServiceIds = $servicePoint->getNearestServiceIds();

        if (empty($nearestServiceIds)) {
            return 0;
        }

        $weight = 0;

        foreach ($nearestServiceIds as $nextServiceId) {
            /** @var ServicePoint|null $nextServicePoint */
            $nextServicePoint = $this->servicePoints[$nextServiceId];

            if ($nextServicePoint->isReserved()) {
                continue;
            }

            $weight += $servicePoint->getWeightToNextPoint($nextServicePoint);
        }

        $weight /= count($nearestServiceIds);

        return $weight;
    }

    private function exportPoints(): string
    {
        $points = [];

        foreach ($this->servicePoints as $id => $servicePoint) {
            $point = $this->getLocalPointXYFromCoordinates($servicePoint->getLocation());
            $points[] = [
                'id' => $id,
                'x' => round($point->x, 2),
                'y' => round($point->y, 2),
                'priority' => $servicePoint->getPriority(),
                'nearestServices' => $servicePoint->getNearestServiceIds(),
            ];
        }

        return json_encode($points);
    }

    private function exportServicePro(): string
    {
        $result = [];

        foreach ($this->schedulingState->getScheduledRoutes() as $route) {
            $point = $this->getLocalPointXYFromCoordinates($route->getServicePro()->getStartLocation());
            $result[] = [
                'id' => $route->getServicePro()->getId(),
                'x' => round($point->x, 2),
                'y' => round($point->y, 2),
            ];
        }

        return json_encode($result);
    }

    private function getMostWeightedNearestService(int $serviceId): ServicePoint|null
    {
        $betterWeight = 0;
        $betterService = null;
        /** @var ServicePoint $servicePoint */
        $servicePoint = $this->servicePoints[$serviceId];

        foreach ($servicePoint->getNearestServiceIds() as $nextServiceId) {
            /** @var ServicePoint|null $nextServicePoint */
            $nextServicePoint = $this->servicePoints[$nextServiceId];

            if ($nextServicePoint->isReserved()) {
                continue;
            }

            $weight = $servicePoint->getWeightToNextPoint($nextServicePoint);

            if ($weight > $betterWeight) {
                $betterWeight = $weight;
                $betterService = $nextServicePoint;
            }
        }

        return $betterService;
    }
}
