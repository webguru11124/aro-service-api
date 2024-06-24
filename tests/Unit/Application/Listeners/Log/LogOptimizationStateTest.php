<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Listeners\Log;

use App\Application\Events\OptimizationState\OptimizationStateStored;
use App\Application\Listeners\Log\LogOptimizationState;
use App\Infrastructure\Formatters\OptimizationStateArrayFormatter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OptimizationStateFactory;

class LogOptimizationStateTest extends TestCase
{
    private LogOptimizationState $listener;
    private OptimizationStateArrayFormatter|MockInterface $formatterMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatterMock = \Mockery::mock(OptimizationStateArrayFormatter::class);
        $this->listener = new LogOptimizationState($this->formatterMock);
    }

    /**
     * @test
     */
    public function it_listens_events(): void
    {
        Event::fake();

        Event::assertListening(
            OptimizationStateStored::class,
            LogOptimizationState::class
        );
    }

    /**
     * @test
     */
    public function it_logs_state_stored_if_debug_is_on(): void
    {
        Config::set('app.debug', true);

        $optimizationState = OptimizationStateFactory::make();
        $event = new OptimizationStateStored($optimizationState);
        $this->formatterMock->shouldReceive('format')->andReturn(['field' => 'value']);

        Log::expects('debug')
            ->withSomeOfArgs('PRE Optimization State');

        $this->listener->handle($event);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->listener);
        unset($this->formatterMock);
    }
}
