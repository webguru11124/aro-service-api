<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\PestRoutes\Entities;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Infrastructure\Services\PestRoutes\Entities\Spot;
use App\Infrastructure\Services\PestRoutes\Entities\SpotStrategies\SpotStrategy;
use App\Infrastructure\Services\PestRoutes\Enums\SpotType;
use Carbon\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\TestValue;

class SpotTest extends TestCase
{
    private const START_HOUR = 8;
    private const END_HOUR = 9;
    private const BLOCK_REASON = 'block reason';

    private SpotStrategy|MockInterface $spotStrategyMock;
    private Spot $spot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->spotStrategyMock = \Mockery::mock(SpotStrategy::class);
        $this->spot = new Spot(
            strategy: $this->spotStrategyMock,
            id: TestValue::SPOT_ID,
            officeId: TestValue::OFFICE_ID,
            routeId: TestValue::ROUTE_ID,
            timeWindow: new TimeWindow(
                Carbon::tomorrow()->hour(self::START_HOUR),
                Carbon::tomorrow()->hour(self::END_HOUR)
            ),
            blockReason: self::BLOCK_REASON,
            previousCoordinates: new Coordinate(TestValue::LATITUDE, TestValue::LONGITUDE),
            nextCoordinates: new Coordinate(TestValue::LATITUDE, TestValue::LONGITUDE)
        );
    }

    /**
     * @test
     */
    public function it_returns_properties(): void
    {
        $this->assertEquals(TestValue::SPOT_ID, $this->spot->getId());
        $this->assertEquals(TestValue::OFFICE_ID, $this->spot->getOfficeId());
        $this->assertEquals(TestValue::ROUTE_ID, $this->spot->getRouteId());
        $this->assertEquals(self::START_HOUR, $this->spot->getTimeWindow()->getStartAt()->hour);
        $this->assertEquals(self::END_HOUR, $this->spot->getTimeWindow()->getEndAt()->hour);
        $this->assertEquals(self::BLOCK_REASON, $this->spot->getBlockReason());
        $this->assertEquals(Carbon::tomorrow()->toDateString(), $this->spot->getDate()->toDateString());
    }

    /**
     * @test
     */
    public function it_delegates_type_to_strategy(): void
    {
        $this->spotStrategyMock
            ->shouldReceive('getSpotType')
            ->withNoArgs()
            ->once()
            ->andReturn(SpotType::REGULAR);

        $this->assertEquals(SpotType::REGULAR, $this->spot->getType());
    }

    /**
     * @test
     */
    public function it_delegates_window_to_strategy(): void
    {
        $window = $this->faker->title();

        $this->spotStrategyMock
            ->shouldReceive('getWindow')
            ->with($this->spot)
            ->once()
            ->andReturn($window);

        $this->assertEquals($window, $this->spot->getWindow());
    }

    /**
     * @test
     */
    public function it_delegates_is_aro_to_strategy(): void
    {
        $isAro = $this->faker->boolean();

        $this->spotStrategyMock
            ->shouldReceive('isAroSpot')
            ->once()
            ->andReturn($isAro);

        $this->assertEquals($isAro, $this->spot->isAroSpot());
    }

    /**
     * @test
     */
    public function it_delegates_previous_coordinate_to_strategy(): void
    {
        $coordinate = new Coordinate(
            $this->faker->randomFloat(),
            $this->faker->randomFloat()
        );

        $this->spotStrategyMock
            ->shouldReceive('getPreviousCoordinate')
            ->with($this->spot)
            ->once()
            ->andReturn($coordinate);

        $this->assertSame($coordinate, $this->spot->getPreviousCoordinate());
    }

    /**
     * @test
     */
    public function it_delegates_next_coordinate_to_strategy(): void
    {
        $coordinate = new Coordinate(
            $this->faker->randomFloat(),
            $this->faker->randomFloat()
        );

        $this->spotStrategyMock
            ->shouldReceive('getNextCoordinate')
            ->with($this->spot)
            ->once()
            ->andReturn($coordinate);

        $this->assertSame($coordinate, $this->spot->getNextCoordinate());
    }
}
