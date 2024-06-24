<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers;

use App\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers\PestRoutesEmployeesDataProcessorCacheWrapper;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesEmployeesDataProcessor;
use Aptive\PestRoutesSDK\Resources\Employees\Params\CreateEmployeesParams;
use Aptive\PestRoutesSDK\Resources\Employees\Params\SearchEmployeesParams;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\EmployeeData;
use Tests\Tools\TestValue;

class PestRoutesEmployeesDataProcessorCacheWrapperTest extends TestCase
{
    private PestRoutesEmployeesDataProcessorCacheWrapper $wrapper;
    private PestRoutesEmployeesDataProcessor|MockInterface $wrappeeMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wrappeeMock = \Mockery::mock(PestRoutesEmployeesDataProcessor::class);

        $this->wrapper = new PestRoutesEmployeesDataProcessorCacheWrapper($this->wrappeeMock);
    }

    /**
     * @test
     */
    public function it_caches_ivr_scheduler(): void
    {
        $officeId = $this->faker->randomNumber(2);
        $employee = EmployeeData::getTestData()->first();

        $this->wrappeeMock->shouldReceive('extractIVRScheduler')
            ->with($officeId)
            ->once()
            ->andReturn($employee);

        $result1 = $this->wrapper->extractIVRScheduler($officeId);
        $result2 = $this->wrapper->extractIVRScheduler($officeId);

        $this->assertSame($employee, $result1);
        $this->assertSame($employee, $result2);
    }

    /**
     * @test
     */
    public function it_doesnt_cache_extract(): void
    {
        $officeId = $this->faker->randomNumber(2);
        $params = new SearchEmployeesParams();
        $employees = EmployeeData::getTestData();

        $this->wrappeeMock->shouldReceive('extract')
            ->with($officeId, $params)
            ->twice()
            ->andReturn($employees);

        $result1 = $this->wrapper->extract($officeId, $params);
        $result2 = $this->wrapper->extract($officeId, $params);

        $this->assertSame($employees, $result1);
        $this->assertSame($employees, $result2);
    }

    /**
     * @test
     */
    public function it_doesnt_cache_create(): void
    {
        $officeId = $this->faker->randomNumber(2);
        $params = new CreateEmployeesParams(
            firstName: $this->faker->firstName,
            lastName: $this->faker->lastName,
            officeId: $officeId
        );

        $this->wrappeeMock->shouldReceive('create')
            ->with($officeId, $params)
            ->twice()
            ->andReturn(TestValue::APPOINTMENT_ID);

        $result1 = $this->wrapper->create($officeId, $params);
        $result2 = $this->wrapper->create($officeId, $params);

        $this->assertSame(TestValue::APPOINTMENT_ID, $result1);
        $this->assertSame(TestValue::APPOINTMENT_ID, $result2);
    }
}
