<?php

declare(strict_types=1);

namespace App\Domain\Notification\Entities;

class NotificationType
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
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
}
