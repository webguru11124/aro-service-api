<?php

declare(strict_types=1);

namespace App\Domain\SharedKernel\Entities;

use App\Domain\SharedKernel\ValueObjects\Address;
use Illuminate\Support\Collection;

class Employee
{
    /**
     * @param Collection<Skill> $skills
     */
    public function __construct(
        private string $employeeId,
        private string $firstName,
        private string $lastName,
        private string $dateOfBirth,
        private string $dateOfHire,
        private string $managerId,
        private string $email,
        private string $phone,
        private Address $address,
        private WorkPeriod $workPeriod,
        private Collection $skills
    ) {
    }

    /**
     * @return string
     */
    public function getEmployeeId(): string
    {
        return $this->employeeId;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @return string
     */
    public function getFullName(): string
    {
        return $this->getFirstName() . ' ' . $this->getLastName();
    }

    /**
     * @return string
     */
    public function getDateOfBirth(): string
    {
        return $this->dateOfBirth;
    }

    /**
     * @return string
     */
    public function getDateOfHire(): string
    {
        return $this->dateOfHire;
    }

    /**
     * @return string
     */
    public function getManagerId(): string
    {
        return $this->managerId;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @return Address
     */
    public function getAddress(): Address
    {
        return $this->address;
    }

    /**
     * @return Collection
     */
    public function getSkills(): Collection
    {
        return $this->skills;
    }

    /**
     * @return WorkPeriod
     */
    public function getWorkPeriod(): WorkPeriod
    {
        return $this->workPeriod;
    }
}
