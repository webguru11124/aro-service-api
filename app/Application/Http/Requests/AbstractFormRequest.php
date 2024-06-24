<?php

declare(strict_types=1);

namespace App\Application\Http\Requests;

use App\Application\Http\Responses\ValidationErrorResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class AbstractFormRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    abstract public function rules(): array;

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            new ValidationErrorResponse(__('messages.validator.invalid_request'), $validator->errors()->all())
        );
    }

    /**
     * @param array<string, mixed> $rules
     *
     * @return array<string, mixed>
     */
    protected function withPagination(array $rules): array
    {
        return array_merge($rules, [
            'page' => 'nullable|int|gte:1',
            'per_page' => 'nullable|int|gte:1',
        ]);
    }
}
