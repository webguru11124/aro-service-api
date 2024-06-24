<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Google\DataTranslators\Transformers;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\Duration as DomainDuration;
use App\Infrastructure\Services\Google\DataTranslators\Transformers\AppointmentTransformer;
use Carbon\Carbon;
use Google\Cloud\Optimization\V1\TimeWindow;
use Google\Protobuf\Duration;
use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Timestamp;
use Google\Type\LatLng;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\TestValue;

class AppointmentTransformerTest extends TestCase
{
    /**
     * @test
     */
    public function it_transforms_appointment_object(): void
    {
        $appointment = AppointmentFactory::make([
            'id' => TestValue::APPOINTMENT_ID,
            'location' => new Coordinate(TestValue::LATITUDE, TestValue::LONGITUDE),
            'duration' => DomainDuration::fromMinutes(TestValue::APPOINTMENT_DURATION),
            'setupDuration' => DomainDuration::fromMinutes(TestValue::APPOINTMENT_SETUP_DURATION),
        ]);

        $shipment = (new AppointmentTransformer())->transform($appointment);
        $visitRequest = $shipment->getDeliveries()[0];

        $expectedLatLng = (new LatLng())->setLatitude(TestValue::LATITUDE)->setLongitude(TestValue::LONGITUDE);
        $expectedDuration = (new Duration())->setSeconds((TestValue::APPOINTMENT_DURATION + TestValue::APPOINTMENT_SETUP_DURATION) * 60);
        $startAt = Carbon::tomorrow()->hour(TestValue::START_OF_DAY);
        $repeatedField = new RepeatedField(GPBType::MESSAGE, TimeWindow::class);
        $repeatedField[] = (new TimeWindow())
            ->setStartTime((new Timestamp())->setSeconds($startAt->timestamp))
            ->setEndTime(
                (new Timestamp())->setSeconds(
                    $startAt->clone()->addMinutes(TestValue::APPOINTMENT_DURATION)->timestamp
                )
            );

        $this->assertEquals(TestValue::APPOINTMENT_ID, $visitRequest->getLabel());
        $this->assertEquals($expectedLatLng, $visitRequest->getArrivalLocation());
        $this->assertEquals($expectedDuration, $visitRequest->getDuration());
        $this->assertEquals($repeatedField, $visitRequest->getTimeWindows());
    }
}
