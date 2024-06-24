<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\PestRoutes\DataProcessors;

use App\Infrastructure\Exceptions\IVRSchedulerNotFoundException;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesEmployeesDataProcessor;
use Aptive\PestRoutesSDK\Resources\Employees\EmployeesResource;
use Aptive\PestRoutesSDK\Resources\Employees\EmployeeType;
use Aptive\PestRoutesSDK\Resources\Employees\Params\CreateEmployeesParams;
use Aptive\PestRoutesSDK\Collection as PestRoutesCollection;
use Aptive\PestRoutesSDK\Resources\Employees\Params\SearchEmployeesParams;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\EmployeeData;
use Tests\Tools\TestValue;
use Tests\Traits\PestRoutesClientMockBuilderAware;

class PestRoutesEmployeesDataProcessorTest extends TestCase
{
    use PestRoutesClientMockBuilderAware;

    private const EMPLOYEE_ID = 73456;
    private const FIRST_NAME = 'FirstName';
    private const LAST_NAME = 'LastName';
    private const IVR_SCHEDULER_FNAME = 'IVR';

    /**
     * @test
     */
    public function it_extracts_employees(): void
    {
        $searchEmployeesParamsMock = \Mockery::mock(SearchEmployeesParams::class);
        $employees = EmployeeData::getTestData(random_int(2, 5));
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(EmployeesResource::class)
            ->callSequence('employees', 'includeData', 'search', 'all')
            ->methodExpectsArgs('search', [$searchEmployeesParamsMock])
            ->willReturn(new PestRoutesCollection($employees->all()))
            ->mock();

        $subject = new PestRoutesEmployeesDataProcessor($pestRoutesClientMock);

        $result = $subject->extract(TestValue::OFFICE_ID, $searchEmployeesParamsMock);

        $this->assertEquals($employees, $result);
    }

    /**
     * @test
     */
    public function it_creates_employee(): void
    {
        $client = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(EmployeesResource::class)
            ->callSequence('employees', 'create')
            ->methodExpectsArgs('update', function (CreateEmployeesParams $params) {
                $array = $params->toArray();

                return $array['type'] === EmployeeType::Technician
                    && $array['fname'] === self::FIRST_NAME
                    && $array['lname'] === self::LAST_NAME
                    && $array['officeID'] === TestValue::OFFICE_ID;
            })
            ->willReturn(self::EMPLOYEE_ID)
            ->mock();

        $employeePersister = new PestRoutesEmployeesDataProcessor($client);

        $result = $employeePersister->create(TestValue::OFFICE_ID, new CreateEmployeesParams(
            firstName: self::FIRST_NAME,
            lastName: self::LAST_NAME,
            type: EmployeeType::Technician,
            officeId: TestValue::OFFICE_ID
        ));

        $this->assertEquals(self::EMPLOYEE_ID, $result);
    }

    /**
     * @test
     */
    public function it_extracts_ivr_scheduler(): void
    {
        $searchParam = new SearchEmployeesParams(
            officeIds: [TestValue::OFFICE_ID],
            firstName: self::IVR_SCHEDULER_FNAME
        );

        $employees = EmployeeData::getTestData();

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(EmployeesResource::class)
            ->callSequence('employees', 'includeData', 'search', 'all')
            ->methodExpectsArgs('search', function (
                SearchEmployeesParams $params
            ) use ($searchParam) {
                return $params->toArray() === $searchParam->toArray();
            })
            ->willReturn(new PestRoutesCollection($employees->all()))
            ->mock();

        $dataProcessor = new PestRoutesEmployeesDataProcessor($pestRoutesClientMock);

        $result = $dataProcessor->extractIVRScheduler(TestValue::OFFICE_ID);

        $this->assertSame($employees->first(), $result);
    }

    /**
     * @test
     */
    public function extract_ivr_scheduler_throws_exception_if_not_found(): void
    {
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(EmployeesResource::class)
            ->callSequence('employees', 'includeData', 'search', 'all')
            ->willReturn(new PestRoutesCollection())
            ->mock();

        $dataProcessor = new PestRoutesEmployeesDataProcessor($pestRoutesClientMock);

        $this->expectException(IVRSchedulerNotFoundException::class);

        $dataProcessor->extractIVRScheduler(TestValue::OFFICE_ID);
    }
}
