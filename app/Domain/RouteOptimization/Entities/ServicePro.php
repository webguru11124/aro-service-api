<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Entities;

use App\Domain\RouteOptimization\ValueObjects\Skill;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Illuminate\Support\Collection;

class ServicePro
{
    use HasRouteId;

    /** @var Collection<int|string, Skill>  */
    private Collection $skills;

    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly Coordinate $startLocation,
        private readonly Coordinate $endLocation,
        private TimeWindow $workingHours,
        private readonly string|null $workdayId,
        private readonly string|null $avatarBase64 = null,
    ) {
        $this->skills = new Collection();
        $this->skills->add($this->getPersonalSkill());
    }

    /**
     * @return string|null
     */
    public function getAvatarBase64(): string|null
    {
        return $this->avatarBase64;
    }

    /**
     * @return Coordinate
     */
    public function getEndLocation(): Coordinate
    {
        return $this->endLocation;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Collection<int|string, Skill>
     */
    public function getSkills(): Collection
    {
        return $this->skills;
    }

    /**
     * @param Skill[] $skills
     *
     * @return $this
     */
    public function addSkills(array $skills): self
    {
        $existingSkillValues = $this->skills->map(fn (Skill $skill) => $skill->value)->toArray();
        $filteredSkills = array_filter($skills, fn (Skill $skill) => !in_array($skill->value, $existingSkillValues));

        $this->skills = $this->skills->merge($filteredSkills);

        return $this;
    }

    /**
     * @return Collection
     */
    public function getSkillsWithoutPersonal(): Collection
    {
        return $this->skills->filter(fn (Skill $skill) => $skill->value !== $this->getPersonalSkill()->value);
    }

    /**
     * @return Coordinate
     */
    public function getStartLocation(): Coordinate
    {
        return $this->startLocation;
    }

    /**
     * @return TimeWindow
     */
    public function getWorkingHours(): TimeWindow
    {
        return $this->workingHours;
    }

    /**
     * @param TimeWindow $workingHours
     *
     * @return $this
     */
    public function setWorkingHours(TimeWindow $workingHours): self
    {
        $this->workingHours = new TimeWindow(
            $workingHours->getStartAt()->clone(),
            $workingHours->getEndAt()->clone(),
        );

        return $this;
    }

    /**
     * @return Skill
     */
    public function getPersonalSkill(): Skill
    {
        return Skill::createPersonalSkillFromId($this->id);
    }

    /**
     * @return string|null
     */
    public function getWorkdayId(): string|null
    {
        return $this->workdayId;
    }
}
