<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\PestRoutes\Translators;

use App\Domain\Contracts\Services\HRService;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesRouteTranslator;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesServiceProTranslator;
use Aptive\PestRoutesSDK\Resources\Employees\Employee;
use Aptive\PestRoutesSDK\Resources\Routes\Route as PestRoutesRoute;
use Mockery;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\EmployeeData;
use Tests\Tools\PestRoutesData\RouteData;
use Tests\Tools\PestRoutesData\SpotData;

class PestRoutesRouteTranslatorTest extends TestCase
{
    private PestRoutesRouteTranslator $routeTranslator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->routeTranslator = new PestRoutesRouteTranslator(
            new PestRoutesServiceProTranslator(Mockery::mock(HRService::class))
        );
    }

    /**
     * @test
     */
    public function it_translates_route_from_pest_routes_to_domain(): void
    {
        /** @var Employee $employee */
        $employee = EmployeeData::getTestData()->first();

        /** @var PestRoutesRoute $route */
        $route = RouteData::getTestData(1, [
            'assignedTech' => $employee->id,
        ])->first();
        $spotsCollection = SpotData::getTestData($this->faker->numberBetween(16, 21));

        $result = $this->routeTranslator->toDomain($route, $employee, $spotsCollection);

        $this->assertEquals($result->getServicePro()->getId(), $employee->id);
        $this->assertEquals($result->getId(), $route->id);
        $this->assertEquals($result->getOfficeId(), $route->officeId);
    }
}
