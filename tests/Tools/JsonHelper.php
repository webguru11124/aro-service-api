<?php

declare(strict_types=1);

namespace Tests\Tools;

class JsonHelper
{
    /**
     * Clone the given json data or array and replace keys values with the given overrides
     *
     * @param mixed $data
     * @param array<string, mixed> $overrides
     *
     * @return mixed
     */
    public static function cloneWith(mixed $data, array $overrides): mixed
    {
        $getType = gettype($data);
        $data = !($getType === 'array') ? json_decode($data, true) : $data;

        foreach ($overrides as $key => $value) {
            $data[$key] = !is_array($value) ? $value : self::cloneWith($data[$key], $value);
        }

        return ($getType === 'array') ? $data : json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
