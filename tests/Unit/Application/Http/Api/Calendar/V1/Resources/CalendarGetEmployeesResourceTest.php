<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Calendar\V1\Resources;

use App\Application\Http\Api\Calendar\V1\Resources\CalendarGetEmployeesResource;
use App\Domain\Calendar\Entities\Employee;
use Illuminate\Http\Request;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\TestValue;

class CalendarGetEmployeesResourceTest extends TestCase
{
    private CalendarGetEmployeesResource $resource;
    private Request|MockInterface $request;

    protected function setup(): void
    {
        parent::setUp();

        $this->request = Mockery::mock(Request::class);
    }

    /**
     * @test
     */
    public function it_creates_expected_array_representation_of_resource(): void
    {
        $employee = new Employee(
            TestValue::EMPLOYEE1_ID,
            'John Doe',
            TestValue::WORKDAY_ID,
        );

        $this->resource = new CalendarGetEmployeesResource($employee);
        $resource = $this->resource->toArray($this->request);

        $this->assertEquals(
            [
                'id' => $employee->getId(),
                'name' => $employee->getName(),
                'external_id' => $employee->getWorkdayId(),
            ],
            $resource
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->request);
        unset($this->resource);
    }
}
