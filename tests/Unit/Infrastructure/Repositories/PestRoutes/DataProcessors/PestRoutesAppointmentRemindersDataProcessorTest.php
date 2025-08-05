<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\PestRoutes\DataProcessors;

use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesAppointmentRemindersDataProcessor;
use Aptive\PestRoutesSDK\Collection as PestRoutesCollection;
use Aptive\PestRoutesSDK\Resources\AppointmentReminders\AppointmentRemindersResource;
use Aptive\PestRoutesSDK\Resources\AppointmentReminders\AppointmentReminderStatus;
use Aptive\PestRoutesSDK\Resources\AppointmentReminders\Params\CreateAppointmentRemindersParams;
use Aptive\PestRoutesSDK\Resources\AppointmentReminders\Params\SearchAppointmentRemindersParams;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\AppointmentReminderData;
use Tests\Tools\TestValue;
use Tests\Traits\PestRoutesClientMockBuilderAware;

class PestRoutesAppointmentRemindersDataProcessorTest extends TestCase
{
    use PestRoutesClientMockBuilderAware;

    /**
     * @test
     *
     * ::extract
     */
    public function it_extracts_appointment_reminders(): void
    {
        $searchParamsMock = \Mockery::mock(SearchAppointmentRemindersParams::class);
        $appointmentReminders = AppointmentReminderData::getTestData(random_int(2, 5));
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(AppointmentRemindersResource::class)
            ->callSequence('appointmentReminders', 'includeData', 'search', 'all')
            ->methodExpectsArgs('search', [$searchParamsMock])
            ->willReturn(new PestRoutesCollection($appointmentReminders->all()))
            ->mock();

        $extractor = new PestRoutesAppointmentRemindersDataProcessor($pestRoutesClientMock);

        $result = $extractor->extract(TestValue::OFFICE_ID, $searchParamsMock);

        $this->assertEquals($appointmentReminders, $result);
    }

    /**
     * @test
     */
    public function it_creates_appointment_reminder(): void
    {
        $createAppointmentRemindersParams = new CreateAppointmentRemindersParams(
            appointmentId: TestValue::APPOINTMENT_ID,
            text: $this->faker->text(16),
            dateSent: $this->faker->dateTime(),
            emailSent: $this->faker->dateTime(),
            status: AppointmentReminderStatus::SENT
        );

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(AppointmentRemindersResource::class)
            ->callSequence('appointmentReminders', 'create')
            ->methodExpectsArgs(
                'create',
                fn (CreateAppointmentRemindersParams $params) => $params === $createAppointmentRemindersParams
            )
            ->willReturn(TestValue::APPOINTMENT_ID)
            ->mock();

        $subject = new PestRoutesAppointmentRemindersDataProcessor($pestRoutesClientMock);

        $result = $subject->create(TestValue::OFFICE_ID, $createAppointmentRemindersParams);

        $this->assertEquals(TestValue::APPOINTMENT_ID, $result);
    }
}
