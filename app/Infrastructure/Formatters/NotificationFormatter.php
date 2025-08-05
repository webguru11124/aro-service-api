<?php

declare(strict_types=1);

namespace App\Infrastructure\Formatters;

use Exception;
use Carbon\CarbonInterface;

interface NotificationFormatter
{
    /**
     * @param CarbonInterface $date
     * @param array<int, array<string, string|float>> $officeScores
     * @param string $type
     *
     * @return string
     * @throws Exception
     */
    public function format(CarbonInterface $date, array $officeScores, string $type): string;
}
