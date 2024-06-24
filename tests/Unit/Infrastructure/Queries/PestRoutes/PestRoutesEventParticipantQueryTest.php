<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Queries\PestRoutes;

use App\Domain\Calendar\Entities\Event;
use App\Infrastructure\Queries\PestRoutes\PestRoutesEventParticipantQuery;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesEmployeesDataProcessor;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\EmployeeData;
use Tests\Tools\TestValue;

class PestRoutesEventParticipantQueryTest extends TestCase
{
    private MockInterface|PestRoutesEmployeesDataProcessor $dataProcessorMock;
    private PestRoutesEventParticipantQuery $query;
    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataProcessorMock = \Mockery::mock(PestRoutesEmployeesDataProcessor::class);
        $this->query = new PestRoutesEventParticipantQuery($this->dataProcessorMock);
        $this->event = \Mockery::mock(Event::class);
    }

    /** @test */
    public function it_correctly_fetches_participants_for_an_event(): void
    {
        $officeStaffData = EmployeeData::getTestData(2, [
            'officeID' => TestValue::OFFICE_ID,
            'type' => 0,
        ], [
            'officeID' => TestValue::OFFICE_ID,
            'type' => 0,
            'fname' => 'Alpha',
            'lname' => 'Scheduler',
        ]);
        $technicianData = EmployeeData::getTestData(2, ['officeId' => TestValue::OFFICE_ID, 'type' => 1]);

        $this->event
            ->shouldReceive('getOfficeId')
            ->once()
            ->andReturn(TestValue::OFFICE_ID);

        $this->event
            ->shouldReceive('getParticipantIds')
            ->once()
            ->andReturn(collect([$officeStaffData[1]->id]));

        $this->dataProcessorMock
            ->shouldReceive('extract')
            ->twice()
            ->andReturn($officeStaffData, $technicianData);

        $participants = $this->query->find($this->event);

        $this->assertCount(4, $participants);
        $this->assertTrue($participants->first()->isInvited());
        $this->assertFalse($participants->last()->isInvited());
        $this->assertEquals('Alpha Scheduler', $participants->first()->getName());
    }
}
