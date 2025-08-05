<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Listeners\Log;

use App\Application\Events\OptimizationRuleApplied;
use App\Application\Listeners\Log\LogOptimizationRuleApplying;
use App\Domain\Contracts\OptimizationRule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogOptimizationRuleApplyingTest extends TestCase
{
    private LogOptimizationRuleApplying $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new LogOptimizationRuleApplying();
    }

    /**
     * @test
     */
    public function it_listens_events(): void
    {
        Event::fake();

        Event::assertListening(
            OptimizationRuleApplied::class,
            LogOptimizationRuleApplying::class
        );
    }

    /**
     * @test
     */
    public function it_logs_rule_applying(): void
    {
        $ruleMock = \Mockery::mock(OptimizationRule::class);
        $name = $this->faker->text(5);
        $ruleMock->shouldReceive('name')->andReturn($name);

        $event = new OptimizationRuleApplied($ruleMock);

        Log::expects('info')->with("Applying rule [$name]");

        $this->listener->handle($event);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->listener);
    }
}
