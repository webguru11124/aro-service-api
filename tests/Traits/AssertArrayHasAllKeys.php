<?php

declare(strict_types=1);

namespace Tests\Traits;

use PHPUnit\Framework\ExpectationFailedException;

/**
 * Adds assertion method that asserts an N level nested array has all specified keys
 */
trait AssertArrayHasAllKeys
{
    /**
     * Checks an array against an array of expected keys. Ensures that all keys which are expected are in the correct level of the array.
     * Recursive - Checks N layer arrays
     *
     * @param array $keys - An array of expected keys to search for
     * @param array $haystack - The array to search
     *
     * @return bool - Returns true if all keys are found in the array
     * @throws ExpectationFailedException
     */
    public function assertArrayHasAllKeys(array $keys, array $haystack): bool
    {
        foreach ($keys as $key => $value) {
            $this->checkCurrentArrayLevelForKey($key, $value, $haystack);
            $this->checkKeysInNextArrayLevel($key, $value, $haystack);
        }

        // Base case (No exception was thrown)
        return true;
    }

    private function checkCurrentArrayLevelForKey($key, $value, $array): void
    {
        if ($this->isKeyToSearchFor($key)) {
            $this->checkForKeyInArray($key, $array);
        } elseif ($this->isKeyToSearchFor($value)) {
            $this->checkForKeyInArray($value, $array);
        }
    }

    private function checkKeysInNextArrayLevel($key, $value, $array): void
    {
        // Recurse if there is another array
        if ($this->containsAnotherArrayLevelToCheck($value)) {
            $this->assertArrayHasAllKeys($value, $array[$key]);
        }
    }

    private function containsAnotherArrayLevelToCheck($mixed): bool
    {
        return is_array($mixed);
    }

    private function checkForKeyInArray($key, $array): void
    {
        // PHPUnit built in assert function and custom message
        $message = $this->createMessage($array);
        $this->assertArrayHasKey($key, $array, $message);
    }

    private function isKeyToSearchFor($key): bool
    {
        return is_string($key);
    }

    private function createMessage(array $array): string
    {
        $message = 'Array: ' . PHP_EOL;
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = 'Array(' . count($value) . ')';
            }
            $message .= '[' . $key . '] => ' . $value . PHP_EOL;
        }

        return $message;
    }
}
