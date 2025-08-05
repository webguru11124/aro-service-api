<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Commands\ResetCustomerPreferredTech;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use App\Application\Events\PreferredTechResigned;
use App\Application\Commands\ResetPreferredTech\ResetPreferredTechCommand;
use App\Application\Commands\ResetPreferredTech\ResetPreferredTechHandler;
use App\Domain\Scheduling\ValueObjects\ResignedTechAssignment;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\CustomersDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\SubscriptionsDataProcessor;
use Mockery;
use Tests\TestCase;
use Tests\Tools\TestValue;

class ResetPreferredTechHandlerTest extends TestCase
{
    private CustomersDataProcessor|Mockery\MockInterface $mockCustomersDataProcessor;
    private SubscriptionsDataProcessor|Mockery\MockInterface $mockSubscriptionsDataProcessor;
    private ResetPreferredTechHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        $this->mockCustomersDataProcessor = Mockery::mock(CustomersDataProcessor::class);
        $this->mockSubscriptionsDataProcessor = Mockery::mock(SubscriptionsDataProcessor::class);
        $this->handler = new ResetPreferredTechHandler(
            $this->mockCustomersDataProcessor,
            $this->mockSubscriptionsDataProcessor
        );
    }

    /**
     * @test
     */
    public function it_resets_preferred_service_pro_for_customers_with_resigned_service_pro_to_default(): void
    {
        $command = new ResetPreferredTechCommand(
            resignedTechAssignments: collect([
                new ResignedTechAssignment(
                    customerId: 1,
                    customerName: 'Test',
                    customerEmail: 'abc@test.test',
                    subscriptionId: 321,
                    preferredTechId: 123,
                ),
            ]),
            officeId: TestValue::OFFICE_ID,
        );

        $this->mockCustomersDataProcessor
            ->shouldReceive('resetPreferredTech')
            ->once()
            ->withArgs(function (int $officeId, int $customerId) {
                return $customerId === 1 && $officeId === TestValue::OFFICE_ID;
            });

        $this->mockSubscriptionsDataProcessor
            ->shouldReceive('resetPreferredTech')
            ->once()
            ->withArgs(function (int $officeId, int $subscriptionId, int $customerId) {
                return $officeId === TestValue::OFFICE_ID && $subscriptionId === 321 && $customerId === 1;
            });

        $this->handler->handle($command);

        Event::assertDispatched(PreferredTechResigned::class);
    }

    /**
     * @test
     */
    public function it_logs_error_when_resetting_customer_preferred_tech_fails(): void
    {
        $command = new ResetPreferredTechCommand(
            resignedTechAssignments: collect([
                new ResignedTechAssignment(
                    customerId: 1,
                    customerName: 'Test',
                    customerEmail: 'abc@test.test',
                    subscriptionId: 321,
                    preferredTechId: 123,
                ),
            ]),
            officeId: TestValue::OFFICE_ID,
        );

        $this->mockCustomersDataProcessor
            ->shouldReceive('resetPreferredTech')
            ->once()
            ->andThrow(new Exception('Error message'));

        $this->mockSubscriptionsDataProcessor
            ->shouldReceive('resetPreferredTech')
            ->once()
            ->andThrow(new Exception('Error message'));

        Log::shouldReceive('notice')
            ->twice();

        $this->handler->handle($command);

        Event::assertDispatched(PreferredTechResigned::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $this->mockCustomersDataProcessor,
            $this->mockSubscriptionsDataProcessor,
            $this->handler,
        );
    }
}
