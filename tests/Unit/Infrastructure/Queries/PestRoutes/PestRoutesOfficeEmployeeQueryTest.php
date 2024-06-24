<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Queries\PestRoutes;

use App\Infrastructure\Queries\PestRoutes\PestRoutesOfficeEmployeeQuery;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesEmployeesDataProcessor;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\EmployeeData;
use Tests\Tools\TestValue;

class PestRoutesOfficeEmployeeQueryTest extends TestCase
{
    private MockInterface|PestRoutesEmployeesDataProcessor $dataProcessorMock;
    private PestRoutesOfficeEmployeeQuery $query;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataProcessorMock = \Mockery::mock(PestRoutesEmployeesDataProcessor::class);
        $this->query = new PestRoutesOfficeEmployeeQuery($this->dataProcessorMock);
    }

    /** @test */
    public function it_correctly_fetches_employees_for_an_office_id(): void
    {
        $officeStaffData = EmployeeData::getTestData(2, ['officeId' => TestValue::OFFICE_ID, 'type' => 0]);
        $technicianData = EmployeeData::getTestData(2, ['officeId' => TestValue::OFFICE_ID, 'type' => 1]);

        $this->dataProcessorMock
            ->shouldReceive('extract')
            ->twice()
            ->andReturn($officeStaffData, $technicianData);

        $employees = $this->query->find(TestValue::OFFICE_ID);

        $this->assertCount(4, $employees);
    }

    /** @test */
    public function it_correctly_fetches_employees_for_an_office_id_and_sorts_them_by_name(): void
    {
        $officeStaffData = EmployeeData::getTestData(1, [
            'officeId' => TestValue::OFFICE_ID,
            'type' => 0,
            'fname' => 'A',
            'lname' => 'Employee 1',
        ]);
        $technicianData = EmployeeData::getTestData(1, [
            'officeId' => TestValue::OFFICE_ID,
            'type' => 1,
            'fname' => 'B',
            'lname' => 'Employee 2',
        ]);

        $this->dataProcessorMock
            ->shouldReceive('extract')
            ->twice()
            ->andReturn($officeStaffData, $technicianData);

        $employees = $this->query->find(TestValue::OFFICE_ID);

        $this->assertEquals('A Employee 1', $employees->first()->getName());
        $this->assertEquals('B Employee 2', $employees->last()->getName());
    }
}
