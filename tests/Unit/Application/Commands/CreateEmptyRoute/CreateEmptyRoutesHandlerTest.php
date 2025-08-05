<?php

declare(strict_types=1);

namespace App\Application\Commands\CreateEmptyRoute;

use App\Domain\Contracts\Queries\GetRouteTemplateQuery;
use App\Domain\SharedKernel\Entities\Employee;
use App\Infrastructure\Helpers\DateTimeHelper;
use Illuminate\Support\Facades\Log;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\RoutesDataProcessor;
use Aptive\PestRoutesSDK\Resources\Routes\Params\CreateRoutesParams;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;

class CreateEmptyRoutesHandlerTest extends TestCase
{
    private RoutesDataProcessor|MockInterface $mockRoutesDataProcessor;
    private GetRouteTemplateQuery|MockInterface $mockRouteTemplateQuery;
    private CreateEmptyRoutesHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRoutesDataProcessor = Mockery::mock(RoutesDataProcessor::class);
        $this->mockRouteTemplateQuery = Mockery::mock(GetRouteTemplateQuery::class);
        $this->handler = new CreateEmptyRoutesHandler(
            $this->mockRoutesDataProcessor,
            $this->mockRouteTemplateQuery
        );
    }

    /**
     * @test
     */
    public function it_creates_empty_routes_for_employees(): void
    {
        $office = OfficeFactory::make();
        $date = Carbon::today($office->getTimezone());
        $employees = new Collection([Mockery::mock(Employee::class), Mockery::mock(Employee::class)]);
        $applicableTemplateId = 123;

        $employees->each(function ($employee) {
            $employee->shouldReceive('getEmployeeId')->andReturn(rand(1, 1000));
            $employee->shouldReceive('getFullName')->andReturn('Test Employee');
        });

        $command = new CreateEmptyRoutesCommand(
            office: $office,
            date: $date,
            employees: $employees
        );

        $this->mockRouteTemplateQuery
            ->shouldReceive('get')
            ->once()
            ->with($office)
            ->andReturn($applicableTemplateId);

        $employees->each(function ($employee) use ($office, $date, $applicableTemplateId) {
            $this->mockRoutesDataProcessor
                ->shouldReceive('create')
                ->once()
                ->with(
                    $office->getId(),
                    Mockery::on(function (CreateRoutesParams $params) use ($date, $applicableTemplateId, $employee, $office) {
                        return $params->toArray() === [
                            'date' => $date->format(DateTimeHelper::DATE_FORMAT),
                            'templateID' => $applicableTemplateId,
                            'assignedTech' => (int) $employee->getEmployeeId(),
                            'autoCreateGroup' => '1',
                            'officeID' => $office->getId(),
                        ];
                    })
                );

            Log::shouldReceive('info')
                ->once()
                ->with(__('messages.route_creation.created'), [
                    'service_pro' => 'Test Employee',
                    'service_pro_id' => $employee->getEmployeeId(),
                    'office' => $office->getName(),
                    'office_id' => $office->getId(),
                    'date' => $date->toDateString(),
                ]);
        });

        $this->handler->handle($command);

        $this->assertTrue(true, 'Mock expectations have been checked.');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
