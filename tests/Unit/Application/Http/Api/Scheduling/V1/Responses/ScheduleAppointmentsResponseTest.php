<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Scheduling\V1\Responses;

use App\Application\Http\Api\Scheduling\V1\Responses\ScheduleAppointmentsResponse;
use Tests\TestCase;
use Tests\Traits\AssertArrayHasAllKeys;

class ScheduleAppointmentsResponseTest extends TestCase
{
    use AssertArrayHasAllKeys;

    /**
     * @test
     */
    public function it_creates_correct_response(): void
    {
        $response = new ScheduleAppointmentsResponse();

        $this->assertArrayHasAllKeys([
            '_metadata' => [
                'success',
            ],
            'result' => ['message'],
        ], $response->getData(true));
    }
}
