<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\PestRoutes;

use App\Domain\RouteOptimization\Entities\ServiceHistory;
use App\Domain\RouteOptimization\Enums\ServiceType;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\AppointmentsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesServiceTypesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\PestRoutesServiceHistoryRepository;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesServiceHistoryTranslator;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesServiceTypeTranslator;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\Params\SearchServiceTypesParams;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\ServiceHistoryFactory;
use Tests\Tools\PestRoutesData\AppointmentData;
use Tests\Tools\PestRoutesData\ServiceTypeData;
use Tests\Tools\TestValue;

class PestRoutesServiceHistoryRepositoryTest extends TestCase
{
    private const CUSTOMERS_IDS = [624552, 24526];

    private MockInterface|AppointmentsDataProcessor $appointmentsDataProcessorMock;
    private MockInterface|PestRoutesServiceTypesDataProcessor $serviceTypeDataProcessorMock;
    private PestRoutesServiceHistoryRepository $serviceHistoryRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->appointmentsDataProcessorMock = \Mockery::mock(AppointmentsDataProcessor::class);
        $this->serviceTypeDataProcessorMock = \Mockery::mock(PestRoutesServiceTypesDataProcessor::class);
        $translator = new PestRoutesServiceHistoryTranslator(
            new PestRoutesServiceTypeTranslator()
        );

        $this->serviceHistoryRepository = new PestRoutesServiceHistoryRepository(
            $this->appointmentsDataProcessorMock,
            $this->serviceTypeDataProcessorMock,
            $translator,
        );
    }

    /**
     * @test
     *
     * @dataProvider searchByCustomerAndOfficeDataProvider
     *
     * @param Collection $pestRoutesAppointments
     * @param Collection $pestRoutesServiceTypes
     * @param array $serviceTypesIds
     * @param array $expected
     *
     * @return void
     * @throws InternalServerErrorHttpException
     */
    public function it_searches_service_history_by_customer_and_office_id(
        Collection $pestRoutesAppointments,
        Collection $pestRoutesServiceTypes,
        array $serviceTypesIds,
        array $expected
    ): void {
        $this->appointmentsDataProcessorMock
            ->shouldReceive('extract')
            ->withArgs(function (int $officeId, SearchAppointmentsParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['officeIDs'] === [TestValue::OFFICE_ID]
                    && $array['status'] === AppointmentStatus::Completed
                    && $array['customerIDs'] === self::CUSTOMERS_IDS;
            })
            ->once()
            ->andReturn($pestRoutesAppointments);

        if ($pestRoutesAppointments->isEmpty()) {
            $this->serviceTypeDataProcessorMock
                ->shouldReceive('extract')
                ->never();
        } else {
            $this->serviceTypeDataProcessorMock
                ->shouldReceive('extract')
                ->withArgs(function (int $officeId, SearchServiceTypesParams $params) use ($serviceTypesIds) {
                    $array = $params->toArray();

                    return $officeId === TestValue::OFFICE_ID
                        && $array['officeIDs'] === [TestValue::OFFICE_ID]
                        && $array['typeIDs'] === $serviceTypesIds;
                })
                ->once()
                ->andReturn($pestRoutesServiceTypes);
        }

        $result = $this->serviceHistoryRepository->searchByCustomerIdAndOfficeId(
            TestValue::OFFICE_ID,
            ...self::CUSTOMERS_IDS
        );

        /**
         * @var int $key
         * @var ServiceHistory $item
         */
        foreach ($result->toArray() as $key => $item) {
            $this->assertEquals($item, $expected[$key]);
        }
    }

    /**
     * @test
     */
    public function it_ignores_missing_service_type_id(): void
    {
        $dateCompleted = '2022-02-24';
        $checkIn = $dateCompleted . ' 09:00:00';
        $checkOut = $dateCompleted . ' 09:20:00';

        $appointmentSubstitutions = [
            'dateCompleted' => $dateCompleted,
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
        ];
        $testSubstitution = $appointmentSubstitutions + [
            'type' => 8675309,
        ];
        $substitutions = [
            $testSubstitution,
            $appointmentSubstitutions,
            $appointmentSubstitutions,
        ];
        $pestRoutesAppointments = AppointmentData::getTestData(3, ...$substitutions);

        $this->appointmentsDataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->andReturn($pestRoutesAppointments);

        $pestRoutesServiceTypes = ServiceTypeData::getTestDataOfTypes(
            ServiceTypeData::PREMIUM,
            ServiceTypeData::RESERVICE,
            ServiceTypeData::INITIAL,
            ServiceTypeData::PRO,
            ServiceTypeData::PRO_PLUS,
        );

        $this->serviceTypeDataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->andReturn($pestRoutesServiceTypes);

        $result = $this->serviceHistoryRepository->searchByCustomerIdAndOfficeId(
            TestValue::OFFICE_ID,
            ...self::CUSTOMERS_IDS
        );

        $this->assertCount(2, $result);
    }

    public static function searchByCustomerAndOfficeDataProvider(): iterable
    {
        $dateCompleted = '2022-02-24';
        $checkIn = $dateCompleted . ' 09:00:00';
        $checkOut = $dateCompleted . ' 09:20:00';
        $duration = Carbon::parse($checkIn)->diffInSeconds(Carbon::parse($checkOut));

        $appointmentSubstitutions = [
            'officeID' => TestValue::OFFICE_ID,
            'dateCompleted' => $dateCompleted,
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
        ];

        $serviceHistoryOverride = [
            'officeId' => TestValue::OFFICE_ID,
            'duration' => Duration::fromSeconds($duration),
            'date' => Carbon::parse($checkIn, TestValue::CUSTOMER_TIME_ZONE),
        ];

        yield [
            AppointmentData::getTestData(
                6,
                array_merge($appointmentSubstitutions, [
                    'appointmentID' => 1,
                    'customerID' => self::CUSTOMERS_IDS[0],
                    'type' => ServiceTypeData::PREMIUM,
                ]),
                array_merge($appointmentSubstitutions, [
                    'appointmentID' => 2,
                    'customerID' => self::CUSTOMERS_IDS[0],
                    'type' => ServiceTypeData::RESERVICE,
                ]),
                array_merge($appointmentSubstitutions, [
                    'appointmentID' => 3,
                    'customerID' => self::CUSTOMERS_IDS[0],
                    'type' => ServiceTypeData::INITIAL,
                ]),
                array_merge($appointmentSubstitutions, [
                    'appointmentID' => 4,
                    'customerID' => self::CUSTOMERS_IDS[1],
                    'type' => ServiceTypeData::PRO,
                ]),
                array_merge($appointmentSubstitutions, [
                    'appointmentID' => 5,
                    'customerID' => self::CUSTOMERS_IDS[1],
                    'type' => ServiceTypeData::PRO_PLUS,
                ]),
                array_merge($appointmentSubstitutions, [
                    'appointmentID' => 6,
                    'customerID' => self::CUSTOMERS_IDS[1],
                    'type' => ServiceTypeData::PREMIUM,
                ]),
            ),
            ServiceTypeData::getTestDataOfTypes(
                ...$serviceTypesIds = [
                    ServiceTypeData::PREMIUM,
                    ServiceTypeData::RESERVICE,
                    ServiceTypeData::INITIAL,
                    ServiceTypeData::PRO,
                    ServiceTypeData::PRO_PLUS,
                ]
            ),
            $serviceTypesIds,
            [
                ServiceHistoryFactory::make(array_merge($serviceHistoryOverride, [
                    'id' => 1,
                    'customerId' => self::CUSTOMERS_IDS[0],
                    'serviceType' => ServiceType::REGULAR,
                ])),
                ServiceHistoryFactory::make(array_merge($serviceHistoryOverride, [
                    'id' => 2,
                    'customerId' => self::CUSTOMERS_IDS[0],
                    'serviceType' => ServiceType::RESERVICE,
                ])),
                ServiceHistoryFactory::make(array_merge($serviceHistoryOverride, [
                    'id' => 3,
                    'customerId' => self::CUSTOMERS_IDS[0],
                    'serviceType' => ServiceType::INITIAL,
                ])),
                ServiceHistoryFactory::make(array_merge($serviceHistoryOverride, [
                    'id' => 4,
                    'customerId' => self::CUSTOMERS_IDS[1],
                    'serviceType' => ServiceType::REGULAR,
                ])),
                ServiceHistoryFactory::make(array_merge($serviceHistoryOverride, [
                    'id' => 5,
                    'customerId' => self::CUSTOMERS_IDS[1],
                    'serviceType' => ServiceType::REGULAR,
                ])),
                ServiceHistoryFactory::make(array_merge($serviceHistoryOverride, [
                    'id' => 6,
                    'customerId' => self::CUSTOMERS_IDS[1],
                    'serviceType' => ServiceType::REGULAR,
                ])),
            ],
        ];
        yield [
            new Collection(),
            new Collection(),
            [],
            [],
        ];
    }
}
