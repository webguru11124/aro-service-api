<?php

declare(strict_types=1);

namespace App\Infrastructure\Exceptions;

use Carbon\CarbonInterface;

class NoServiceProFoundException extends \Exception
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
        return new self(__('messages.not_found.service_pro', [
            'office' => $officeName,
            'office_id' => $officeId,
            'date' => $date->toDateString(),
        ]));
    }
}
