<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\DTO;

class Skills implements VroomArrayFormat
{
    /**
     * @param int[] $skills
     */
    public function __construct(
        private array $skills = []
    ) {
    }

    public function add(int $skill): void
    {
        $this->skills[] = $skill;
    }

    /**
     * @return int[]
     */
    public function toArray(): array
    {
        return $this->skills;
    }
}
