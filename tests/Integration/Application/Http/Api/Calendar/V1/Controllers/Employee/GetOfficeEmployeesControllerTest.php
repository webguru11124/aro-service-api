<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Api\Calendar\V1\Controllers\Employee;

use App\Domain\Calendar\Entities\Employee;
use App\Domain\Contracts\Queries\Office\OfficeEmployeeQuery;
use App\Infrastructure\Queries\PestRoutes\PestRoutesOfficeEmployeeQuery;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesEmployeesDataProcessor;
use Aptive\PestRoutesSDK\Exceptions\ResourceNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\JWTAuthTokenHelper;
use Tests\Tools\TestValue;

class GetOfficeEmployeesControllerTest extends TestCase
{
    use DatabaseTransactions;
    use JWTAuthTokenHelper;

    private const ROUTE_GET_OFFICE_EMPLOYEES = 'calendar.office.employees.index';

    private PestRoutesEmployeesDataProcessor|MockInterface $pestRoutesEmployeesDataProcessor;

    /** @var string[] */
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->headers = [
            'Authorization' => 'Bearer ' . $this->generateValidJwtToken(),
        ];

        $this->pestRoutesEmployeesDataProcessor = Mockery::mock(PestRoutesEmployeesDataProcessor::class);
        $this->instance(PestRoutesEmployeesDataProcessor::class, $this->pestRoutesEmployeesDataProcessor);
    }

    /**
     * @test
     */
    public function it_returns_list_of_employees_for_office(): void
    {
        $officeId = TestValue::OFFICE_ID;
        $officeEmployeeQueryMock = Mockery::mock(PestRoutesOfficeEmployeeQuery::class);

        $officeEmployeeQueryMock
            ->shouldReceive('find')
            ->with($officeId)
            ->once()
            ->andReturn(collect([
                new Employee(
                    TestValue::EMPLOYEE1_ID,
                    'John Doe',
                ),
            ]));

        $this->instance(OfficeEmployeeQuery::class, $officeEmployeeQueryMock);

        $uri = route(self::ROUTE_GET_OFFICE_EMPLOYEES, ['office_id' => $officeId]);

        $response = $this
            ->withHeaders($this->getHeaders())
            ->get($uri);

        $response->assertOk();
        $response->assertJsonPath('_metadata.success', true);
    }

    /**
     * @test
     */
    public function it_returns_404_response_when_office_not_found(): void
    {
        $officeId = '123456';

        $officeEmployeeQueryMock = Mockery::mock(PestRoutesOfficeEmployeeQuery::class);

        $officeEmployeeQueryMock
            ->shouldReceive('find')
            ->with($officeId)
            ->once()
            ->andThrow(ResourceNotFoundException::class);

        $this->instance(OfficeEmployeeQuery::class, $officeEmployeeQueryMock);

        $uri = route(self::ROUTE_GET_OFFICE_EMPLOYEES, ['office_id' => $officeId]);

        $response = $this
            ->withHeaders($this->getHeaders())
            ->get($uri);

        $response->assertNotFound();
        $response->assertJsonPath('_metadata.success', false);
    }

    /**
     * @return mixed[]
     */
    private function getHeaders(): array
    {
        return $this->headers;
    }
}
