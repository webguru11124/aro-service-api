<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Services;

use App\Domain\Contracts\Repositories\ServiceHistoryRepository;
use App\Domain\RouteOptimization\Entities\ServiceHistory;
use App\Domain\RouteOptimization\Enums\ServiceType;
use App\Domain\RouteOptimization\Services\AverageDurationService;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\ServiceHistoryFactory;
use Tests\Tools\TestValue;

class AverageDurationServiceTest extends TestCase
{
    private const TTL = 5; //5 min
    private const CUSTOMER_IDS = [1111, 2222];
    private const QUARTER = 1;

    private ServiceHistoryRepository|MockInterface $serviceHistoryRepositoryMock;
    private AverageDurationService $averageDurationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serviceHistoryRepositoryMock = \Mockery::mock(ServiceHistoryRepository::class);

        $this->averageDurationService = new AverageDurationService($this->serviceHistoryRepositoryMock);
    }

    /**
     * @test
     *
     * @dataProvider preloadDataProvider
     *
     * @param Collection<ServiceHistory> $appointments
     * @param array<int, Duration|null> $expectedCacheData
     *
     * @return void
     */
    public function it_preloads_data(Collection $appointments, array $expectedCacheData): void
    {
        $this->serviceHistoryRepositoryMock
            ->shouldReceive('searchByCustomerIdAndOfficeId')
            ->with(TestValue::OFFICE_ID, ...self::CUSTOMER_IDS)
            ->once()
            ->andReturn($appointments);

        $expectedTtl = CarbonInterval::minutes(self::TTL);

        foreach ($expectedCacheData as $datum) {
            Cache::shouldReceive('put')
                ->withArgs(function (string $key, array $cacheData, CarbonInterval $ttl) use ($datum, $expectedTtl) {
                    $expectedDuration = $datum['duration'] === null
                        ? null
                        : Duration::fromMinutes($datum['duration']);
                    $expectedKey = self::getCacheKey($datum['customer_id']);

                    return $key === $expectedKey
                        && $cacheData['data']?->getTotalMinutes() == $expectedDuration?->getTotalMinutes()
                        && $ttl->minutes === $expectedTtl->minutes;
                })
                ->once()
                ->andReturnNull();
        }

        $this->averageDurationService->preload(TestValue::OFFICE_ID, self::QUARTER, ...self::CUSTOMER_IDS);
    }

    public static function preloadDataProvider(): iterable
    {
        yield [
            'appointments' => new Collection([
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[0],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(20),
                    'date' => Carbon::parse('2022-01-02'),
                ]),
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[0],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(15),
                    'date' => Carbon::parse('2022-02-02'),
                ]),
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[0],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(16),
                    'date' => Carbon::parse('2022-03-02'),
                ]),
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[1],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(10),
                    'date' => Carbon::parse('2022-01-02'),
                ]),
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[1],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(12),
                    'date' => Carbon::parse('2022-02-02'),
                ]),
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[1],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(14),
                    'date' => Carbon::parse('2022-03-02'),
                ]),
            ]),
            'expected result' => [
                ['customer_id' => self::CUSTOMER_IDS[0], 'duration' => 17],
                ['customer_id' => self::CUSTOMER_IDS[1], 'duration' => 12],
            ],
        ];

        yield [
            'appointments' => new Collection([
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[0],
                    'serviceType' => ServiceType::INITIAL,
                    'duration' => Duration::fromMinutes(20),
                    'date' => Carbon::parse('2022-01-02'),
                ]),
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[0],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(15),
                    'date' => Carbon::parse('2022-02-02'),
                ]),
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[0],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(16),
                    'date' => Carbon::parse('2022-03-02'),
                ]),
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[1],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(10),
                    'date' => Carbon::parse('2022-01-02'),
                ]),
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[1],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(12),
                    'date' => Carbon::parse('2022-02-02'),
                ]),
            ]),
            'expected result' => [
                ['customer_id' => self::CUSTOMER_IDS[0], 'duration' => null],
                ['customer_id' => self::CUSTOMER_IDS[1], 'duration' => null],
            ],
        ];

        yield [
            'appointments' => new Collection([
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[0],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(20),
                    'date' => Carbon::parse('2022-01-02'),
                ]),
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[0],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(15),
                    'date' => Carbon::parse('2022-02-02'),
                ]),
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[0],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(16),
                    'date' => Carbon::parse('2022-03-02'),
                ]),
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[0],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(61),
                    'date' => Carbon::parse('2022-03-02'),
                ]),
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[0],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(2),
                    'date' => Carbon::parse('2022-03-02'),
                ]),
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[1],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(10),
                    'date' => Carbon::parse('2022-01-02'),
                ]),
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[1],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(12),
                    'date' => Carbon::parse('2022-04-02'),
                ]),
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[1],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(14),
                    'date' => Carbon::parse('2022-05-02'),
                ]),
            ]),
            'expected result' => [
                ['customer_id' => self::CUSTOMER_IDS[0], 'duration' => 17],
                ['customer_id' => self::CUSTOMER_IDS[1], 'duration' => null],
            ],
        ];

        yield [
            'appointments' => new Collection(),
            'expected result' => [
                ['customer_id' => self::CUSTOMER_IDS[0], 'duration' => null],
                ['customer_id' => self::CUSTOMER_IDS[1], 'duration' => null],
            ],
        ];
    }

    private static function getCacheKey(int $customerId): string
    {
        return md5('average_duration_' . $customerId . '_' . self::QUARTER);
    }

    /**
     * @test
     *
     * @dataProvider calculateDataProvider
     *
     * @param Collection $appointments
     * @param int $expectedDuration
     *
     * @return void
     */
    public function it_calculates_average_service_duration(Collection $appointments, int|null $expectedDuration): void
    {
        Cache::shouldReceive('has')
            ->once()
            ->andReturnFalse();

        $this->serviceHistoryRepositoryMock
            ->shouldReceive('searchByCustomerIdAndOfficeId')
            ->with(TestValue::OFFICE_ID, self::CUSTOMER_IDS[0])
            ->once()
            ->andReturn($appointments);

        $result = $this->averageDurationService->getAverageServiceDuration(
            TestValue::OFFICE_ID,
            self::CUSTOMER_IDS[0],
            self::QUARTER
        );

        $this->assertEquals($expectedDuration, $result?->getTotalMinutes());
    }

    public static function calculateDataProvider(): iterable
    {
        yield [
            'appointments' => new Collection([
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[0],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(20),
                    'date' => Carbon::parse('2022-01-02'),
                ]),
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[0],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(15),
                    'date' => Carbon::parse('2022-02-02'),
                ]),
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[0],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(16),
                    'date' => Carbon::parse('2022-03-02'),
                ]),
            ]),
            'expected result' => 17,
        ];

        yield [
            'appointments' => new Collection([
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[0],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(20),
                    'date' => Carbon::parse('2022-01-02'),
                ]),
            ]),
            'expected result' => null,
        ];

        yield [
            'appointments' => new Collection([
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[0],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(20),
                    'date' => Carbon::parse('2022-01-02'),
                ]),
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[0],
                    'serviceType' => ServiceType::INITIAL,
                    'duration' => Duration::fromMinutes(15),
                    'date' => Carbon::parse('2022-02-02'),
                ]),
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[0],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(16),
                    'date' => Carbon::parse('2022-03-02'),
                ]),
            ]),
            'expected result' => null,
        ];

        yield [
            'appointments' => new Collection([
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[0],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(20),
                    'date' => Carbon::parse('2022-01-02'),
                ]),
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[0],
                    'serviceType' => ServiceType::INITIAL,
                    'duration' => Duration::fromMinutes(15),
                    'date' => Carbon::parse('2022-02-02'),
                ]),
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[0],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(61),
                    'date' => Carbon::parse('2022-03-02'),
                ]),
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[0],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(3),
                    'date' => Carbon::parse('2022-03-02'),
                ]),
            ]),
            'expected result' => null,
        ];

        yield [
            'appointments' => new Collection([
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[0],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(20),
                    'date' => Carbon::parse('2022-01-02'),
                ]),
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[0],
                    'serviceType' => ServiceType::INITIAL,
                    'duration' => Duration::fromMinutes(15),
                    'date' => Carbon::parse('2022-02-02'),
                ]),
                ServiceHistoryFactory::make([
                    'customerId' => self::CUSTOMER_IDS[0],
                    'serviceType' => ServiceType::REGULAR,
                    'duration' => Duration::fromMinutes(16),
                    'date' => Carbon::parse('2022-04-02'),
                ]),
            ]),
            'expected result' => null,
        ];
    }

    /**
     * @test
     */
    public function it_takes_average_duration_from_cache(): void
    {
        $duration = Duration::fromMinutes($this->faker->numberBetween(5, 60));
        $cacheKey = self::getCacheKey(self::CUSTOMER_IDS[0]);

        Cache::shouldReceive('has')
            ->with($cacheKey)
            ->once()
            ->andReturnTrue();

        Cache::shouldReceive('get')
            ->with($cacheKey)
            ->once()
            ->andReturn(['data' => $duration]);

        $this->serviceHistoryRepositoryMock
            ->shouldReceive('searchByCustomerIdAndOfficeId')
            ->never();

        $result = $this->averageDurationService->getAverageServiceDuration(
            TestValue::OFFICE_ID,
            self::CUSTOMER_IDS[0],
            self::QUARTER
        );

        $this->assertSame($duration, $result);
    }
}
