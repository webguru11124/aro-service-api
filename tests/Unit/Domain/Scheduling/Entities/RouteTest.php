<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Scheduling\Entities;

use App\Domain\Scheduling\Entities\Route;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Tests\TestCase;

class RouteTest extends TestCase
{
    private Route $route;
    private int $officeId;
    private CarbonInterface $date;
    private int $templateId;
    private int $id;
    private int $employeeId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->officeId = 1;
        $this->date = Carbon::now();
        $this->templateId = 123;
        $this->id = 456;
        $this->employeeId = 789;

        $this->route = new Route(
            id: $this->id,
            officeId: $this->officeId,
            date: $this->date,
            templateId: $this->templateId,
            employeeId: $this->employeeId
        );
    }

    /** @test */
    public function getters_return_expected_values(): void
    {
        $this->assertEquals($this->officeId, $this->route->getOfficeId());
        $this->assertInstanceOf(CarbonInterface::class, $this->route->getDate());
        $this->assertEquals($this->date, $this->route->getDate());
        $this->assertEquals($this->templateId, $this->route->getTemplateId());
        $this->assertEquals($this->id, $this->route->getId());
        $this->assertEquals($this->employeeId, $this->route->getEmployeeId());
    }

    /** @test */
    public function getters_return_null_when_values_not_set(): void
    {
        $routeWithoutId = new Route(
            id: $this->id,
            officeId: $this->officeId,
            date: $this->date,
            templateId: $this->templateId,
        );

        $this->assertNull($routeWithoutId->getEmployeeId());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset(
            $this->route,
            $this->officeId,
            $this->date,
            $this->templateId,
            $this->id,
            $this->employeeId
        );
    }
}
