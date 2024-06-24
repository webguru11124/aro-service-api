<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Services;

use App\Domain\Contracts\Repositories\ServiceHistoryRepository;
use App\Domain\RouteOptimization\DomainContext;
use App\Domain\RouteOptimization\Entities\ServiceHistory;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Carbon\CarbonInterval;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AverageDurationService
{
    private const AVERAGE_DURATION_PREFIX = 'average_duration_';
    private const CACHE_TTL = 5; //5 min
    private const MIN_APPOINTMENT_DURATION_INCLUDED_INTO_AVERAGE = 5; //5 min
    private const MAX_APPOINTMENT_DURATION_INCLUDED_INTO_AVERAGE = 60; //60 min

    public function __construct(
        private readonly ServiceHistoryRepository $serviceHistoryRepository
    ) {
    }

    /**
     * @param int $officeId
     * @param int $quarter
     * @param int ...$customerIds
     *
     * @return void
     */
    public function preload(int $officeId, int $quarter, int ...$customerIds): void
    {
        $nonInitialAppointments = $this->serviceHistoryRepository
            ->searchByCustomerIdAndOfficeId($officeId, ...$customerIds)
            ->filter($this->getFilter($quarter))
            ->groupBy(fn (ServiceHistory $appointment) => $appointment->getCustomerId());

        foreach ($customerIds as $customerId) {
            $appointmentsCollection = $nonInitialAppointments->get($customerId);

            $averageDuration = is_null($appointmentsCollection)
                ? null
                : $this->calculateAverageDuration($appointmentsCollection);

            $this->putToCache($this->buildCacheKey($customerId, $quarter), $averageDuration);
        }

        unset($nonInitialAppointments);
    }

    /**
     * @param int $customerId
     * @param int $quarter
     * @param int $officeId
     *
     * @return Duration|null
     */
    public function getAverageServiceDuration(int $officeId, int $customerId, int $quarter): Duration|null
    {
        $cacheKey = $this->buildCacheKey($customerId, $quarter);

        if ($this->isInCache($cacheKey)) {
            return $this->getFromCache($cacheKey);
        }

        $nonInitialAppointments = $this->serviceHistoryRepository
            ->searchByCustomerIdAndOfficeId($officeId, $customerId)
            ->filter($this->getFilter($quarter));

        return $this->calculateAverageDuration($nonInitialAppointments);
    }

    private function putToCache(string $key, Duration|null $duration): void
    {
        $cacheTtl = CarbonInterval::minutes(self::CACHE_TTL);
        Cache::put($key, ['data' => $duration], $cacheTtl);
    }

    private function getFromCache(string $key): Duration|null
    {
        //TODO: Needs to be refactored by replacing using Laravel Cache facade with implementation of a cache wrapper
        $data = Cache::get($key);

        return $data['data'] ?? null;
    }

    private function isInCache(string $key): bool
    {
        return Cache::has($key);
    }

    private function getFilter(int $quarter): callable
    {
        return function (ServiceHistory $appointment) use ($quarter) {
            return $appointment->getQuarter() === $quarter
                && !$appointment->isInitial()
                && $appointment->getDuration()->getTotalMinutes() <= self::MAX_APPOINTMENT_DURATION_INCLUDED_INTO_AVERAGE
                && $appointment->getDuration()->getTotalMinutes() > self::MIN_APPOINTMENT_DURATION_INCLUDED_INTO_AVERAGE;
        };
    }

    /**
     * @param Collection<ServiceHistory> $appointments
     *
     * @return Duration|null
     */
    private function calculateAverageDuration(Collection $appointments): Duration|null
    {
        $count = $appointments->count();
        if ($count < DomainContext::getMinAppointmentsToDetermineDuration()) {
            return null;
        }

        $totalDurationInMinutes = $appointments->sum(
            fn (ServiceHistory $serviceHistory) => $serviceHistory->getDuration()->getTotalMinutes()
        );

        $averageDurationInMinutes = (int) floor($totalDurationInMinutes / $count);

        return Duration::fromMinutes($averageDurationInMinutes);
    }

    private function buildCacheKey(int $customerId, int $quarter): string
    {
        return md5(self::AVERAGE_DURATION_PREFIX . $customerId . '_' . $quarter);
    }
}
