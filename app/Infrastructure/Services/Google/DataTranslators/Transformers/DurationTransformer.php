<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Google\DataTranslators\Transformers;

use App\Domain\SharedKernel\ValueObjects\Duration;
use Google\Protobuf\Duration as GoogleDuration;

class DurationTransformer
{
    /**
     * @param Duration $duration
     *
     * @return GoogleDuration
     */
    public function transform(Duration $duration): GoogleDuration
    {
        return (new GoogleDuration())
            ->setSeconds($duration->getTotalSeconds());
    }
}
