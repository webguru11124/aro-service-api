<?php

declare(strict_types=1);

namespace App\Domain\SharedKernel\ValueObjects;

readonly class Address
{
    public function __construct(
        private string|null $address = null,
        private string|null $city = null,
        private string|null $state = null,
        private string|null $zip = null,
    ) {
    }

    /**
     * @return string|null
     */
    public function getAddress(): string|null
    {
        return $this->address;
    }

    /**
     * @return string|null
     */
    public function getCity(): string|null
    {
        return $this->city;
    }

    /**
     * @return string|null
     */
    public function getState(): string|null
    {
        return $this->state;
    }

    /**
     * @return string|null
     */
    public function getZip(): string|null
    {
        return $this->zip;
    }

    /**
     * @return string
     */
    public function getFullAddress(): string
    {
        return "$this->address $this->city $this->state $this->zip";
    }

    /**
     * @return string
     */
    public function toJson(): string
    {
        return json_encode([
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
        ]);
    }
}
