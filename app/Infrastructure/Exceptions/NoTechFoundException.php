<?php

declare(strict_types=1);

namespace App\Infrastructure\Exceptions;

use Carbon\CarbonInterface;

class NoTechFoundException extends \Exception
{
    /**
     * @param int $officeId
     * @param string $officeName
     * @param CarbonInterface $date
     * @param string $techName
     *
     * @return self
     */
    public static function instance(int $officeId, string $officeName, CarbonInterface $date, string $techName): self
    {
        return new self(__('messages.not_found.reschedule_route_tech', [
            'office' => $officeName,
            'office_id' => $officeId,
            'date' => $date->toDateString(),
            'name' => $techName,
        ]));
    }
}
