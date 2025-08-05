<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\ValueObjects;

class CustomerPreferences
{
    private const DEFAULT_PREFERRED_START = '08:00:00';
    private const DEFAULT_PREFERRED_END = '20:00:00';

    public function __construct(
        private string|null $preferredStart = null,
        private string|null $preferredEnd = null,
        private int|null $preferredEmployeeId = null,
        private int|null $preferredDay = null,
    ) {
    }

    /**
     * @return string
     */
    public function getPreferredStart(): string
    {
        return $this->preferredStart ?? self::DEFAULT_PREFERRED_START;
    }

    /**
     * @return string
     */
    public function getPreferredEnd(): string
    {
        return $this->preferredEnd ?? self::DEFAULT_PREFERRED_END;
    }

    /**
     * @return int|null
     */
    public function getPreferredEmployeeId(): int|null
    {
        return $this->preferredEmployeeId;
    }

    /**
     * @return int|null
     */
    public function getPreferredDay(): int|null
    {
        return $this->preferredDay;
    }

    /**
     * It returns new object with reset preferred employee id
     *
     * @return self
     */
    public function resetPreferredEmployeeId(): self
    {
        return new self(
            $this->preferredStart,
            $this->preferredEnd,
            null,
            $this->preferredDay,
        );
    }
}
