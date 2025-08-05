<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Entities;

use Carbon\CarbonInterface;

class Route
{
    public function __construct(
        private readonly int $id,
        private readonly int $officeId,
        private readonly CarbonInterface $date,
        private readonly int $templateId,
        private int|null $employeeId = null,
    ) {
    }

    /**
     * @return int
     */
    public function getOfficeId(): int
    {
        return $this->officeId;
    }

    /**
     * @return CarbonInterface
     */
    public function getDate(): CarbonInterface
    {
        return $this->date;
    }

    /**
     * @return int
     */
    public function getTemplateId(): int
    {
        return $this->templateId;
    }

    /**
     * @return int|null
     */
    public function getId(): int|null
    {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getEmployeeId(): int|null
    {
        return $this->employeeId;
    }
}
