<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Weather\Helpers;

use Tests\TestCase;
use App\Infrastructure\Services\Weather\Helpers\WindDirectionHelper;

class WindDirectionHelperTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider windDirectionProvider
     */
    public function it_returns_correct_wind_direction_text(int $degrees, string $expectedDirection): void
    {
        $actualDirection = WindDirectionHelper::getTextDirection($degrees);

        $this->assertEquals($expectedDirection, $actualDirection);
    }

    public static function windDirectionProvider(): array
    {
        return [
            [0, 'N'],
            [45, 'NE'],
            [90, 'E'],
            [135, 'SE'],
            [180, 'S'],
            [225, 'SW'],
            [270, 'W'],
            [315, 'NW'],
            [360, 'N'],
            [405, 'NE'],
            [-45, 'NW'],
            [-90, 'W'],
            [-135, 'SW'],
            [-180, 'S'],
            [-225, 'SE'],
            [-270, 'E'],
            [-315, 'NE'],
            [-360, 'N'],
            [-405, 'NW'],
        ];
    }
}
