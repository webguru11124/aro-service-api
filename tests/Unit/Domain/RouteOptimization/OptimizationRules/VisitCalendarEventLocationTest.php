<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\OptimizationRules;

use App\Domain\Contracts\Queries\GetEventsOnDateQuery;
use App\Domain\Contracts\Queries\Office\GetOfficesByIdsQuery;
use App\Domain\Contracts\Repositories\CalendarEventRepository;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\OptimizationRules\VisitCalendarEventLocation;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\Calendar\RecurringEventFactory;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\Factories\ReservedTimeFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\ServiceProFactory;
use Tests\Tools\TestValue;
use Tests\Traits\AssertRuleExecutionResultsTrait;
use Tests\Traits\RuleGetNameAndDescription;

class VisitCalendarEventLocationTest extends TestCase
{
    use AssertRuleExecutionResultsTrait;
    use RuleGetNameAndDescription;

    private const WORKING_DAY_START = '08:00:00';

    private VisitCalendarEventLocation $rule;

    private CalendarEventRepository|MockInterface $mockEventsQuery;
    private GetOfficesByIdsQuery|MockInterface $mockOfficesQuery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockEventsQuery = Mockery::mock(GetEventsOnDateQuery::class);
        $this->mockOfficesQuery = Mockery::mock(GetOfficesByIdsQuery::class);

        $this->rule = new VisitCalendarEventLocation(
            $this->mockEventsQuery,
            $this->mockOfficesQuery
        );
    }

    /**
     * @test
     */
    public function it_skips_processing_if_no_events_in_calendar(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make();

        $this->mockEventsQuery
            ->shouldReceive('get')
            ->with($optimizationState->getOffice()->getId(), $optimizationState->getDate())
            ->once()
            ->andReturn(collect());

        $this->mockOfficesQuery
            ->shouldReceive('get')
            ->never();

        $result = $this->rule->process($optimizationState);
        $this->assertTriggeredRuleResult($result);
    }

    /**
     * @test
     */
    public function it_adds_meeting_on_route(): void
    {
        $servicePro = ServiceProFactory::make([
            'workingHours' => new TimeWindow(
                Carbon::today(TestValue::TIME_ZONE)->setTimeFromTimeString(self::WORKING_DAY_START),
                Carbon::today(TestValue::TIME_ZONE)->setTimeFromTimeString(self::WORKING_DAY_START)->addHours(8),
            ),
        ]);

        $route = RouteFactory::make([
            'workEvents' => [],
            'servicePro' => $servicePro,
        ]);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$route],
        ]);

        $event = RecurringEventFactory::make([
            'participantIds' => [$servicePro->getId()],
        ]);

        $this->mockEventsQuery
            ->shouldReceive('get')
            ->with($optimizationState->getOffice()->getId(), $optimizationState->getDate())
            ->once()
            ->andReturn(collect([$event]));

        $this->mockOfficesQuery
            ->shouldReceive('get')
            ->with([$optimizationState->getOffice()->getId()])
            ->once()
            ->andReturn(collect([
                OfficeFactory::make(),
            ]));

        $result = $this->rule->process($optimizationState);

        $this->assertCount(1, $optimizationState->getRoutes()->first()->getMeetings());
        $this->assertSuccessRuleResult($result);
    }

    /**
     * @test
     */
    public function it_does_not_add_meeting_if_service_pro_was_not_invited(): void
    {
        $servicePro = ServiceProFactory::make([
            'workingHours' => new TimeWindow(
                Carbon::today(TestValue::TIME_ZONE)->setTimeFromTimeString(self::WORKING_DAY_START),
                Carbon::today(TestValue::TIME_ZONE)->setTimeFromTimeString(self::WORKING_DAY_START)->addHours(8),
            ),
        ]);

        $route = RouteFactory::make([
            'workEvents' => [],
            'servicePro' => $servicePro,
        ]);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$route],
        ]);

        $event = RecurringEventFactory::make([
            'participantIds' => [],
        ]);

        $this->mockEventsQuery
            ->shouldReceive('get')
            ->with($optimizationState->getOffice()->getId(), $optimizationState->getDate())
            ->once()
            ->andReturn(collect([$event]));

        $this->mockOfficesQuery
            ->shouldReceive('get')
            ->with([$optimizationState->getOffice()->getId()])
            ->once()
            ->andReturn(collect([
                OfficeFactory::make(),
            ]));

        $result = $this->rule->process($optimizationState);

        $this->assertCount(0, $optimizationState->getRoutes()->first()->getMeetings());
        $this->assertSuccessRuleResult($result);
    }

    /**
     * @test
     */
    public function it_does_not_add_meeting_if_it_intersects_with_reserved_time(): void
    {
        $servicePro = ServiceProFactory::make([
            'workingHours' => new TimeWindow(
                Carbon::today(TestValue::TIME_ZONE)->setTimeFromTimeString(self::WORKING_DAY_START),
                Carbon::today(TestValue::TIME_ZONE)->setTimeFromTimeString(self::WORKING_DAY_START)->addHours(8),
            ),
        ]);
        $reservedTime = ReservedTimeFactory::make([
            'timeWindow' => new TimeWindow(
                Carbon::today(TestValue::TIME_ZONE)->setTimeFromTimeString(self::WORKING_DAY_START),
                Carbon::today(TestValue::TIME_ZONE)->setTimeFromTimeString(self::WORKING_DAY_START)->addHours(2),
            ),
        ]);

        $route = RouteFactory::make([
            'workEvents' => [$reservedTime],
            'servicePro' => $servicePro,
        ]);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$route],
        ]);

        $event = RecurringEventFactory::make([
            'participantIds' => [$servicePro->getId()],
        ]);

        $this->mockEventsQuery
            ->shouldReceive('get')
            ->with($optimizationState->getOffice()->getId(), $optimizationState->getDate())
            ->once()
            ->andReturn(collect([$event]));

        $this->mockOfficesQuery
            ->shouldReceive('get')
            ->with([$optimizationState->getOffice()->getId()])
            ->once()
            ->andReturn(collect([
                OfficeFactory::make(),
            ]));

        $result = $this->rule->process($optimizationState);

        $this->assertCount(0, $optimizationState->getRoutes()->first()->getMeetings());
        $this->assertSuccessRuleResult($result);
    }

    protected function getClassRuleName(): string
    {
        return VisitCalendarEventLocation::class;
    }
}
