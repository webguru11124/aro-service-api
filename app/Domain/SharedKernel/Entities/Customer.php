<?php

declare(strict_types=1);

namespace App\Domain\SharedKernel\Entities;

use App\Domain\SharedKernel\ValueObjects\Address;
use App\Domain\SharedKernel\ValueObjects\Coordinate;

class Customer
{
    public function __construct(
        private int $id,
        private string $name,
        private Coordinate $location,
        private Address $address,
        private string $phone,
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
     * @return Address
     */
    public function getAddress(): Address
    {
        return $this->address;
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }
}
