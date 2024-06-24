<?php

declare(strict_types=1);

namespace App\Application\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NativeBoolean implements ValidationRule
{
    /**
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     *
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_bool($value)) {
            $fail($attribute . ' must be a boolean.');
        }
    }
}
