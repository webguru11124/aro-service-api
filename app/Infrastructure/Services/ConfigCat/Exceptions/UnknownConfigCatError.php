<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\ConfigCat\Exceptions;

class UnknownConfigCatError extends \Exception
{
    /**
     * @param string $featureName
     *
     * @return UnknownConfigCatError
     */
    public static function instance(string $featureName): self
    {
        return new self(__('messages.config_cat.unknown_error', [
            'feature' => $featureName,
        ]));
    }
}
