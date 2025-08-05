<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Entities\WorkEvent;

use App\Domain\RouteOptimization\ValueObjects\Skill;
use App\Domain\RouteOptimization\ValueObjects\WorkEvent\ExtraWork;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ExtraWorkTest extends WorkBreakTest
{
    /**
     * @test
     */
    public function it_formats_correct_string(): void
    {
        $startLocation = new Coordinate(54.5432, -34.4242);
        $endLocation = new Coordinate(65.2455, -65.3453);

        $extraWork = new ExtraWork(
            timeWindow: new TimeWindow(Carbon::tomorrow()->hour(12), Carbon::tomorrow()->hour(12)->minute(30)),
            startLocation: $startLocation,
            endLocation: $endLocation,
            skills: new Collection([Skill::fromState('CA'), new Skill(Skill::INITIAL_SERVICE)])
        );

        $formattedString = $extraWork->getFormattedDescription();
        $expectedString = sprintf(
            '\ARO {"from": [%g, %g], "to": [%g, %g], "skills": ["CA", "INI"], "time": [10, 14]}',
            round($startLocation->getLongitude(), 4),
            round($startLocation->getLatitude(), 4),
            round($endLocation->getLongitude(), 4),
            round($endLocation->getLatitude(), 4),
        );

        $this->assertEquals($expectedString, $formattedString);
    }
}
