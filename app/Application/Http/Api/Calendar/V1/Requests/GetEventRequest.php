<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Requests;

use App\Application\Http\Requests\AbstractFormRequest;

/**
 * @property int|null $office_id
 * @property string $start_date
 * @property string $end_date
 * @property string|null $search_text
 * @property int|null $page
 * @property int|null $per_page
 */
class GetEventRequest extends AbstractFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->withPagination([
            'office_id' => 'nullable|int|gte:1',
            'start_date' => 'nullable|date|date_format:Y-m-d|required_with:end_date',
            'end_date' => 'nullable|date|date_format:Y-m-d|after_or_equal:start_date|required_with:start_date',
            'search_text' => 'nullable|string|min:2',
        ]);
    }

    protected function prepareForValidation(): void
    {
        if (!empty($this->search_text)) {
            $this->merge(['search_text' => trim($this->search_text)]);
        }
    }
}
