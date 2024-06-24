<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Scheduling\Entities;

use App\Domain\Scheduling\Entities\ClusterOfServices;
use App\Domain\Scheduling\Entities\ServicePoint;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Tests\TestCase;
use Tests\Tools\Factories\Scheduling\ServicePointFactory;
use Tests\Tools\TestValue;

class ClusterOfServicesTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_created(): void
    {
        $cluster = new ClusterOfServices(
            1,
            10,
            new Coordinate(TestValue::MIN_LATITUDE, TestValue::MIN_LONGITUDE),
        );
        $servicePoint = ServicePointFactory::make([
            'reserved' => false,
        ]);
        $cluster->addService($servicePoint);

        $this->assertEquals(1, $cluster->getId());
        $this->assertCount(1, $cluster->getServices());
        $this->assertEquals(1, $cluster->getServicesCount());
        $this->assertTrue($cluster->getServices()->first()->isReserved());
    }

    /**
     * @test
     *
     * ::getDistanceToServicePoint
     */
    public function it_returns_distance_to_service_point_from_service_pro_start_location(): void
    {
        $cluster = new ClusterOfServices(
            1,
            10,
            new Coordinate(TestValue::MIN_LATITUDE, TestValue::MIN_LONGITUDE),
        );
        $servicePoint = ServicePointFactory::make([
            'location' => new Coordinate(TestValue::MAX_LATITUDE, TestValue::MAX_LONGITUDE),
        ]);

        $this->assertEquals(91.9, $cluster->getDistanceToServicePoint($servicePoint)->getMiles());
    }

    /**
     * @test
     *
     * ::canHandleService
     */
    public function it_returns_true_when_there_is_capacity_to_handle_service(): void
    {
        $cluster = new ClusterOfServices(
            1,
            10,
            new Coordinate(TestValue::MIN_LATITUDE, TestValue::MIN_LONGITUDE),
        );
        $servicePoint = ServicePointFactory::make();

        $this->assertTrue($cluster->canHandleService($servicePoint));
    }

    /**
     * @test
     *
     * ::canHandleService
     */
    public function it_returns_false_when_there_is_no_capacity_to_handle_service(): void
    {
        $cluster = new ClusterOfServices(
            1,
            0,
            new Coordinate(TestValue::MIN_LATITUDE, TestValue::MIN_LONGITUDE),
        );
        $servicePoint = ServicePointFactory::make();

        $this->assertFalse($cluster->canHandleService($servicePoint));
    }

    /**
     * @test
     *
     * ::canHandleService
     */
    public function it_returns_false_when_preferred_employee_id_does_not_match_to_cluster_employee(): void
    {
        $cluster = new ClusterOfServices(
            1,
            10,
            new Coordinate(TestValue::MIN_LATITUDE, TestValue::MIN_LONGITUDE),
            TestValue::EMPLOYEE_ID
        );
        $servicePoint = ServicePointFactory::make([
            'preferredEmployeeId' => TestValue::EMPLOYEE_ID + 1,
        ]);

        $this->assertFalse($cluster->canHandleService($servicePoint));
    }

    /**
     * @test
     *
     * ::canHandleService
     */
    public function it_returns_true_when_there_preferred_employee_match_to_cluster_employee(): void
    {
        $cluster = new ClusterOfServices(
            1,
            10,
            new Coordinate(TestValue::MIN_LATITUDE, TestValue::MIN_LONGITUDE),
            TestValue::EMPLOYEE_ID
        );
        /** @var ServicePoint $servicePoint */
        $servicePoint = ServicePointFactory::make([
            'preferredEmployeeId' => TestValue::EMPLOYEE_ID,
        ]);

        $this->assertTrue($cluster->canHandleService($servicePoint));
    }

    /**
     * @test
     *
     * ::isFull
     */
    public function it_returns_true_when_cluster_is_full(): void
    {
        $cluster = new ClusterOfServices(
            1,
            1,
            new Coordinate(TestValue::MIN_LATITUDE, TestValue::MIN_LONGITUDE),
        );
        $servicePoint = ServicePointFactory::make();
        $cluster->addService($servicePoint);

        $this->assertTrue($cluster->isFull());
    }

    /**
     * @test
     *
     * ::isFull
     */
    public function it_returns_false_when_cluster_is_not_full(): void
    {
        $cluster = new ClusterOfServices(
            1,
            10,
            new Coordinate(TestValue::MIN_LATITUDE, TestValue::MIN_LONGITUDE),
        );
        $servicePoint = ServicePointFactory::make();
        $cluster->addService($servicePoint);

        $this->assertFalse($cluster->isFull());
    }
}
