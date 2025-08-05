<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Scheduling\Entities;

use App\Domain\Scheduling\Entities\ServicePoint;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Tests\TestCase;
use Tests\Tools\TestValue;

class ServicePointTest extends TestCase
{
    private int $id;
    private int $referenceId;
    private Coordinate $location;
    private int $priority;
    private bool $isReserved;
    private int $preferredEmployeeId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = $this->faker->randomNumber(3);
        $this->referenceId = $this->faker->randomNumber(4);
        $this->location = new Coordinate($this->faker->latitude, $this->faker->longitude);
        $this->priority = $this->faker->randomNumber(2);
        $this->preferredEmployeeId = $this->faker->randomNumber(4);
        $this->isReserved = false;
    }

    /**
     * @test
     */
    public function it_returns_correct_values(): void
    {
        $servicePoint = new ServicePoint(
            id: $this->id,
            referenceId: $this->referenceId,
            location: $this->location,
            priority: $this->priority,
            preferredEmployeeId: $this->preferredEmployeeId,
            reserved: $this->isReserved,
        );
        $this->assertEquals($this->id, $servicePoint->getId());
        $this->assertEquals($this->referenceId, $servicePoint->getReferenceId());
        $this->assertEquals($this->location, $servicePoint->getLocation());
        $this->assertEquals($this->priority, $servicePoint->getPriority());
        $this->assertEquals($this->preferredEmployeeId, $servicePoint->getPreferredEmployeeId());
        $this->assertEquals($this->isReserved, $servicePoint->isReserved());
    }

    /**
     * @test
     *
     * ::isHighPriority
     */
    public function it_returns_true_if_service_is_high_priority(): void
    {
        $servicePoint = new ServicePoint(
            id: $this->id,
            referenceId: $this->referenceId,
            location: $this->location,
            priority: 100,
            reserved: $this->isReserved,
        );
        $this->assertTrue($servicePoint->isHighPriority());
    }

    /**
     * @test
     *
     * ::addNearestServiceId
     * ::getNearestServiceIds
     */
    public function it_adds_nearest_service_id(): void
    {
        $servicePoint = new ServicePoint(
            id: $this->id,
            referenceId: $this->referenceId,
            location: $this->location,
            priority: $this->priority,
            reserved: $this->isReserved,
        );
        $servicePoint->addNearestServiceId(99);

        $this->assertEquals([99], $servicePoint->getNearestServiceIds());
    }

    /**
     * @test
     *
     * ::reserve
     */
    public function it_reserves_service(): void
    {
        $servicePoint = new ServicePoint(
            id: $this->id,
            referenceId: $this->referenceId,
            location: $this->location,
            priority: $this->priority,
            reserved: false,
        );
        $servicePoint->reserve();

        $this->assertTrue($servicePoint->isReserved());
    }

    /**
     * @test
     *
     * ::getWeightToNextPoint
     */
    public function it_returns_weight_to_another_service_point(): void
    {
        $servicePoint1 = new ServicePoint(
            id: 1,
            referenceId: $this->referenceId,
            location: new Coordinate(TestValue::MIN_LATITUDE, TestValue::MIN_LONGITUDE),
            priority: 50,
            reserved: false,
        );
        $servicePoint2 = new ServicePoint(
            id: 2,
            referenceId: $this->referenceId,
            location: new Coordinate(TestValue::MAX_LATITUDE, TestValue::MAX_LONGITUDE),
            priority: 50,
            reserved: false,
        );

        $this->assertEquals(0.54, round($servicePoint1->getWeightToNextPoint($servicePoint2), 2));
    }
}
