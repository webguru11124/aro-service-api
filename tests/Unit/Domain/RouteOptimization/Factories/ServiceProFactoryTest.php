<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Factories;

use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\RouteOptimization\Factories\ServiceProFactory;
use App\Domain\RouteOptimization\ValueObjects\Skill;
use App\Domain\SharedKernel\Entities\Office;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\TestValue;

class ServiceProFactoryTest extends TestCase
{
    private ServiceProFactory $serviceProFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serviceProFactory = new ServiceProFactory();
    }

    /**
     * @test
     */
    public function it_creates_service_pro_from_provided_data(): void
    {
        /** @var Office $office */
        $office = OfficeFactory::make();
        $data = [
            'id' => TestValue::ROUTE_ID,
            'details' => [
                'start_at' => '2024-04-01 08:00:00',
                'route_type' => 'Regular Route',
                'actual_capacity' => 10,
            ],
            'service_pro' => [
                'id' => TestValue::EMPLOYEE_ID,
                'name' => $this->faker->name(),
                'workday_id' => TestValue::WORKDAY_ID,
                'start_location' => ['lat' => 1.0, 'lon' => 1.0],
                'end_location' => ['lat' => 2.0, 'lon' => 2.0],
                'working_hours' => [
                    'start_at' => '08:00:00',
                    'end_at' => '16:00:00',
                ],
                'capacity' => 10,
                'skills' => ['TX'],
            ],
        ];

        $servicePro = $this->serviceProFactory->make($data, $office->getTimezone());

        $this->assertInstanceOf(ServicePro::class, $servicePro);
        $this->assertEquals($data['service_pro']['id'], $servicePro->getId());
        $this->assertEquals($data['service_pro']['name'], $servicePro->getName());
        $this->assertEquals($data['service_pro']['start_location']['lat'], $servicePro->getStartLocation()->getLatitude());
        $this->assertEquals($data['service_pro']['start_location']['lon'], $servicePro->getStartLocation()->getLongitude());
        $this->assertEquals($data['service_pro']['end_location']['lat'], $servicePro->getEndLocation()->getLatitude());
        $this->assertEquals($data['service_pro']['end_location']['lon'], $servicePro->getEndLocation()->getLongitude());
        $this->assertEquals($data['service_pro']['workday_id'], $servicePro->getWorkdayId());
        $this->assertEquals($data['service_pro']['working_hours']['start_at'], $servicePro->getWorkingHours()->getStartAt()->format('H:i:s'));
        $this->assertEquals($data['service_pro']['working_hours']['end_at'], $servicePro->getWorkingHours()->getEndAt()->format('H:i:s'));
        $this->assertEquals(
            $data['service_pro']['skills'],
            $servicePro->getSkillsWithoutPersonal()->map(fn (Skill $skill) => $skill->getLiteral())->values()->toArray()
        );
        $this->assertEquals($data['id'], $servicePro->getRouteId());
    }
}
