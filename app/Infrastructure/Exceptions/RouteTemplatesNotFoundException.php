<?php

declare(strict_types=1);

namespace App\Infrastructure\Exceptions;

use Carbon\CarbonInterface;

class RouteTemplatesNotFoundException extends \Exception
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
        return new self(__('messages.not_found.route_templates', [
            'office' => $officeName,
            'office_id' => $officeId,
            'date' => $date->toDateString(),
        ]));
    }

    /**
     * @param int $officeId
     * @param string $officeName
     * @param CarbonInterface $date
     *
     * @return self
     */
    public static function applicableTemplateNotFound(int $officeId, string $officeName, CarbonInterface $date): self
    {
        return new self(__('messages.not_found.applicable_route_template', [
            'office' => $officeName,
            'office_id' => $officeId,
            'date' => $date->toDateString(),
        ]));
    }
}
