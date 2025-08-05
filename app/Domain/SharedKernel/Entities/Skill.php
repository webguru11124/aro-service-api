<?php

declare(strict_types=1);

namespace App\Domain\SharedKernel\Entities;

class Skill
{
    public function __construct(
        private string $descriptor
    ) {
    }

    /**
     * @return string
     */
    public function getDescriptor(): string
    {
        return $this->descriptor;
    }
}
