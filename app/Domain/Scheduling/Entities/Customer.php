<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Entities;

use App\Domain\SharedKernel\ValueObjects\Coordinate;

class Customer
{
    public function __construct(
        private int $id,
        private string $name,
        private Coordinate $location,
        private string|null $email,
        private int|null $preferredTechId,
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
     * @return Coordinate
     */
    public function getLocation(): Coordinate
    {
        return $this->location;
    }

    /**
     * @return string|null
     */
    public function getEmail(): string|null
    {
        return $this->email;
    }

    /**
     * @return int|null
     */
    public function getPreferredTechId(): int|null
    {
        return $this->preferredTechId;
    }

    /**
     * This method is used to reset the preferred tech id of the customer entity
     *
     * @return self
     */
    public function resetPreferredTechId(): self
    {
        $this->preferredTechId = null;

        return $this;
    }
}
