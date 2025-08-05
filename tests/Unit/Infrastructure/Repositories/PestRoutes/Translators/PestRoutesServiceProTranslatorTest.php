<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\PestRoutes\Translators;

use App\Domain\RouteOptimization\ValueObjects\Skill;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesServiceProTranslator;
use Aptive\PestRoutesSDK\Resources\Employees\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\EmployeeData;
use Tests\Tools\PestRoutesData\RouteData;
use Tests\Tools\PestRoutesData\SpotData;

class PestRoutesServiceProTranslatorTest extends TestCase
{
    private const MAX_WORKING_MINS = 630; // 10.5 hours

    private PestRoutesServiceProTranslator $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new PestRoutesServiceProTranslator();
    }

    /**
     * @dataProvider spotsDataProvider
     *
     * @test
     */
    public function it_translates_service_pro_from_pest_routes_to_domain(Collection $spotsCollection, TimeWindow $expectedWorkingHours): void
    {
        /** @var Employee $employee */
        $employee = EmployeeData::getTestData()->first();
        $route = RouteData::getTestData(1, [
            'assignedTech' => $employee->id,
        ])->first();

        $result = $this->subject->toDomain($route, $employee, $spotsCollection);

        $expectedName = "$employee->firstName $employee->lastName";
        $this->assertEquals($expectedName, $result->getName());
        $this->assertEquals($expectedWorkingHours, $result->getWorkingHours());

        $expectedSkills = new Collection();
        $expectedSkills->add($result->getPersonalSkill());
        $expectedSkills->push(...array_map(
            fn (string $skillId) => new Skill((int) $skillId),
            array_keys($employee->skills)
        ));

        $this->assertEquals($expectedSkills, $result->getSkills());
    }

    public static function spotsDataProvider(): iterable
    {
        $date = '2022-02-24';

        $spot1Start = '08:00:00';
        $spot1End = '08:30:00';

        $spot2Start = '09:00:00';
        $spot2End = '09:30:00';

        $spot3Start = '17:30:00';
        $spot3End = '18:00:00';

        $timeZone = 'America/New_York';

        $start = Carbon::parse("$date $spot1Start $timeZone");
        $end = $start->copy();
        $end->addMinutes(self::MAX_WORKING_MINS);

        $expectedWorkingHours = new TimeWindow(
            $start,
            $end
        );

        $substitution1 = [
            'officeTimeZone' => $timeZone,
            'date' => $date,
            'start' => $spot1Start,
            'end' => $spot1End,
            'blockReason' => '',
        ];

        $substitution2 = [
            'officeTimeZone' => $timeZone,
            'date' => $date,
            'start' => $spot2Start,
            'end' => $spot2End,
            'blockReason' => '',
        ];

        $substitution3 = [
            'officeTimeZone' => $timeZone,
            'date' => $date,
            'start' => $spot3Start,
            'end' => $spot3End,
            'blockReason' => '',
        ];

        yield [
            new Collection([
                SpotData::getTestData(1, $substitution1)->first(),
                SpotData::getTestData(1, $substitution2)->first(),
                SpotData::getTestData(1, $substitution3)->first(),
            ]),
            $expectedWorkingHours,
        ];

        yield [
            new Collection([
                SpotData::getTestData(1, $substitution1)->first(),
                SpotData::getTestData(1, $substitution3)->first(),
                SpotData::getTestData(1, $substitution2)->first(),
            ]),
            $expectedWorkingHours,
        ];

        yield [
            new Collection([
                SpotData::getTestData(1, $substitution3)->first(),
                SpotData::getTestData(1, $substitution2)->first(),
                SpotData::getTestData(1, $substitution1)->first(),
            ]),
            $expectedWorkingHours,
        ];
    }
}
