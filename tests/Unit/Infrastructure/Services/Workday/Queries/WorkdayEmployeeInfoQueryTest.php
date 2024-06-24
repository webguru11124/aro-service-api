<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Workday\Queries;

use App\Domain\Contracts\Queries\Params\EmployeeInfoQueryParams;
use Tests\TestCase;
use Mockery;
use Illuminate\Support\Facades\Config;
use App\Infrastructure\Services\Workday\WorkdayAPIClient;
use App\Infrastructure\Services\Workday\Queries\WorkdayEmployeeInfoQuery;
use App\Infrastructure\Services\Workday\Exceptions\WorkdayErrorException;
use App\Domain\SharedKernel\Entities\Employee;
use App\Domain\SharedKernel\Entities\Skill;
use App\Domain\SharedKernel\Entities\WorkPeriod;
use App\Domain\SharedKernel\ValueObjects\Address;
use Illuminate\Support\Collection;
use Tests\Tools\Workday\StubServiceProReportResponse;

class WorkdayEmployeeInfoQueryTest extends TestCase
{
    private const WORKDAY_REPORT_URL = 'valid-test-url';

    private WorkdayAPIClient|Mockery\MockInterface $mockWorkdayAPIClient;
    private WorkdayEmployeeInfoQuery $query;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockWorkdayAPIClient = Mockery::mock(WorkdayAPIClient::class);
        $this->query = new WorkdayEmployeeInfoQuery($this->mockWorkdayAPIClient);

        Config::set('workday.services.service_pro_info_report_url', self::WORKDAY_REPORT_URL);
    }

    /**
     * @test
     */
    public function it_fetches_service_pro_info(): void
    {
        $queryParams = new EmployeeInfoQueryParams();
        $serviceProDataResponseAsArray = StubServiceProReportResponse::responseWithSuccess();
        $expectedEntries = new Collection([$this->getSampleExpectedEntry()]);

        $this->mockWorkdayAPIClient
            ->shouldReceive('get')
            ->with(self::WORKDAY_REPORT_URL, $queryParams->toArray())
            ->andReturn($serviceProDataResponseAsArray);

        $result = $this->query->get($queryParams);
        $this->assertEquals($expectedEntries, $result);
    }

    /**
     * @test
     */
    public function it_throws_exception_on_invalid_xml_response(): void
    {
        $queryParams = new EmployeeInfoQueryParams();
        $invalidXmlResponse = 'invalid response';

        $this->mockWorkdayAPIClient
            ->shouldReceive('get')
            ->with(self::WORKDAY_REPORT_URL, $queryParams->toArray())
            ->andReturn($invalidXmlResponse);

        $this->expectException(WorkdayErrorException::class);
        $this->query->get($queryParams);
    }

    private function getSampleExpectedEntry(): Employee
    {
        return new Employee(
            'ABC123',
            'John',
            'Doe',
            '1980-01-01',
            '2015-06-01',
            'Jane Smith (ID123456)',
            'johndoe@gmail.com',
            '+1 (555) 123-4567',
            new Address('123 Main St', 'Springfield', 'State', '12345'),
            new WorkPeriod('Standard Work Schedule (M-F 9:00 AM - 5:00 PM) 1 hr lunch'),
            collect([
                new Skill('Basic Training'),
                new Skill('Advanced Training'),
            ]),
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->mockWorkdayAPIClient);
        unset($this->query);
    }
}
