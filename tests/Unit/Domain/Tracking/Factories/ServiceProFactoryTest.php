<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Tracking\Factories;

use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\Tracking\Factories\ServiceProFactory;
use Carbon\CarbonTimeZone;
use PHPUnit\Framework\TestCase;
use Tests\Tools\TestValue;

class ServiceProFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_service_pro(): void
    {
        $serviceProFactory = new ServiceProFactory();

        $servicePro = $serviceProFactory->create(
            $this->getRouteData(),
            CarbonTimeZone::create(TestValue::TIME_ZONE)
        );

        $this->assertInstanceOf(ServicePro::class, $servicePro);
    }

    private function getRouteData(): array
    {
        return [
            'service_pro' => json_decode('{"id": 530772, "name": "ARO QA7", "workday_id": "", "working_hours": {"end_at": "18:30:00", "start_at": "08:00:00"}}', true),
            'details' => json_decode('{"end_at": "2024-03-11 14:06:21", "capacity": 10, "start_at": "2024-03-11 07:30:00", "route_type": "Short Route", "end_location": {"lat": 30.351, "lon": -97.709}, "start_location": {"lat": 30.351305579189788, "lon": -97.70943845704998}, "optimization_score": 0.71}', true),
        ];
    }
}
