<?php

declare(strict_types=1);

namespace App\Domain\Calendar\ValueObjects;

use Carbon\CarbonInterface;

class RecurringEventId
{
    public function __construct(
        private int $id,
        private int $officeId,
        private CarbonInterface $date,
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
     * @return CarbonInterface
     */
    public function getDate(): CarbonInterface
    {
        return $this->date;
    }

    /**
     * @return int
     */
    public function getOfficeId(): int
    {
        return $this->officeId;
    }

    /**
     * Creates instance of the class with given parameters
     *
     * @param int $id
     * @param int $officeId
     * @param CarbonInterface $date
     *
     * @return RecurringEventId
     */
    public static function create(int $id, int $officeId, CarbonInterface $date): RecurringEventId
    {
        return new self($id, $officeId, $date);
    }
}
