<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Calendar\V1\Resources;

use App\Application\Http\Api\Calendar\V1\Resources\CalendarGetParticipantsResource;
use App\Domain\Calendar\Entities\Participant;
use Illuminate\Http\Request;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\TestValue;

class CalendarGetParticipantsResourceTest extends TestCase
{
    private CalendarGetParticipantsResource $resource;
    private Request|MockInterface $request;

    protected function setup(): void
    {
        parent::setUp();

        $this->request = Mockery::mock(Request::class);
    }

    /**
     * @test
     */
    public function it_creates_expected_array_representation_of_resource(): void
    {
        $participant = new Participant(
            TestValue::EMPLOYEE1_ID,
            'John Doe',
            true,
            TestValue::WORKDAY_ID
        );

        $this->resource = new CalendarGetParticipantsResource($participant);
        $resource = $this->resource->toArray($this->request);

        $this->assertEquals(
            [
                'id' => $participant->getId(),
                'name' => $participant->getName(),
                'is_invited' => $participant->isInvited(),
                'external_id' => $participant->getWorkdayId(),
            ],
            $resource
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->request);
        unset($this->resource);
    }
}
