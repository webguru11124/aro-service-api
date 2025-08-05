<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Vroom\DataTranslators\Transformers;

use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\ValueObjects\Skill;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Infrastructure\Services\Vroom\DataTranslators\Transformers\AppointmentTransformer;
use Carbon\Carbon;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\TestValue;
use Tests\Traits\VroomDataAndObjects;

class AppointmentTransformerTest extends TestCase
{
    use VroomDataAndObjects;

    private const EXPECTED_ARRIVAL_WINDOW_START = 8;
    private const EXPECTED_ARRIVAL_WINDOW_END = 20;

    /**
     * @test
     */
    public function transform_appointment_without_start_and_end_times(): void
    {
        $appointment = $this->getAppointmentWithoutStartAndEndTimes();
        $actual = (new AppointmentTransformer())->transform($appointment);

        $this->assertEquals($this->expectedWithoutStartAndEndTimes(), $actual->toArray());
    }

    private function expectedWithoutStartAndEndTimes(): array
    {
        return [
            'delivery' => [1],
            'description' => self::APPOINTMENT_BASIC_LABEL,
            'id' => TestValue::APPOINTMENT_ID,
            'location' => [
                TestValue::LONGITUDE,
                TestValue::LATITUDE,
            ],
            'priority' => 20,
            'service' => self::TIMESTAMP_25_MINUTES,
            'skills' => [
                Skill::INITIAL_SERVICE,
                Skill::NC,
            ],
            'setup' => self::TIMESTAMP_3_MINUTES,
            'time_windows' => [
                [
                    Carbon::tomorrow()->hour(self::EXPECTED_ARRIVAL_WINDOW_START)->timestamp,
                    Carbon::tomorrow()->hour(self::EXPECTED_ARRIVAL_WINDOW_END)->timestamp,
                ],
            ],
        ];
    }

    private function getAppointmentWithoutStartAndEndTimes(): Appointment
    {
        /** @var Appointment $appointment */
        $appointment = AppointmentFactory::make([
            'id' => TestValue::APPOINTMENT_ID,
            'description' => self::APPOINTMENT_BASIC_LABEL,
            'location' => new Coordinate(
                TestValue::LATITUDE,
                TestValue::LONGITUDE
            ),
            'skills' => collect([
                new Skill(Skill::INITIAL_SERVICE),
                new Skill(Skill::NC),
            ]),
        ]);
        $appointment
            ->setTimeWindow(null)
            ->setPriority(20)
            ->setDuration($this->domainDuration25Minutes())
            ->setSetupDuration($this->domainDuration3Minutes());

        return $appointment;
    }
}
