<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Google\DataTranslators;

use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Infrastructure\Services\Google\DataTranslators\DomainToGoogleTranslator;
use Carbon\Carbon;
use Google\Protobuf\Timestamp;
use Tests\TestCase;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\TestValue;

class DomainToGoogleTranslatorTest extends TestCase
{
    /**
     * @test
     *
     * ::translate
     */
    public function it_translates_optimization_state_to_shipment_model(): void
    {
        $startAt = Carbon::tomorrow()->setTimeFromTimeString(TestValue::ROUTE_START_TIME);
        $endAt = Carbon::tomorrow()->setTimeFromTimeString(TestValue::ROUTE_END_TIME);

        $optimizationState = OptimizationStateFactory::make([
            'timeFrame' => new TimeWindow($startAt, $endAt),
        ]);

        /** @var DomainToGoogleTranslator $translator */
        $translator = app(DomainToGoogleTranslator::class);
        $shipmentModel = $translator->translate($optimizationState);

        $expectedStartTime = (new Timestamp())->setSeconds($startAt->timestamp);
        $expectedEndTime = (new Timestamp())->setSeconds($endAt->timestamp);

        $this->assertEquals($expectedStartTime, $shipmentModel->getGlobalStartTime());
        $this->assertEquals($expectedEndTime, $shipmentModel->getGlobalEndTime());
        $this->assertEquals(5, $shipmentModel->getMaxActiveVehicles());
        $this->assertCount(53, $shipmentModel->getShipments());
        $this->assertCount(5, $shipmentModel->getVehicles());
    }

    /**
     * @test
     *
     * ::translateSingleRoute
     */
    public function it_translates_single_route_to_shipment_model(): void
    {
        $startAt = Carbon::tomorrow()->startOfDay();
        $endAt = Carbon::tomorrow()->endOfDay();

        $route = RouteFactory::make([
            'date' => Carbon::tomorrow(),
        ]);

        /** @var DomainToGoogleTranslator $translator */
        $translator = app(DomainToGoogleTranslator::class);
        $shipmentModel = $translator->translateSingleRoute($route);

        $expectedStartTime = (new Timestamp())->setSeconds($startAt->timestamp);
        $expectedEndTime = (new Timestamp())->setSeconds($endAt->timestamp);

        $this->assertEquals($expectedStartTime, $shipmentModel->getGlobalStartTime());
        $this->assertEquals($expectedEndTime, $shipmentModel->getGlobalEndTime());
        $this->assertCount(10, $shipmentModel->getShipments());
        $this->assertCount(1, $shipmentModel->getVehicles());
    }
}
