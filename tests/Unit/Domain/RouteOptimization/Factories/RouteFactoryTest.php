<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Factories;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\RouteOptimization\Factories\RouteFactory;
use App\Domain\RouteOptimization\ValueObjects\RouteType;
use App\Domain\SharedKernel\Entities\Office;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\Factories\ServiceProFactory;
use Tests\Tools\TestValue;

class RouteFactoryTest extends TestCase
{
    private RouteFactory $routeFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->routeFactory = new RouteFactory();
    }

    /**
     * @test
     */
    public function it_creates_a_route_from_provided_data(): void
    {
        /** @var Office $office */
        $office = OfficeFactory::make();
        /** @var ServicePro $servicePro */
        $servicePro = ServiceProFactory::make();

        $data = [
            'id' => TestValue::ROUTE_ID,
            'details' => [
                'start_at' => '2024-04-01 08:00:00',
                'route_type' => 'Regular Route',
                'actual_capacity' => 10,
            ],
        ];

        $route = $this->routeFactory->make($data, $office, $servicePro);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals($data['id'], $route->getId());
        $this->assertEquals($office->getId(), $route->getOfficeId());
        $this->assertEquals($data['details']['start_at'], $route->getDate()->format('Y-m-d H:i:s'));
        $this->assertEquals($servicePro, $route->getServicePro());
        $this->assertEquals(RouteType::REGULAR_ROUTE, $route->getRouteType());
        $this->assertEquals($data['details']['actual_capacity'], $route->getActualCapacityCount());
    }
}
