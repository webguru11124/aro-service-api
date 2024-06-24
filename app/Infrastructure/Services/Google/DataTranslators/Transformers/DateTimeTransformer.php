<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Google\DataTranslators\Transformers;

use DateTimeInterface;
use Google\Protobuf\Timestamp;

class DateTimeTransformer
{
    /**
     * @param DateTimeInterface $date
     *
     * @return Timestamp
     */
    public function transform(DateTimeInterface $date): Timestamp
    {
        return (new Timestamp())
            ->setSeconds($date->getTimestamp());
    }
}
