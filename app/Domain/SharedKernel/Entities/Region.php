<?php

declare(strict_types=1);

namespace App\Domain\SharedKernel\Entities;

use App\Domain\Tracking\Exceptions\OfficeAlreadyAddedException;
use App\Domain\Tracking\ValueObjects\ConvexPolygon;
use Illuminate\Support\Collection;

class Region
{
    /** @var Collection<Office> */
    private Collection $offices;

    public function __construct(
        private int $id,
        private string $name
    ) {
        $this->offices = new Collection();
    }

    /**
     * Get the value of id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the value of name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get list of offices
     *
     * @return Collection
     */
    public function getOffices(): Collection
    {
        return $this->offices;
    }

    /**
     * Adds office to the region
     *
     * @throws OfficeAlreadyAddedException
     */
    public function addOffice(Office $office): self
    {
        $existingOffice = $this->getOfficeById($office->getId());

        if ($existingOffice) {
            throw new OfficeAlreadyAddedException();
        }

        $this->offices->add($office);

        return $this;
    }

    private function getOfficeById(int $officeId): Office|null
    {
        return $this->getOffices()->first(fn (Office $office) => $office->getId() === $officeId);
    }

    /**
     * Returns region boundary as convex polygon
     *
     * @return ConvexPolygon
     */
    public function getBoundaryPolygon(): ConvexPolygon
    {
        $points = $this->getOffices()->map(fn (Office $office) => $office->getLocation())->filter();

        return new ConvexPolygon($points);
    }
}
