<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\ConfigCat\Exceptions;

class InvalidConfigCatValueType extends \Exception
{
    /**
     * @param string $featureName
     * @param string $type
     * @param string $expectedType
     *
     * @return InvalidConfigCatValueType
     */
    public static function instance(string $featureName, string $type, string $expectedType): self
    {
        return new self(__('messages.config_cat.invalid_type', [
            'feature' => $featureName,
            'type' => $type,
            'expected_type' => $expectedType,
        ]));
    }
}
