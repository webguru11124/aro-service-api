<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Vroom\DataTranslators\Transformers;

use App\Infrastructure\Services\Vroom\DataTranslators\Transformers\MeetingTransformer;
use Tests\TestCase;
use Tests\Tools\Factories\RouteOptimization\MeetingFactory;

class MeetingTransformerTest extends TestCase
{
    /**
     * @test
     */
    public function it_transforms_meeting_object(): void
    {
        $meeting = MeetingFactory::make();

        $job = (new MeetingTransformer())->transform($meeting);

        $expected = [
            'id' => $meeting->getId(),
            'description' => $meeting->getDescription(),
            'skills' => [$meeting->getId()],
            'service' => $meeting->getDuration()->getTotalSeconds(),
            'delivery' => [1],
            'location' => [
                $meeting->getLocation()->getLongitude(),
                $meeting->getLocation()->getLatitude(),
            ],
            'priority' => 100,
            'setup' => 0,
            'time_windows' => [
                [
                    $meeting->getExpectedArrival()->getStartAt()->timestamp,
                    $meeting->getExpectedArrival()->getEndAt()->timestamp,
                ],
            ],
        ];
        $this->assertEquals($expected, $job->toArray());
    }
}
