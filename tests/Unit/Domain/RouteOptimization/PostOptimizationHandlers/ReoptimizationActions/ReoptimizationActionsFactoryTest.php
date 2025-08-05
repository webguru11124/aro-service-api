<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions;

use App\Domain\RouteOptimization\Exceptions\UnknownRouteReoptimizationAction;
use App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions\LimitBreakTimeFrames;
use App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions\LimitFirstAppointmentExpectedArrival;
use App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions\ReduceWorkTimeRange;
use App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions\ReoptimizationActionFactory;
use App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions\ReverseRoute;
use Tests\TestCase;

class ReoptimizationActionsFactoryTest extends TestCase
{
    private ReoptimizationActionFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new ReoptimizationActionFactory();
    }

    /**
     * @test
     *
     * @dataProvider actionClassesDataProvider
     */
    public function it_returns_same_instance_of_action(string $actionClass): void
    {
        $firstInstance = $this->factory->getAction($actionClass);
        $secondInstance = $this->factory->getAction($actionClass);

        $this->assertEquals(spl_object_id($firstInstance), spl_object_id($secondInstance));
    }

    public static function actionClassesDataProvider(): iterable
    {
        yield [LimitBreakTimeFrames::class];
        yield [LimitFirstAppointmentExpectedArrival::class];
        yield [ReduceWorkTimeRange::class];
        yield [ReverseRoute::class];
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_action_doesnt_exist(): void
    {
        $this->expectException(UnknownRouteReoptimizationAction::class);

        $this->factory->getAction(self::class);
    }
}
