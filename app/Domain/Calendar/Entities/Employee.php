<?php

declare(strict_types=1);

namespace App\Domain\Calendar\Entities;

class Employee
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string|null $workdayId = null,
    ) {
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
     * @return string|null
     */
    public function getWorkdayId(): null|string
    {
        return $this->workdayId;
    }
}
