<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Web\ServiceDurationCalculation\Controllers;

use Tests\TestCase;

class ServiceDurationCalculationEndpointTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_200_status_code_for_index_route(): void
    {
        $response = $this->get(route('service-duration-calculations.index'));

        $response->assertOk();
        $response->assertViewIs('service-duration-calculator');
    }

    /**
     * @test
     */
    public function it_calculates_service_duration_correctly(): void
    {
        $formData = [
            'calculateServiceDuration' => '1',
            'linearFootPerSecond' => '1.45',
            'squareFootageOfHouse' => '2500',
            'squareFootageOfLot' => '3000',
        ];

        $response = $this->withSession([])->post(route('service-duration-calculations.calculate'), $formData);

        $response->assertSessionHas('results');
        $response->assertRedirect();
    }

    /**
     * @test
     */
    public function it_calculates_linear_feet_per_second_correctly(): void
    {
        $formData = [
            'calculateLf' => '1',
            'actualDuration' => '120',
            'squareFootageOfHouse' => '2500',
            'squareFootageOfLot' => '3000',
        ];

        $response = $this->withSession([])->post(route('service-duration-calculations.calculate'), $formData);

        $response->assertSessionHas('results');
        $response->assertRedirect();
    }
}
