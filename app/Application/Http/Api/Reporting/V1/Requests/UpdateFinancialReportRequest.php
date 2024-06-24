<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Reporting\V1\Requests;

use App\Application\Http\Requests\AbstractFormRequest;
use Illuminate\Validation\Rule;

class UpdateFinancialReportRequest extends AbstractFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'year' => 'sometimes|int|min:2020|max:2099',
            'month' => [
                'sometimes',
                'string',
                Rule::in(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']),
            ],
        ];
    }
}
