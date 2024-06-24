<?php

declare(strict_types=1);

namespace App\Infrastructure\Helpers;

class DataMask
{
    private const MASK = '*****';

    /**
     * @return string[]
     */
    protected static function getFieldsToMask(): array
    {
        return config('data-mask.field_to_mask');
    }

    /**
     * @param array<mixed> $data
     * @param string[] $fieldsToMask
     *
     * @return array<mixed>
     */
    public static function mask(array $data, array $fieldsToMask = []): array
    {
        $fieldsToMask = $fieldsToMask ?: static::getFieldsToMask();

        foreach ($fieldsToMask as $field) {
            $keys = explode('.', $field);
            $data = static::maskField($data, $keys);
        }

        return $data;
    }

    /**
     * @param array<mixed> $data
     * @param string[]     $keys
     *
     * @return array<mixed>
     */
    protected static function maskField(array $data, array $keys): array
    {
        $key = array_shift($keys);

        if ($key === '*') {
            $newData = [];
            foreach ($data as $index => $item) {
                $newData[$index] = static::maskField($item, $keys);
            }

            return $newData;
        } elseif (isset($data[$key])) {
            if ($keys) {
                $data[$key] = static::maskField($data[$key], $keys);
            } else {
                $data[$key] = self::MASK;
            }
        }

        return $data;
    }
}
