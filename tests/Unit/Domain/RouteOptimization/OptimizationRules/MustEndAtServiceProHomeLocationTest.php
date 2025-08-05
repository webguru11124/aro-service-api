<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\OptimizationRules;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\OptimizationRules\MustEndAtServiceProHomeLocation;
use Mockery;
use Tests\TestCase;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Traits\AssertRuleExecutionResultsTrait;
use Tests\Traits\RuleGetNameAndDescription;

class MustEndAtServiceProHomeLocationTest extends TestCase
{
    use AssertRuleExecutionResultsTrait;
    use RuleGetNameAndDescription;

    private MustEndAtServiceProHomeLocation $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new MustEndAtServiceProHomeLocation();
    }

    /**
     * @test
     */
    public function it_applies_rule_correctly(): void
    {
        $mockRoute = Mockery::mock(Route::class);
        $mockRoute->shouldReceive('setEndLocationCoordinatesToServiceProHome')->once();

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$mockRoute],
        ]);

        $result = $this->rule->process($optimizationState);
        $this->assertSuccessRuleResult($result);
    }

    protected function getClassRuleName(): string
    {
        return MustEndAtServiceProHomeLocation::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->rule);
    }
}
