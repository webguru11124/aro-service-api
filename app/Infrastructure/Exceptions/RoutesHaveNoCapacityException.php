<?php

declare(strict_types=1);

namespace App\Infrastructure\Exceptions;

use Carbon\CarbonInterface;

class RoutesHaveNoCapacityException extends \Exception
{
    /**
     * @param int $officeId
     * @param string $officeName
     * @param CarbonInterface $date
     *
     * @return self
     */
    public static function instance(int $officeId, string $officeName, CarbonInterface $date): self
    {
        return new self(__('messages.routes_optimization.no_routes_with_capacity', [
            'office' => $officeName,
            'office_id' => $officeId,
            'date' => $date->toDateString(),
        ]));
    }
}
