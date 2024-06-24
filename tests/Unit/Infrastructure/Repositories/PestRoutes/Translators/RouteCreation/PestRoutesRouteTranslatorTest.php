<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\PestRoutes\Translators\RouteCreation;

use App\Domain\Scheduling\Entities\Route;
use App\Infrastructure\Repositories\PestRoutes\Translators\RouteCreation\PestRoutesRouteTranslator;
use Aptive\PestRoutesSDK\Resources\Routes\Route as PestRoutesRoute;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\PestRoutesData\RouteData;

class PestRoutesRouteTranslatorTest extends TestCase
{
    private PestRoutesRouteTranslator $routeTranslator;

    /**
     * @test
     */
    public function it_translates_route_from_pest_routes_to_domain(): void
    {
        $this->routeTranslator = new PestRoutesRouteTranslator();
        /** @var PestRoutesRoute $route */
        $route = RouteData::getTestData(1)->first();
        $office = OfficeFactory::make();

        $result = $this->routeTranslator->toDomain($route, $office);

        $this->assertInstanceOf(Route::class, $result);
        $this->assertEquals($result->getOfficeId(), $route->officeId);
        $this->assertEquals($result->getId(), $route->id);
        $this->assertEquals($result->getDate(), $route->date);
        $this->assertEquals($result->getTemplateId(), $route->templateId);
        $this->assertEquals($result->getEmployeeId(), $route->assignedTech);
    }
}
