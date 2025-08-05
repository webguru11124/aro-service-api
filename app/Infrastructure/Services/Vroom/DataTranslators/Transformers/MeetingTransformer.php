<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\DataTranslators\Transformers;

use App\Domain\RouteOptimization\Entities\WorkEvent\Meeting;
use App\Domain\RouteOptimization\ValueObjects\Skill;
use App\Infrastructure\Services\Vroom\DTO\Delivery;
use App\Infrastructure\Services\Vroom\DTO\Job;
use App\Infrastructure\Services\Vroom\DTO\Skills;

class MeetingTransformer
{
    private const DEFAULT_PRIORITY = 100;

    /**
     * @param Meeting $meeting
     *
     * @return Job
     */
    public function transform(Meeting $meeting): Job
    {
        $vroomSkills = new Skills();
        $skillsTransformer = new SkillTransformer();

        $vroomSkills->add($skillsTransformer->transform(
            new Skill($meeting->getId())
        ));

        $delivery = new Delivery([1]);
        $timeWindow = (new TimeWindowTransformer())->transform($meeting->getExpectedArrival());
        $location = (new CoordinateTransformer())->transform($meeting->getLocation());

        return new Job(
            id: $meeting->getId(),
            description: $meeting->getDescription(),
            skills: $vroomSkills,
            service: $meeting->getDuration()->getTotalSeconds(),
            delivery: $delivery,
            location: $location,
            priority: self::DEFAULT_PRIORITY,
            setup: 0,
            timeWindows: $timeWindow
        );
    }
}
