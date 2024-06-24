<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Entities;

use App\Domain\RouteOptimization\Entities\ServiceHistory;
use App\Domain\RouteOptimization\Enums\ServiceType;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Carbon\Carbon;
use Tests\TestCase;
use Tests\Tools\TestValue;

class ServiceHistoryTest extends TestCase
{
    /**
     * @test
     */
    public function create_service_pro(): void
    {
        Carbon::setTestNow('2024-02-15 10:20:30');

        $serviceHistory = new ServiceHistory(
            1,
            TestValue::CUSTOMER_ID,
            ServiceType::INITIAL,
            Duration::fromMinutes(10),
            Carbon::now(),
        );

        $this->assertEquals(1, $serviceHistory->getId());
        $this->assertEquals(TestValue::CUSTOMER_ID, $serviceHistory->getCustomerId());
        $this->assertEquals(Duration::fromMinutes(10), $serviceHistory->getDuration());
        $this->assertEquals(Carbon::now()->toDateString(), $serviceHistory->getDate()->toDateString());
        $this->assertTrue($serviceHistory->isInitial());
        $this->assertEquals(1, $serviceHistory->getQuarter());
    }
}
