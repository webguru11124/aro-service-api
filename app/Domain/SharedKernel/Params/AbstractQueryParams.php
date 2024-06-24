<?php

declare(strict_types=1);

namespace  App\Domain\SharedKernel\Params;

abstract class AbstractQueryParams
{
    /**
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;

    /**
     * @param array<string, mixed> $array
     *
     * @return array<string, mixed>
     */
    protected function removeNullValuesAndEmptyArraysFromParamsArray(array $array): array
    {
        return array_filter($array, static fn ($item) => !(is_null($item) || (is_array($item) && empty($item))));
    }
}
