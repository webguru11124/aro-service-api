<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Api\Scheduling\V1\Controllers;

use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Infrastructure\Exceptions\AppointmentNotFoundException;
use App\Infrastructure\Exceptions\SpotNotFoundException;
use App\Infrastructure\Queries\PestRoutes\PestRoutesOfficeQuery;
use App\Infrastructure\Services\PestRoutes\Actions\CreateAppointment;
use App\Infrastructure\Services\PestRoutes\Actions\FindAvailableSpots;
use App\Infrastructure\Services\PestRoutes\Actions\RescheduleAppointment;
use App\Infrastructure\Services\PestRoutes\Entities\SpotStrategies\BucketSpotStrategy;
use App\Infrastructure\Services\PestRoutes\Enums\AppointmentType;
use App\Infrastructure\Services\PestRoutes\Enums\RequestingSource;
use App\Infrastructure\Services\PestRoutes\Enums\ServiceType;
use App\Infrastructure\Services\PestRoutes\Enums\Window;
use Aptive\PestRoutesSDK\Exceptions\PestRoutesApiException;
use Carbon\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\Factories\SpotFactory;
use Tests\Tools\TestValue;

class SchedulingControllerTest extends TestCase
{
    private const ROUTE_AVAILABLE_SPOTS = 'scheduling.available-spots.index';
    private const ROUTE_CREATE_APPOINTMENT = 'scheduling.appointments.create';
    private const ROUTE_RESCHEDULE_APPOINTMENT = 'scheduling.appointments.reschedule';

    private const VALID_DATA_AVAILABLE_SPOTS = [
        'office_id' => TestValue::OFFICE_ID,
        'lat' => TestValue::LATITUDE,
        'lng' => TestValue::LONGITUDE,
        'state' => TestValue::USA_STATE,
        'is_initial' => 0,
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-30',
        'distance_threshold' => 10,
        'limit' => 2,
    ];

    private const VALID_DATA_CREATE_APPOINTMENT = [
        'office_id' => TestValue::OFFICE_ID,
        'customer_id' => TestValue::CUSTOMER_ID,
        'spot_id' => TestValue::SPOT_ID,
        'subscription_id' => TestValue::SUBSCRIPTION_ID,
        'appointment_type' => AppointmentType::BASIC->value,
        'window' => 'AM',
        'requesting_source' => RequestingSource::TEST->value,
        'execution_sid' => 'TESTEXSID',
        'notes' => 'Test notes',
        'is_aro_spot' => 0,
    ];

    private const VALID_DATA_RESCHEDULE_APPOINTMENT = [
        'office_id' => TestValue::OFFICE_ID,
        'customer_id' => TestValue::CUSTOMER_ID,
        'spot_id' => TestValue::SPOT_ID,
        'subscription_id' => TestValue::SUBSCRIPTION_ID,
        'current_appt_type' => ServiceType::BASIC->value,
        'window' => Window::AM->value,
        'requesting_source' => RequestingSource::TEST->value,
        'execution_sid' => 'TESTEXSID',
        'notes' => 'Test notes',
        'is_aro_spot' => 0,
    ];

    private PestRoutesOfficeQuery|MockInterface $officeQueryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->officeQueryMock = \Mockery::mock(PestRoutesOfficeQuery::class);
        $this->instance(PestRoutesOfficeQuery::class, $this->officeQueryMock);
    }

    /**
     * @test
     */
    public function available_spots_returns_proper_response(): void
    {
        $office = OfficeFactory::make([
            'id' => TestValue::OFFICE_ID,
        ]);

        $this->officeQueryMock
            ->shouldReceive('get')
            ->with(TestValue::OFFICE_ID)
            ->once()
            ->andReturn($office);

        $spots = collect([
            SpotFactory::make([
                'strategy' => new BucketSpotStrategy(),
                'id' => TestValue::SPOT_ID,
                'timeWindow' => new TimeWindow(
                    Carbon::tomorrow()->hour(8),
                    Carbon::tomorrow()->hour(8)->minute(29)
                ),
            ]),
        ]);

        $action = \Mockery::mock(FindAvailableSpots::class);
        $this->instance(FindAvailableSpots::class, $action);

        $action
            ->shouldReceive('__invoke')
            ->once()
            ->andReturn($spots);

        $response = $this->get(
            route(self::ROUTE_AVAILABLE_SPOTS, self::VALID_DATA_AVAILABLE_SPOTS),
            $this->getHeaders()
        );

        $response->assertOk();
        $response->assertJsonStructure([
            '_metadata' => ['success'],
            'result' => ['spots' => [['spot_id', 'date', 'window', 'is_aro_spot']]],
        ]);
    }

    /**
     * @test
     */
    public function available_spots_return_400_if_invalid_params_sent(): void
    {
        $params = array_diff_key(self::VALID_DATA_AVAILABLE_SPOTS, [
            'office_id' => null,
        ]);

        $response = $this->get(
            route(self::ROUTE_AVAILABLE_SPOTS, $params),
            $this->getHeaders()
        );

        $response->assertBadRequest();
    }

    /**
     * @test
     */
    public function create_appointment_returns_proper_response(): void
    {
        $office = OfficeFactory::make([
            'id' => TestValue::OFFICE_ID,
        ]);

        $this->officeQueryMock
            ->shouldReceive('get')
            ->with(TestValue::OFFICE_ID)
            ->once()
            ->andReturn($office);

        $action = \Mockery::mock(CreateAppointment::class);
        $this->instance(CreateAppointment::class, $action);

        $action
            ->shouldReceive('__invoke')
            ->once()
            ->andReturn(TestValue::APPOINTMENT_ID);

        $response = $this->postJson(
            route(self::ROUTE_CREATE_APPOINTMENT),
            self::VALID_DATA_CREATE_APPOINTMENT,
            $this->getHeaders()
        );

        $response->assertCreated();
        $response->assertJsonStructure([
            '_metadata' => ['success'],
            'result' => ['message', 'id', 'execution_sid'],
        ]);
    }

    /**
     * @test
     */
    public function create_returns_400_if_invalid_params_sent(): void
    {
        $params = array_diff_key(self::VALID_DATA_CREATE_APPOINTMENT, [
            'office_id' => null,
        ]);

        $response = $this->postJson(
            route(self::ROUTE_CREATE_APPOINTMENT),
            $params,
            $this->getHeaders()
        );

        $response->assertBadRequest();
    }

    /**
     * @test
     *
     * @dataProvider exceptionsDataProvider
     */
    public function create_appointment_shows_readable_errors(\Throwable $exception, string $errorMessage): void
    {
        $office = OfficeFactory::make([
            'id' => TestValue::OFFICE_ID,
        ]);

        $this->officeQueryMock
            ->shouldReceive('get')
            ->andReturn($office);

        $action = \Mockery::mock(CreateAppointment::class);
        $this->instance(CreateAppointment::class, $action);

        $action
            ->shouldReceive('__invoke')
            ->andThrow($exception);

        $response = $this->postJson(
            route(self::ROUTE_CREATE_APPOINTMENT),
            self::VALID_DATA_CREATE_APPOINTMENT,
            $this->getHeaders()
        );

        $response->assertUnprocessable();
        $response->assertJsonPath('_metadata.success', false);
        $response->assertJsonPath('result.message', $errorMessage);
    }

    /**
     * @test
     */
    public function reschedule_returns_proper_response(): void
    {
        $office = OfficeFactory::make([
            'id' => TestValue::OFFICE_ID,
        ]);

        $this->officeQueryMock
            ->shouldReceive('get')
            ->with(TestValue::OFFICE_ID)
            ->once()
            ->andReturn($office);

        $action = \Mockery::mock(RescheduleAppointment::class);
        $this->instance(RescheduleAppointment::class, $action);

        $action
            ->shouldReceive('__invoke')
            ->once()
            ->andReturn(TestValue::APPOINTMENT_ID);

        $response = $this->putJson(
            route(self::ROUTE_RESCHEDULE_APPOINTMENT, ['id' => TestValue::APPOINTMENT_ID]),
            self::VALID_DATA_RESCHEDULE_APPOINTMENT,
            $this->getHeaders(),
        );

        $response->assertOk();
        $response->assertJsonStructure([
            '_metadata' => ['success'],
            'result' => ['message', 'id', 'execution_sid'],
        ]);
    }

    /**
     * @test
     */
    public function reschedule_returns_400_if_invalid_params_sent(): void
    {
        $params = array_diff_key(self::VALID_DATA_RESCHEDULE_APPOINTMENT, [
            'office_id' => null,
        ]);

        $response = $this->putJson(
            route(self::ROUTE_RESCHEDULE_APPOINTMENT, ['id' => TestValue::APPOINTMENT_ID]),
            $params,
            $this->getHeaders()
        );

        $response->assertBadRequest();
    }

    /**
     * @test
     */
    public function reschedule_returns_404_if_appointment_not_found(): void
    {
        $this->officeQueryMock
            ->shouldReceive('get')
            ->with(TestValue::OFFICE_ID)
            ->once()
            ->andReturn(OfficeFactory::make());

        $action = \Mockery::mock(RescheduleAppointment::class);
        $this->instance(RescheduleAppointment::class, $action);

        $action
            ->shouldReceive('__invoke')
            ->andThrow(AppointmentNotFoundException::instance(TestValue::APPOINTMENT_ID));

        $response = $this->putJson(
            route(self::ROUTE_RESCHEDULE_APPOINTMENT, ['id' => TestValue::APPOINTMENT_ID]),
            self::VALID_DATA_RESCHEDULE_APPOINTMENT,
            $this->getHeaders()
        );

        $response->assertNotFound();
    }

    /**
     * @test
     */
    public function reschedule_returns_404_if_id_is_invalid(): void
    {
        $response = $this->putJson(
            route(self::ROUTE_RESCHEDULE_APPOINTMENT, ['id' => ':appointment_id']),
            self::VALID_DATA_RESCHEDULE_APPOINTMENT,
            $this->getHeaders()
        );

        $response->assertNotFound();
        $response->assertJsonPath('result.message', 'The resource you requested could not be found.');
    }

    /**
     * @test
     *
     * @dataProvider exceptionsDataProvider
     */
    public function reschedule_shows_readable_errors(\Throwable $exception, string $errorMessage): void
    {
        $office = OfficeFactory::make([
            'id' => TestValue::OFFICE_ID,
        ]);

        $this->officeQueryMock
            ->shouldReceive('get')
            ->andReturn($office);

        $action = \Mockery::mock(RescheduleAppointment::class);
        $this->instance(RescheduleAppointment::class, $action);

        $action
            ->shouldReceive('__invoke')
            ->andThrow($exception);

        $response = $this->putJson(
            route(self::ROUTE_RESCHEDULE_APPOINTMENT, ['id' => TestValue::APPOINTMENT_ID]),
            self::VALID_DATA_RESCHEDULE_APPOINTMENT,
            $this->getHeaders(),
        );

        $response->assertUnprocessable();
        $response->assertJsonPath('_metadata.success', false);
        $response->assertJsonPath('result.message', $errorMessage);
    }

    public static function exceptionsDataProvider(): iterable
    {
        $errorMessage = 'Readable error message';

        yield [
            new PestRoutesApiException($errorMessage),
            $errorMessage,
        ];

        yield [
            new SpotNotFoundException($errorMessage),
            $errorMessage,
        ];
    }

    private function getHeaders(): array
    {
        return [];
    }
}
