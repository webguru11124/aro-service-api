<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Entities;

use Carbon\CarbonInterface;

class Plan
{
    private const INITIAL_SERVICE_PERIOD_DAYS = 2;

    public function __construct(
        private int $id,
        private string $name,
        private int $serviceTypeId,
        private int $summerServiceIntervalDays,
        private int $winterServiceIntervalDays,
        private int $summerServicePeriodDays,
        private int $winterServicePeriodDays,
        private int $initialFollowUpDays,
    ) {
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getServiceTypeId(): int
    {
        return $this->serviceTypeId;
    }

    /**
     * @return int
     */
    public function getSummerServiceIntervalDays(): int
    {
        return $this->summerServiceIntervalDays;
    }

    /**
     * @return int
     */
    public function getWinterServiceIntervalDays(): int
    {
        return $this->winterServiceIntervalDays;
    }

    /**
     * @return int
     */
    public function getInitialFollowUpDays(): int
    {
        return $this->initialFollowUpDays;
    }

    /**
     * @param CarbonInterface $date
     *
     * @return int
     */
    public function getServiceIntervalDays(CarbonInterface $date): int
    {
        return $this->isWinterPeriod($date)
            ? $this->getWinterServiceIntervalDays()
            : $this->getSummerServiceIntervalDays();
    }

    /**
     * @param CarbonInterface $date
     *
     * @return int
     */
    public function getServicePeriodDays(CarbonInterface $date): int
    {
        return $this->isWinterPeriod($date)
            ? $this->getWinterServicePeriodDays()
            : $this->getSummerServicePeriodDays();
    }

    private function isWinterPeriod(CarbonInterface $date): bool
    {
        return $date->month < 4 || $date->month > 10; // [april..october] => summer services
    }

    private function getWinterServicePeriodDays(): int
    {
        return $this->winterServicePeriodDays;
    }

    public function getSummerServicePeriodDays(): int
    {
        return $this->summerServicePeriodDays;
    }

    /**
     * @return int
     */
    public function getInitialServicePeriodDays(): int
    {
        return self::INITIAL_SERVICE_PERIOD_DAYS;
    }
}
