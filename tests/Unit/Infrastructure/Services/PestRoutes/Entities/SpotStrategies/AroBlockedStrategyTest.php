<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\PestRoutes\Entities\SpotStrategies;

use App\Infrastructure\Services\PestRoutes\Entities\Spot;
use App\Infrastructure\Services\PestRoutes\Entities\SpotStrategies\AroBlockedStrategy;
use App\Infrastructure\Services\PestRoutes\Enums\SpotType;
use Tests\TestCase;
use Tests\Tools\Factories\SpotFactory;

class AroBlockedStrategyTest extends TestCase
{
    private AroBlockedStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->strategy = new AroBlockedStrategy();
    }

    /**
     * @test
     */
    public function it_returns_proper_spot_type(): void
    {
        $this->assertEquals(SpotType::ARO_BLOCKED, $this->strategy->getSpotType());
    }

    /**
     * @test
     *
     * @dataProvider windowDataProvider
     */
    public function it_returns_proper_window(Spot $spot, string $expectedWindow): void
    {
        $this->assertEquals($expectedWindow, $this->strategy->getWindow($spot));
    }

    public static function windowDataProvider(): iterable
    {
        yield [
            'spot' => SpotFactory::make([
                'blockReason' => '\ARO {"from": [-97.7791, 30.2999], "to": [-97.7612, 30.2981], "skills": ["INI", "TX"], "time": [8, 12]}',
            ]),
            'expectedWindow' => 'AM',
        ];

        yield [
            'spot' => SpotFactory::make([
                'blockReason' => '\ARO {"from": [-97.7791, 30.2999], "to": [-97.7612, 30.2981], "skills": ["INI", "TX"], "time": [12, 16]}',
            ]),
            'expectedWindow' => 'PM',
        ];

        yield [
            'spot' => SpotFactory::make([
                'blockReason' => '\ARO {"from": [-97.7791, 30.2999], "to": [-97.7612, 30.2981], "skills": ["INI", "TX"], "time": [10, 14]}',
            ]),
            'expectedWindow' => 'AM',
        ];

        yield [
            'spot' => SpotFactory::make([
                'blockReason' => '\ARO {"from": [-97.7791, 30.2999], "to": [-97.7612, 30.2981], "skills": ["INI", "TX"], "time": [11, 15]}',
            ]),
            'expectedWindow' => 'PM',
        ];
    }

    /**
     * @test
     */
    public function it_returns_proper_coordinates(): void
    {
        $prevLat = $this->faker->randomFloat(4, 1, 90);
        $prevLng = $this->faker->randomFloat(4, 1, 180);
        $nextLat = $this->faker->randomFloat(4, 1, 90);
        $nextLng = $this->faker->randomFloat(4, 1, 180);

        $spot = SpotFactory::make([
            'blockReason' => sprintf(
                '\ARO {"from": [%f, %f], "to": [%f, %f], "skills": ["INI", "TX"], "time": [8, 12]}',
                $prevLng,
                $prevLat,
                $nextLng,
                $nextLat
            ),
        ]);

        $this->assertEquals($prevLat, $this->strategy->getPreviousCoordinate($spot)->getLatitude());
        $this->assertEquals($prevLng, $this->strategy->getPreviousCoordinate($spot)->getLongitude());
        $this->assertEquals($nextLat, $this->strategy->getNextCoordinate($spot)->getLatitude());
        $this->assertEquals($nextLng, $this->strategy->getNextCoordinate($spot)->getLongitude());
    }

    /**
     * @test
     */
    public function get_coordinates_returns_null_if_there_is_no_block_reason(): void
    {
        $spot = SpotFactory::make([
            'blockReason' => '\ARO {"skills": ["INI", "TX"], "time": [8, 12]}',
        ]);

        $this->assertNull($this->strategy->getPreviousCoordinate($spot));
        $this->assertNull($this->strategy->getNextCoordinate($spot));
    }

    /**
     * @test
     */
    public function it_returns_is_aro(): void
    {
        $this->assertTrue($this->strategy->isAroSpot());
    }
}
