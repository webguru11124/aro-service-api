<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\SharedKernel\Entities;

use Tests\TestCase;
use App\Domain\SharedKernel\Entities\WorkPeriod;

class WorkPeriodTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_work_period_with_correct_descriptor(): void
    {
        $workPeriod = new WorkPeriod('M-F 9am-6pm');

        $this->assertEquals('M-F 9am-6pm', $workPeriod->getDescriptor());
    }
}
