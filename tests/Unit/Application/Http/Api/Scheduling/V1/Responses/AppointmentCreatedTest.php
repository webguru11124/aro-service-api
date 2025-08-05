<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Scheduling\V1\Responses;

use App\Application\Http\Api\Scheduling\V1\Responses\AppointmentCreated;
use Tests\TestCase;
use Tests\Tools\TestValue;
use Tests\Traits\AssertArrayHasAllKeys;

class AppointmentCreatedTest extends TestCase
{
    use AssertArrayHasAllKeys;

    /**
     * @test
     */
    public function it_creates_correct_response(): void
    {
        $response = new AppointmentCreated(TestValue::APPOINTMENT_ID, $this->faker->text(16));

        $responseData = $response->getData(true);

        $this->assertArrayHasAllKeys([
            '_metadata' => 'success',
            'result' => ['message', 'id', 'execution_sid'],
        ], $responseData);
    }
}
