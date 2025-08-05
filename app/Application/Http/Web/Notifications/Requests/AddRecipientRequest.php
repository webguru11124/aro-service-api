<?php

declare(strict_types=1);

namespace App\Application\Http\Web\Notifications\Requests;

use App\Application\Http\Requests\AbstractFormRequest;

class AddRecipientRequest extends AbstractFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'phone' => 'nullable|string|required_without:email|max:10',
            'email' => 'nullable|email|required_without:phone',
        ];
    }
}
